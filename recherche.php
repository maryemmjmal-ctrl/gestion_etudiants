<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$q = trim($_GET['q'] ?? '');
$etudiant = null;
$notes = [];
$absences = [];

if ($q) {
    $stmt = $pdo->prepare("SELECT e.*, f.nom as filiere FROM etudiants e
                           LEFT JOIN filieres f ON f.id=e.filiere_id
                           WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.numero_inscription LIKE ?
                           LIMIT 1");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    $etudiant = $stmt->fetch();

    if ($etudiant) {
        $notes = $pdo->prepare("SELECT n.*, m.nom as m_nom, m.coefficient FROM notes n
                                JOIN matieres m ON m.id=n.matiere_id
                                WHERE n.etudiant_id=? ORDER BY n.semestre, m.nom");
        $notes->execute([$etudiant['id']]);
        $notes = $notes->fetchAll();

        $absences = $pdo->prepare("SELECT a.*, m.nom as m_nom FROM absences a
                                   LEFT JOIN matieres m ON m.id=a.matiere_id
                                   WHERE a.etudiant_id=? ORDER BY a.date_absence DESC");
        $absences->execute([$etudiant['id']]);
        $absences = $absences->fetchAll();

        // Moyenne générale
        $moy = $pdo->prepare("SELECT ROUND(AVG(valeur),2) FROM notes WHERE etudiant_id=?");
        $moy->execute([$etudiant['id']]);
        $moyenne = $moy->fetchColumn();

        $nbAbs = count($absences);
        $nbAbsInj = count(array_filter($absences, fn($a) => !$a['justifiee']));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Recherche</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Rechercher un étudiant</h1><div class="breadcrumb">Tableau de bord / Recherche</div></div>
    </div>
    <div class="content">

        <!-- Barre de recherche -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="search-bar">
                    <input type="text" name="q" class="form-control" style="font-size:15px;padding:12px;"
                           placeholder="Rechercher par nom, prénom ou numéro d'inscription..." value="<?= htmlspecialchars($q) ?>" autofocus>
                    <button type="submit" class="btn btn-primary" style="padding:12px 24px;"><i class="fas fa-search"></i> Rechercher</button>
                </form>
            </div>
        </div>

        <?php if ($q && !$etudiant): ?>
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Aucun étudiant trouvé pour "<strong><?= htmlspecialchars($q) ?></strong>".</div>
        <?php endif; ?>

        <?php if ($etudiant): ?>

        <!-- Fiche étudiant -->
        <div class="card">
            <div class="card-header">
                <h3>🎓 Dossier de <?= htmlspecialchars($etudiant['prenom'].' '.$etudiant['nom']) ?></h3>
                <?php if (isAdmin()): ?>
                <a href="etudiants.php?action=modifier&id=<?= $etudiant['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Modifier</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div>
                        <p style="font-size:12px;color:#888;">N° Inscription</p>
                        <p style="font-weight:700;color:var(--navy);"><?= htmlspecialchars($etudiant['numero_inscription']) ?></p>
                    </div>
                    <div>
                        <p style="font-size:12px;color:#888;">Filière</p>
                        <p><span class="badge badge-info"><?= htmlspecialchars($etudiant['filiere'] ?? '—') ?></span></p>
                    </div>
                    <div>
                        <p style="font-size:12px;color:#888;">Niveau</p>
                        <p style="font-weight:700;"><?= htmlspecialchars($etudiant['niveau']) ?></p>
                    </div>
                    <div>
                        <p style="font-size:12px;color:#888;">Email</p>
                        <p><?= htmlspecialchars($etudiant['email'] ?? '—') ?></p>
                    </div>
                    <div>
                        <p style="font-size:12px;color:#888;">Date de naissance</p>
                        <p><?= $etudiant['date_naissance'] ? date('d/m/Y', strtotime($etudiant['date_naissance'])) : '—' ?></p>
                    </div>
                    <div>
                        <p style="font-size:12px;color:#888;">Téléphone</p>
                        <p><?= htmlspecialchars($etudiant['telephone'] ?? '—') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats rapides -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="value"><?= $moyenne ?? '—' ?>/20</div>
                    <div class="label">Moyenne générale</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-info">
                    <div class="value"><?= count($notes) ?></div>
                    <div class="label">Notes enregistrées</div>
                </div>
            </div>
            <div class="stat-card <?= $nbAbsInj >= SEUIL_ABSENCES ? 'red' : 'orange' ?>">
                <div class="stat-icon">📅</div>
                <div class="stat-info">
                    <div class="value"><?= $nbAbs ?></div>
                    <div class="label">Absences totales (<?= $nbAbsInj ?> injustifiées)</div>
                </div>
            </div>
            <div class="stat-card <?= isset($moyenne) && $moyenne >= 10 ? 'green' : 'red' ?>">
                <div class="stat-icon"><?= isset($moyenne) && $moyenne >= 10 ? '✅' : '❌' ?></div>
                <div class="stat-info">
                    <div class="value"><?= isset($moyenne) ? ($moyenne >= 10 ? 'Admis' : 'En échec') : '—' ?></div>
                    <div class="label">Statut académique</div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($notes)): ?>
        <div class="card">
            <div class="card-header"><h3>📚 Notes de <?= htmlspecialchars($etudiant['prenom']) ?></h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Matière</th><th>Coeff.</th><th>Semestre</th><th>Note</th><th>Appréciation</th></tr></thead>
                    <tbody>
                    <?php foreach ($notes as $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['m_nom']) ?></td>
                        <td><?= $n['coefficient'] ?></td>
                        <td><span class="badge badge-info"><?= $n['semestre'] ?></span></td>
                        <td><span class="<?= $n['valeur']>=14?'note-high':($n['valeur']>=10?'note-medium':'note-low') ?>"><?= $n['valeur'] ?>/20</span></td>
                        <td><?= $n['valeur']>=14?'✅ Bien':($n['valeur']>=10?'🟡 Passable':'❌ Insuffisant') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Absences -->
        <?php if (!empty($absences)): ?>
        <div class="card">
            <div class="card-header"><h3>📅 Absences de <?= htmlspecialchars($etudiant['prenom']) ?></h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Date</th><th>Matière</th><th>Justifiée</th><th>Motif</th></tr></thead>
                    <tbody>
                    <?php foreach ($absences as $a): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                        <td><?= htmlspecialchars($a['m_nom'] ?? 'Toutes') ?></td>
                        <td><?= $a['justifiee']?'<span class="badge badge-success">Oui</span>':'<span class="badge badge-danger">Non</span>' ?></td>
                        <td><?= htmlspecialchars($a['motif'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
</body>
</html>
