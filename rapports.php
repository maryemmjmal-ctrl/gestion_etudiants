<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

// Stats générales
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM etudiants) as nb_etudiants,
        (SELECT COUNT(*) FROM notes) as nb_notes,
        (SELECT ROUND(AVG(valeur),2) FROM notes) as moy_generale,
        (SELECT COUNT(*) FROM absences WHERE justifiee=0) as nb_abs_inj,
        (SELECT COUNT(*) FROM notes WHERE valeur >= 10) as nb_admis,
        (SELECT COUNT(*) FROM notes WHERE valeur < 10) as nb_echec
")->fetch();

// Résultats par étudiant
$bulletins = $pdo->query("
    SELECT e.id, e.nom, e.prenom, e.numero_inscription, e.niveau, f.nom as filiere,
           ROUND(AVG(n.valeur),2) as moyenne,
           COUNT(n.id) as nb_notes,
           COUNT(a.id) as nb_absences
    FROM etudiants e
    LEFT JOIN filieres f ON f.id=e.filiere_id
    LEFT JOIN notes n ON n.etudiant_id=e.id
    LEFT JOIN absences a ON a.etudiant_id=e.id AND a.justifiee=0
    GROUP BY e.id
    ORDER BY moyenne DESC
")->fetchAll();

// Stats par matière
$parMatiere = $pdo->query("
    SELECT m.nom, ROUND(AVG(n.valeur),2) as moy, COUNT(n.id) as nb,
           ROUND(MIN(n.valeur),2) as min_note, ROUND(MAX(n.valeur),2) as max_note,
           SUM(CASE WHEN n.valeur >= 10 THEN 1 ELSE 0 END) as admis
    FROM notes n JOIN matieres m ON m.id=n.matiere_id
    GROUP BY m.id ORDER BY moy DESC
")->fetchAll();

$filtre_id = intval($_GET['bulletin'] ?? 0);
$bulletinEtudiant = null;
if ($filtre_id) {
    $bulletinEtudiant = $pdo->prepare("SELECT e.*, f.nom as filiere FROM etudiants e LEFT JOIN filieres f ON f.id=e.filiere_id WHERE e.id=?");
    $bulletinEtudiant->execute([$filtre_id]);
    $bulletinEtudiant = $bulletinEtudiant->fetch();
    if ($bulletinEtudiant) {
        $bNotes = $pdo->prepare("SELECT n.*, m.nom as m_nom, m.coefficient, m.semestre as m_semestre FROM notes n JOIN matieres m ON m.id=n.matiere_id WHERE n.etudiant_id=? ORDER BY n.semestre, m.nom");
        $bNotes->execute([$filtre_id]);
        $bNotes = $bNotes->fetchAll();
        $bAbsences = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=?");
        $bAbsences->execute([$filtre_id]);
        $bNbAbs = $bAbsences->fetchColumn();
        $bMoy = count($bNotes) ? round(array_sum(array_column($bNotes,'valeur')) / count($bNotes), 2) : null;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Rapports & Statistiques</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .sidebar, .topbar, .no-print { display:none!important; }
            .main { margin-left:0!important; }
            .card { box-shadow:none; border:1px solid #ddd; }
        }
        .bulletin { border:2px solid var(--navy); border-radius:10px; padding:24px; }
        .bulletin-header { text-align:center; border-bottom:2px solid var(--navy); margin-bottom:20px; padding-bottom:16px; }
    </style>
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Rapports & Statistiques</h1><div class="breadcrumb">Tableau de bord / Rapports</div></div>
        <button class="btn btn-secondary no-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimer</button>
    </div>
    <div class="content">

        <!-- Stats générales -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">🎓</div><div class="stat-info"><div class="value"><?= $stats['nb_etudiants'] ?></div><div class="label">Étudiants</div></div></div>
            <div class="stat-card green"><div class="stat-icon">📊</div><div class="stat-info"><div class="value"><?= $stats['moy_generale'] ?? '—' ?>/20</div><div class="label">Moyenne générale</div></div></div>
            <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-info"><div class="value"><?= $stats['nb_admis'] ?></div><div class="label">Notes ≥ 10</div></div></div>
            <div class="stat-card red"><div class="stat-icon">❌</div><div class="stat-info"><div class="value"><?= $stats['nb_echec'] ?></div><div class="label">Notes < 10</div></div></div>
        </div>

        <!-- Générateur bulletin -->
        <div class="card no-print">
            <div class="card-header"><h3>📄 Générer un bulletin individuel</h3></div>
            <div class="card-body">
                <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
                    <div class="form-group" style="margin:0;min-width:250px;">
                        <label>Sélectionner un étudiant</label>
                        <select name="bulletin" class="form-control">
                            <option value="">— Choisir —</option>
                            <?php foreach ($bulletins as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $filtre_id==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['nom'].' '.$b['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-alt"></i> Afficher</button>
                    <?php if ($filtre_id): ?>
                        <button onclick="window.print()" class="btn btn-success" type="button"><i class="fas fa-print"></i> Imprimer / PDF</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Bulletin individuel -->
        <?php if ($bulletinEtudiant && isset($bNotes)): ?>
        <div class="card">
            <div class="card-body">
                <div class="bulletin">
                    <div class="bulletin-header">
                        <h2 style="color:var(--navy);">ESSAT — École Supérieure des Sciences Appliquées pour la Technologie</h2>
                        <h3 style="margin:8px 0;">Bulletin de Notes — Année <?= ANNEE_EN_COURS ?></h3>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                        <div><strong>Nom :</strong> <?= htmlspecialchars($bulletinEtudiant['nom']) ?></div>
                        <div><strong>Prénom :</strong> <?= htmlspecialchars($bulletinEtudiant['prenom']) ?></div>
                        <div><strong>N° Inscription :</strong> <?= htmlspecialchars($bulletinEtudiant['numero_inscription']) ?></div>
                        <div><strong>Filière :</strong> <?= htmlspecialchars($bulletinEtudiant['filiere'] ?? '—') ?></div>
                        <div><strong>Niveau :</strong> <?= htmlspecialchars($bulletinEtudiant['niveau']) ?></div>
                        <div><strong>Absences :</strong> <?= $bNbAbs ?> absence(s)</div>
                    </div>
                    <table>
                        <thead><tr><th>Matière</th><th>Coeff.</th><th>Semestre</th><th>Note /20</th><th>Mention</th></tr></thead>
                        <tbody>
                        <?php foreach ($bNotes as $n): ?>
                        <tr>
                            <td><?= htmlspecialchars($n['m_nom']) ?></td>
                            <td><?= $n['coefficient'] ?></td>
                            <td><?= $n['semestre'] ?></td>
                            <td><strong class="<?= $n['valeur']>=14?'note-high':($n['valeur']>=10?'note-medium':'note-low') ?>"><?= $n['valeur'] ?></strong></td>
                            <td><?= $n['valeur']>=16?'Très Bien':($n['valeur']>=14?'Bien':($n['valeur']>=12?'Assez Bien':($n['valeur']>=10?'Passable':'Insuffisant'))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--navy);color:#fff;">
                                <td colspan="3"><strong>Moyenne Générale</strong></td>
                                <td colspan="2"><strong><?= $bMoy ?>/20 — <?= $bMoy>=16?'Très Bien':($bMoy>=14?'Bien':($bMoy>=12?'Assez Bien':($bMoy>=10?'Passable':'Insuffisant'))) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div style="margin-top:30px;display:flex;justify-content:flex-end;">
                        <div style="text-align:center;">
                            <p>Signature de l'encadrante</p>
                            <div style="height:60px;"></div>
                            <p>Mme Hajer Ben Fraj</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Résultats par étudiant -->
        <div class="card">
            <div class="card-header"><h3>📋 Récapitulatif — Tous les étudiants</h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Rang</th><th>Étudiant</th><th>N° Inscription</th><th>Filière</th><th>Niveau</th><th>Moyenne</th><th>Absences inj.</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach ($bulletins as $i => $b): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($b['prenom'].' '.$b['nom']) ?></td>
                        <td><?= htmlspecialchars($b['numero_inscription']) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($b['filiere'] ?? '—') ?></span></td>
                        <td><?= htmlspecialchars($b['niveau']) ?></td>
                        <td><span class="<?= $b['moyenne']>=14?'note-high':($b['moyenne']>=10?'note-medium':'note-low') ?>"><?= $b['moyenne'] ?? '—' ?>/20</span></td>
                        <td><?= $b['nb_absences'] > 0 ? '<span class="badge badge-danger">'.$b['nb_absences'].'</span>' : '<span class="badge badge-success">0</span>' ?></td>
                        <td><?= $b['moyenne'] >= 10 ? '<span class="badge badge-success">Admis</span>' : '<span class="badge badge-danger">Ajourné</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stats par matière -->
        <div class="card">
            <div class="card-header"><h3>📚 Statistiques par matière</h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Matière</th><th>Moyenne</th><th>Note min</th><th>Note max</th><th>Nb notes</th><th>Taux de réussite</th></tr></thead>
                    <tbody>
                    <?php foreach ($parMatiere as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['nom']) ?></td>
                        <td><span class="<?= $m['moy']>=14?'note-high':($m['moy']>=10?'note-medium':'note-low') ?>"><?= $m['moy'] ?>/20</span></td>
                        <td class="note-low"><?= $m['min_note'] ?></td>
                        <td class="note-high"><?= $m['max_note'] ?></td>
                        <td><?= $m['nb'] ?></td>
                        <td>
                            <?php $taux = $m['nb'] > 0 ? round($m['admis'] / $m['nb'] * 100) : 0; ?>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;background:#eee;border-radius:10px;height:8px;">
                                    <div style="width:<?= $taux ?>%;background:<?= $taux>=50?'#27ae60':'#e74c3c' ?>;height:8px;border-radius:10px;"></div>
                                </div>
                                <span style="font-size:12px;font-weight:700;"><?= $taux ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
