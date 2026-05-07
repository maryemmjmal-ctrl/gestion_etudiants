<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$action = $_GET['action'] ?? 'liste';
$id     = intval($_GET['id'] ?? 0);
$flash  = getFlash();

// Filieres pour les selects
$filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();

// ── TRAITEMENT FORMULAIRES ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'numero_inscription' => trim($_POST['numero_inscription'] ?? ''),
        'nom'                => trim($_POST['nom'] ?? ''),
        'prenom'             => trim($_POST['prenom'] ?? ''),
        'date_naissance'     => $_POST['date_naissance'] ?? null,
        'email'              => trim($_POST['email'] ?? ''),
        'telephone'          => trim($_POST['telephone'] ?? ''),
        'adresse'            => trim($_POST['adresse'] ?? ''),
        'filiere_id'         => intval($_POST['filiere_id'] ?? 0) ?: null,
        'niveau'             => $_POST['niveau'] ?? 'L1',
    ];

    if ($_POST['action_form'] === 'ajouter') {
        try {
            $stmt = $pdo->prepare("INSERT INTO etudiants (numero_inscription,nom,prenom,date_naissance,email,telephone,adresse,filiere_id,niveau)
                                   VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute(array_values($data));
            flash("Étudiant ajouté avec succès !");
        } catch (PDOException $e) {
            flash("Erreur : numéro d'inscription déjà utilisé.", 'danger');
        }
        header('Location: etudiants.php');
        exit;
    }

    if ($_POST['action_form'] === 'modifier') {
        $stmt = $pdo->prepare("UPDATE etudiants SET numero_inscription=?,nom=?,prenom=?,date_naissance=?,
                                email=?,telephone=?,adresse=?,filiere_id=?,niveau=? WHERE id=?");
        $stmt->execute([...array_values($data), intval($_POST['etudiant_id'])]);
        flash("Étudiant modifié avec succès !");
        header('Location: etudiants.php');
        exit;
    }
}

if ($action === 'supprimer' && $id) {
    $pdo->prepare("DELETE FROM etudiants WHERE id=?")->execute([$id]);
    flash("Étudiant supprimé.", 'warning');
    header('Location: etudiants.php');
    exit;
}

// ── DONNÉES ───────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$sql    = "SELECT e.*, f.nom as filiere FROM etudiants e LEFT JOIN filieres f ON f.id=e.filiere_id";
$params = [];
if ($search) {
    $sql   .= " WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.numero_inscription LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY e.nom, e.prenom";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$etudiants = $stmt->fetchAll();

$etudiant = null;
if ($action === 'modifier' && $id) {
    $etudiant = $pdo->prepare("SELECT * FROM etudiants WHERE id=?");
    $etudiant->execute([$id]);
    $etudiant = $etudiant->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <div>
            <h1>Gestion des Étudiants</h1>
            <div class="breadcrumb">Tableau de bord / Étudiants</div>
        </div>
        <button class="btn btn-primary" onclick="toggleForm('form-ajout')">
            <i class="fas fa-plus"></i> Ajouter un étudiant
        </button>
    </div>

    <div class="content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Formulaire Ajout -->
        <div class="card" id="form-ajout" style="display:<?= $action === 'ajouter' ? 'block' : 'none' ?>;">
            <div class="card-header">
                <h3><i class="fas fa-user-plus"></i> Ajouter un étudiant</h3>
                <button class="btn btn-secondary btn-sm" onclick="toggleForm('form-ajout')">✕ Fermer</button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_form" value="ajouter">
                    <div class="form-row">
                        <div class="form-group">
                            <label>N° Inscription *</label>
                            <input type="text" name="numero_inscription" class="form-control" required placeholder="2024BC001">
                        </div>
                        <div class="form-group">
                            <label>Niveau *</label>
                            <select name="niveau" class="form-control" required>
                                <?php foreach (['L1','L2','L3','M1','M2'] as $n): ?>
                                    <option><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Filière</label>
                            <select name="filiere_id" class="form-control">
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($filieres as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Adresse</label>
                        <input type="text" name="adresse" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>

        <!-- Formulaire Modifier -->
        <?php if ($action === 'modifier' && $etudiant): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Modifier l'étudiant</h3>
                <a href="etudiants.php" class="btn btn-secondary btn-sm">✕ Annuler</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_form" value="modifier">
                    <input type="hidden" name="etudiant_id" value="<?= $etudiant['id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>N° Inscription *</label>
                            <input type="text" name="numero_inscription" class="form-control" value="<?= htmlspecialchars($etudiant['numero_inscription']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Niveau *</label>
                            <select name="niveau" class="form-control" required>
                                <?php foreach (['L1','L2','L3','M1','M2'] as $n): ?>
                                    <option <?= $etudiant['niveau'] === $n ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($etudiant['nom']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($etudiant['prenom']) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control" value="<?= $etudiant['date_naissance'] ?>">
                        </div>
                        <div class="form-group">
                            <label>Filière</label>
                            <select name="filiere_id" class="form-control">
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($filieres as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= $etudiant['filiere_id'] == $f['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($etudiant['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($etudiant['telephone']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Adresse</label>
                        <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($etudiant['adresse']) ?>">
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Liste étudiants -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Liste des étudiants (<?= count($etudiants) ?>)</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="search-bar">
                    <input type="text" name="q" class="form-control" placeholder="Rechercher par nom, prénom ou N° inscription..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    <?php if ($search): ?>
                        <a href="etudiants.php" class="btn btn-secondary">Effacer</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>N° Inscription</th><th>Nom</th><th>Prénom</th>
                            <th>Filière</th><th>Niveau</th><th>Email</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($etudiants)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#888; padding:30px;">Aucun étudiant trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($etudiants as $e): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($e['numero_inscription']) ?></strong></td>
                            <td><?= htmlspecialchars($e['nom']) ?></td>
                            <td><?= htmlspecialchars($e['prenom']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($e['filiere'] ?? '—') ?></span></td>
                            <td><?= htmlspecialchars($e['niveau']) ?></td>
                            <td><?= htmlspecialchars($e['email'] ?? '—') ?></td>
                            <td style="white-space:nowrap;">
                                <a href="etudiants.php?action=modifier&id=<?= $e['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="etudiants.php?action=supprimer&id=<?= $e['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Supprimer cet étudiant et toutes ses données ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
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

<script>
function toggleForm(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
</body>
</html>
