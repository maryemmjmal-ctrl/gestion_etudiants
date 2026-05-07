<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$flash = getFlash();
$etudiants = $pdo->query("SELECT * FROM etudiants ORDER BY nom")->fetchAll();
$matieres  = $pdo->query("SELECT * FROM matieres ORDER BY nom")->fetchAll();

// Enregistrer absence
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etudiant_id = intval($_POST['etudiant_id']);
    $matiere_id  = intval($_POST['matiere_id']) ?: null;
    $date        = $_POST['date_absence'];
    $justifiee   = isset($_POST['justifiee']) ? 1 : 0;
    $motif       = trim($_POST['motif'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO absences (etudiant_id, matiere_id, date_absence, justifiee, motif, enseignant_id)
                           VALUES (?,?,?,?,?,?)");
    $stmt->execute([$etudiant_id, $matiere_id, $date, $justifiee, $motif, currentUser()['id']]);
    flash("Absence enregistrée !");
    header('Location: absences.php');
    exit;
}

// Supprimer
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM absences WHERE id=?")->execute([intval($_GET['supprimer'])]);
    flash("Absence supprimée.", 'warning');
    header('Location: absences.php');
    exit;
}

// Filtres
$filtre_e = intval($_GET['etudiant'] ?? 0);
$sql = "SELECT a.*, e.nom as e_nom, e.prenom as e_prenom, e.numero_inscription, m.nom as m_nom
        FROM absences a
        JOIN etudiants e ON e.id = a.etudiant_id
        LEFT JOIN matieres m ON m.id = a.matiere_id
        WHERE 1=1";
$params = [];
if ($filtre_e) { $sql .= " AND a.etudiant_id=?"; $params[] = $filtre_e; }
$sql .= " ORDER BY a.date_absence DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absences = $stmt->fetchAll();

// Alertes
$alertes = $pdo->query("
    SELECT e.nom, e.prenom, e.numero_inscription, COUNT(a.id) as nb
    FROM absences a JOIN etudiants e ON e.id=a.etudiant_id
    WHERE a.justifiee=0
    GROUP BY e.id HAVING nb >= " . SEUIL_ABSENCES . " ORDER BY nb DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Gestion des Absences</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Gestion des Absences</h1><div class="breadcrumb">Tableau de bord / Absences</div></div>
        <button class="btn btn-primary" onclick="toggleForm('form-abs')"><i class="fas fa-plus"></i> Enregistrer une absence</button>
    </div>
    <div class="content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Alertes -->
        <?php if (!empty($alertes)): ?>
        <div class="card" style="border-left:4px solid #e74c3c;">
            <div class="card-header" style="background:#fde8e8;">
                <h3 style="color:#c0392b;">⚠️ Alertes — Étudiants avec <?= SEUIL_ABSENCES ?>+ absences injustifiées</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Étudiant</th><th>N° Inscription</th><th>Absences injustifiées</th></tr></thead>
                    <tbody>
                    <?php foreach ($alertes as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></td>
                            <td><?= htmlspecialchars($a['numero_inscription']) ?></td>
                            <td><span class="badge badge-danger"><?= $a['nb'] ?> absences</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="card" id="form-abs" style="display:none;">
            <div class="card-header">
                <h3><i class="fas fa-calendar-times"></i> Enregistrer une absence</h3>
                <button class="btn btn-secondary btn-sm" onclick="toggleForm('form-abs')">✕</button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Étudiant *</label>
                            <select name="etudiant_id" class="form-control" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($etudiants as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Matière</label>
                            <select name="matiere_id" class="form-control">
                                <option value="">— Toutes matières —</option>
                                <?php foreach ($matieres as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" name="date_absence" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Motif</label>
                            <input type="text" name="motif" class="form-control" placeholder="Motif (facultatif)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="justifiee"> Absence justifiée
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>

        <!-- Filtre -->
        <div class="card">
            <div class="card-body">
                <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
                    <div class="form-group" style="margin:0;min-width:220px;">
                        <label>Filtrer par étudiant</label>
                        <select name="etudiant" class="form-control">
                            <option value="">Tous</option>
                            <?php foreach ($etudiants as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $filtre_e==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="absences.php" class="btn btn-secondary">Réinitialiser</a>
                </form>
            </div>
        </div>

        <!-- Liste absences -->
        <div class="card">
            <div class="card-header"><h3>📋 Absences enregistrées (<?= count($absences) ?>)</h3></div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                <table>
                    <thead><tr><th>Date</th><th>Étudiant</th><th>N° Inscription</th><th>Matière</th><th>Justifiée</th><th>Motif</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($absences)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:#888;">Aucune absence enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($absences as $a): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                            <td><?= htmlspecialchars($a['e_prenom'].' '.$a['e_nom']) ?></td>
                            <td><?= htmlspecialchars($a['numero_inscription']) ?></td>
                            <td><?= htmlspecialchars($a['m_nom'] ?? 'Toutes') ?></td>
                            <td><?= $a['justifiee'] ? '<span class="badge badge-success">Oui</span>' : '<span class="badge badge-danger">Non</span>' ?></td>
                            <td><?= htmlspecialchars($a['motif'] ?? '—') ?></td>
                            <td>
                                <a href="absences.php?supprimer=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Supprimer cette absence ?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>function toggleForm(id){const el=document.getElementById(id);el.style.display=el.style.display==='none'?'block':'none';}</script>
</body>
</html>
