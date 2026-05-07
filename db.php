<?php
// ── Connexion à la base de données ────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_etudiants');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connexion impossible : ' . $e->getMessage()]));
}

// Seuil d'alerte absences
define('SEUIL_ABSENCES', 3);
define('ANNEE_EN_COURS', '2024/2025');
