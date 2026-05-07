<?php
// enseignant/dashboard.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
if (isAdmin()) { header('Location: ../admin/dashboard.php'); exit; }

$u = currentUser();
$nbEtudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
$mesNotes    = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE enseignant_id=?");
$mesNotes->execute([$u['id']]);
$nbNotes = $mesNotes->fetchColumn();

$mesAbsences = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE enseignant_id=?");
$mesAbsences->execute([$u['id']]);
$nbAbs = $mesAbsences->fetchColumn();

$alertes = $pdo->query("
    SELECT e.nom, e.prenom, COUNT(a.id) as nb
    FROM absences a JOIN etudiants e ON e.id=a.etudiant_id
    WHERE a.justifiee=0
    GROUP BY e.id HAVING nb >= " . SEUIL_ABSENCES . " ORDER BY nb DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Tableau de bord — Enseignant</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Bonjour, <?= htmlspecialchars($u['prenom']) ?> 👋</h1><div class="breadcrumb">Tableau de bord enseignant</div></div>
        <div style="font-size:13px;color:#888;"><?= date('d/m/Y') ?></div>
    </div>
    <div class="content">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">🎓</div><div class="stat-info"><div class="value"><?= $nbEtudiants ?></div><div class="label">Étudiants inscrits</div></div></div>
            <div class="stat-card green"><div class="stat-icon">📝</div><div class="stat-info"><div class="value"><?= $nbNotes ?></div><div class="label">Notes saisies par moi</div></div></div>
            <div class="stat-card orange"><div class="stat-icon">📅</div><div class="stat-info"><div class="value"><?= $nbAbs ?></div><div class="label">Absences saisies par moi</div></div></div>
            <div class="stat-card red"><div class="stat-icon">⚠️</div><div class="stat-info"><div class="value"><?= count($alertes) ?></div><div class="label">Alertes absences</div></div></div>
        </div>

        <?php if (!empty($alertes)): ?>
        <div class="card">
            <div class="card-header"><h3>⚠️ Alertes — Absences excessives</h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Étudiant</th><th>Absences injustifiées</th></tr></thead>
                    <tbody>
                    <?php foreach ($alertes as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></td>
                            <td><span class="badge badge-danger"><?= $a['nb'] ?> absences</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <a href="notes.php" class="btn btn-primary" style="justify-content:center;padding:18px;font-size:15px;">
                    <i class="fas fa-star"></i> Saisir des notes
                </a>
                <a href="absences.php" class="btn btn-warning" style="justify-content:center;padding:18px;font-size:15px;">
                    <i class="fas fa-calendar-times"></i> Saisir des absences
                </a>
                <a href="recherche.php" class="btn btn-secondary" style="justify-content:center;padding:18px;font-size:15px;">
                    <i class="fas fa-search"></i> Rechercher un étudiant
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
