<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $role   = $_POST['role'] ?? 'enseignant';

    if ($nom && $prenom && $email && $mdp) {
        try {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role) VALUES (?,?,?,?,?)")
                ->execute([$nom, $prenom, $email, $hash, $role]);
            flash("Utilisateur créé avec succès !");
        } catch (PDOException $e) {
            flash("Erreur : cet email est déjà utilisé.", 'danger');
        }
        header('Location: utilisateurs.php');
        exit;
    }
}

if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    if ($id !== currentUser()['id']) {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$id]);
        flash("Utilisateur supprimé.", 'warning');
    } else {
        flash("Vous ne pouvez pas supprimer votre propre compte.", 'danger');
    }
    header('Location: utilisateurs.php');
    exit;
}

$utilisateurs = $pdo->query("SELECT * FROM utilisateurs ORDER BY role, nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once '../includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div><h1>Gestion des Utilisateurs</h1><div class="breadcrumb">Tableau de bord / Utilisateurs</div></div>
        <button class="btn btn-primary" onclick="toggleForm('form-user')"><i class="fas fa-plus"></i> Ajouter un utilisateur</button>
    </div>
    <div class="content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="card" id="form-user" style="display:none;">
            <div class="card-header">
                <h3><i class="fas fa-user-plus"></i> Ajouter un utilisateur</h3>
                <button class="btn btn-secondary btn-sm" onclick="toggleForm('form-user')">✕</button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group"><label>Nom *</label><input type="text" name="nom" class="form-control" required></div>
                        <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" class="form-control" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="form-group"><label>Mot de passe *</label><input type="password" name="mot_de_passe" class="form-control" required placeholder="Min. 8 caractères"></div>
                    </div>
                    <div class="form-group">
                        <label>Rôle *</label>
                        <select name="role" class="form-control">
                            <option value="enseignant">Enseignant</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Créer le compte</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="fas fa-users"></i> Liste des utilisateurs (<?= count($utilisateurs) ?>)</h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Créé le</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nom']) ?></td>
                        <td><?= htmlspecialchars($u['prenom']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge <?= $u['role']==='admin'?'badge-admin':'badge-info' ?>"><?= $u['role']==='admin'?'Administrateur':'Enseignant' ?></span></td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['id'] !== currentUser()['id']): ?>
                            <a href="utilisateurs.php?supprimer=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Supprimer cet utilisateur ?')"><i class="fas fa-trash"></i></a>
                            <?php else: ?>
                            <span style="color:#aaa;font-size:12px;">Vous</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>function toggleForm(id){const el=document.getElementById(id);el.style.display=el.style.display==='none'?'block':'none';}</script>
</body>
</html>
