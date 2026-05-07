<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$flash = getFlash();
$etudiants = $pdo->query("SELECT e.*, f.nom as filiere FROM etudiants e LEFT JOIN filieres f ON f.id=e.filiere_id ORDER BY e.nom")->fetchAll();
$matieres  = $pdo->query("SELECT m.*, f.nom as filiere FROM matieres m LEFT JOIN filieres f ON f.id=m.filiere_id ORDER BY m.nom")->fetchAll();

// Sauvegarde note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etudiant_id = intval($_POST['etudiant_id']);
    $matiere_id  = intval($_POST['matiere_id']);
    $valeur      = floatval(str_replace(',', '.', $_POST['valeur']));
    $semestre    = $_POST['semestre'];

    if ($valeur < 0 || $valeur > 20) {
        flash("La note doit être entre 0 et 20.", 'danger');
    } else {
        $stmt = $pdo->prepare("INSERT INTO notes (etudiant_id, matiere_id, valeur, semestre, annee_universitaire, enseignant_id)
                               VALUES (?,?,?,?,?,?)
                               ON DUPLICATE KEY UPDATE valeur=?, enseignant_id=?");
        $stmt->execute([$etudiant_id, $matiere_id, $valeur, $semestre, ANNEE_EN_COURS, currentUser()['id'], $valeur, currentUser()['id']]);
        flash("Note enregistrée avec succès !");
    }
    header('Location: notes.php');
    exit;
}

// Filtres
$filtre_etudiant = intval($_GET['etudiant'] ?? 0);
$filtre_semestre = $_GET['semestre'] ?? '';
$sql = "SELECT n.*, e.nom as e_nom, e.prenom as e_prenom, m.nom as m_nom, m.coefficient
        FROM notes n
        JOIN etudiants e ON e.id = n.etudiant_id
        JOIN matieres m ON m.id = n.matiere_id
        WHERE 1=1";
$params = [];
if ($filtre_etudiant) { $sql .= " AND n.etudiant_id=?"; $params[] = $filtre_etudiant; }
if ($filtre_semestre) { $sql .= " AND n.semestre=?";    $params[] = $filtre_semestre; }
$sql .= " ORDER BY e.nom, e.prenom, m.nom";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Gestion des Notes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Gestion des Notes</h1><div class="breadcrumb">Tableau de bord / Notes</div></div>
        <button class="btn btn-primary" onclick="toggleForm('form-note')"><i class="fas fa-plus"></i> Saisir une note</button>
    </div>
    <div class="content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Formulaire saisie -->
        <div class="card" id="form-note" style="display:none;">
            <div class="card-header">
                <h3><i class="fas fa-star"></i> Saisir / Modifier une note</h3>
                <button class="btn btn-secondary btn-sm" onclick="toggleForm('form-note')">✕</button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Étudiant *</label>
                            <select name="etudiant_id" class="form-control" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($etudiants as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Matière *</label>
                            <select name="matiere_id" class="form-control" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($matieres as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?> (coeff. <?= $m['coefficient'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Semestre *</label>
                            <select name="semestre" class="form-control" required>
                                <?php foreach (['S1','S2','S3','S4','S5','S6'] as $s): ?>
                                    <option><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="max-width:200px;">
                        <label>Note (0 — 20) *</label>
                        <input type="number" name="valeur" class="form-control" min="0" max="20" step="0.25" required placeholder="ex: 14.5">
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-filter"></i> Filtrer les notes</h3></div>
            <div class="card-body">
                <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                    <div class="form-group" style="margin:0; min-width:200px;">
                        <label>Étudiant</label>
                        <select name="etudiant" class="form-control">
                            <option value="">Tous les étudiants</option>
                            <?php foreach ($etudiants as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $filtre_etudiant == $e['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Semestre</label>
                        <select name="semestre" class="form-control">
                            <option value="">Tous</option>
                            <?php foreach (['S1','S2','S3','S4','S5','S6'] as $s): ?>
                                <option <?= $filtre_semestre === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="notes.php" class="btn btn-secondary">Réinitialiser</a>
                </form>
            </div>
        </div>

        <!-- Tableau notes -->
        <div class="card">
            <div class="card-header"><h3>📋 Notes enregistrées (<?= count($notes) ?>)</h3></div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                <table>
                    <thead><tr><th>Étudiant</th><th>Matière</th><th>Coeff.</th><th>Semestre</th><th>Note</th><th>Appréciation</th></tr></thead>
                    <tbody>
                    <?php if (empty($notes)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#888;">Aucune note enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notes as $n): ?>
                        <tr>
                            <td><?= htmlspecialchars($n['e_prenom'] . ' ' . $n['e_nom']) ?></td>
                            <td><?= htmlspecialchars($n['m_nom']) ?></td>
                            <td><?= $n['coefficient'] ?></td>
                            <td><span class="badge badge-info"><?= $n['semestre'] ?></span></td>
                            <td><span class="<?= $n['valeur'] >= 14 ? 'note-high' : ($n['valeur'] >= 10 ? 'note-medium' : 'note-low') ?>"><?= $n['valeur'] ?>/20</span></td>
                            <td><?= $n['valeur'] >= 14 ? '✅ Bien' : ($n['valeur'] >= 10 ? '🟡 Passable' : '❌ Insuffisant') ?></td>
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
