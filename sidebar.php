<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$u = currentUser();
$role = $u['role'];
$initiales = strtoupper(substr($u['prenom'],0,1) . substr($u['nom'],0,1));

$base = $role === 'admin' ? '../admin/' : '../enseignant/';
$cur  = basename($_SERVER['PHP_SELF']);
function active($page) { global $cur; return $cur === $page ? 'active' : ''; }
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>🎓 Gestion des<br>Étudiants</h2>
        <small>ESSAT — 2024/2025</small>
    </div>

    <div class="sidebar-user">
        <div class="avatar"><?= $initiales ?></div>
        <div class="info">
            <div class="name"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
            <div class="role"><?= $role === 'admin' ? 'Administrateur' : 'Enseignant' ?></div>
        </div>
    </div>

    <nav>
        <div class="nav-section">Principal</div>
        <a href="<?= $base ?>dashboard.php" class="nav-link <?= active('dashboard.php') ?>">
            <i class="fas fa-tachometer-alt"></i> Tableau de bord
        </a>

        <?php if ($role === 'admin'): ?>
        <div class="nav-section">Gestion</div>
        <a href="<?= $base ?>etudiants.php" class="nav-link <?= active('etudiants.php') ?>">
            <i class="fas fa-user-graduate"></i> Étudiants
        </a>
        <a href="<?= $base ?>notes.php" class="nav-link <?= active('notes.php') ?>">
            <i class="fas fa-star"></i> Notes
        </a>
        <a href="<?= $base ?>absences.php" class="nav-link <?= active('absences.php') ?>">
            <i class="fas fa-calendar-times"></i> Absences
        </a>
        <a href="<?= $base ?>recherche.php" class="nav-link <?= active('recherche.php') ?>">
            <i class="fas fa-search"></i> Recherche
        </a>
        <div class="nav-section">Rapports</div>
        <a href="<?= $base ?>rapports.php" class="nav-link <?= active('rapports.php') ?>">
            <i class="fas fa-chart-bar"></i> Rapports & Stats
        </a>
        <a href="<?= $base ?>ia_datamining.php" class="nav-link <?= active('ia_datamining.php') ?>"><i class="fas fa-robot"></i> IA & Data Mining</a>
        <div class="nav-section">Administration</div>
        <a href="<?= $base ?>utilisateurs.php" class="nav-link <?= active('utilisateurs.php') ?>">
            <i class="fas fa-users-cog"></i> Utilisateurs
        </a>
        <?php else: ?>
        <div class="nav-section">Mes actions</div>
        <a href="<?= $base ?>notes.php" class="nav-link <?= active('notes.php') ?>">
            <i class="fas fa-star"></i> Saisir notes
        </a>
        <a href="<?= $base ?>absences.php" class="nav-link <?= active('absences.php') ?>">
            <i class="fas fa-calendar-times"></i> Saisir absences
        </a>
        <a href="<?= $base ?>recherche.php" class="nav-link <?= active('recherche.php') ?>">
            <i class="fas fa-search"></i> Recherche
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="nav-link" style="color:rgba(255,100,100,0.85);">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>
