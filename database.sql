-- ============================================================
-- Base de données : gestion_etudiants
-- Application de Gestion des Étudiants
-- Réalisée par : Maryem Jmal | ESSAT 2024/2025
-- ============================================================

CREATE DATABASE IF NOT EXISTS gestion_etudiants CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_etudiants;

-- Table utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'enseignant') NOT NULL DEFAULT 'enseignant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table filieres
CREATE TABLE filieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    niveau VARCHAR(50) NOT NULL
);

-- Table matieres
CREATE TABLE matieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    coefficient FLOAT NOT NULL DEFAULT 1,
    filiere_id INT,
    semestre ENUM('S1','S2','S3','S4','S5','S6') NOT NULL DEFAULT 'S1',
    FOREIGN KEY (filiere_id) REFERENCES filieres(id) ON DELETE SET NULL
);

-- Table etudiants
CREATE TABLE etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_inscription VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    email VARCHAR(150),
    telephone VARCHAR(20),
    adresse TEXT,
    filiere_id INT,
    niveau VARCHAR(20) NOT NULL DEFAULT 'L1',
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filiere_id) REFERENCES filieres(id) ON DELETE SET NULL
);

-- Table notes
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    matiere_id INT NOT NULL,
    valeur FLOAT NOT NULL CHECK (valeur >= 0 AND valeur <= 20),
    semestre ENUM('S1','S2','S3','S4','S5','S6') NOT NULL,
    annee_universitaire VARCHAR(9) NOT NULL DEFAULT '2024/2025',
    enseignant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
    FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    UNIQUE KEY unique_note (etudiant_id, matiere_id, semestre, annee_universitaire)
);

-- Table absences
CREATE TABLE absences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    matiere_id INT,
    date_absence DATE NOT NULL,
    justifiee TINYINT(1) DEFAULT 0,
    motif TEXT,
    enseignant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE SET NULL,
    FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- ── Données de test ──────────────────────────────────────────────────────────

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'Système', 'admin@essat.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ben Fraj', 'Hajer', 'hajer.benfraj@essat.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant');
-- Mot de passe par défaut : password

INSERT INTO filieres (nom, niveau) VALUES
('Business Computing', 'Licence'),
('Informatique de Gestion', 'Licence'),
('Réseaux et Systèmes', 'Licence');

INSERT INTO matieres (nom, coefficient, filiere_id, semestre) VALUES
('Algorithmes', 3, 1, 'S1'),
('Base de données', 3, 1, 'S1'),
('Développement Web', 2, 1, 'S2'),
('Systèmes d information', 2, 1, 'S2'),
('Mathématiques appliquées', 2, 1, 'S1'),
('Anglais des affaires', 1, 1, 'S1');

INSERT INTO etudiants (numero_inscription, nom, prenom, date_naissance, email, filiere_id, niveau) VALUES
('2024BC001', 'Jmal', 'Maryem', '2003-05-15', 'maryem.jmal@essat.tn', 1, 'L2'),
('2024BC002', 'Ben Ali', 'Ahmed', '2003-08-20', 'ahmed.benali@essat.tn', 1, 'L2'),
('2024BC003', 'Trabelsi', 'Sonia', '2003-03-10', 'sonia.trabelsi@essat.tn', 1, 'L2'),
('2024BC004', 'Oueslati', 'Yassine', '2002-11-25', 'yassine.oueslati@essat.tn', 1, 'L2'),
('2024BC005', 'Hamdi', 'Rania', '2003-07-08', 'rania.hamdi@essat.tn', 1, 'L2');

INSERT INTO notes (etudiant_id, matiere_id, valeur, semestre, enseignant_id) VALUES
(1,1,15.5,'S1',2),(1,2,14,'S1',2),(1,5,12,'S1',2),(1,6,17,'S1',2),
(2,1,11,'S1',2),(2,2,13.5,'S1',2),(2,5,9,'S1',2),(2,6,14,'S1',2),
(3,1,18,'S1',2),(3,2,16,'S1',2),(3,5,15,'S1',2),(3,6,19,'S1',2),
(4,1,8,'S1',2),(4,2,10,'S1',2),(4,5,7,'S1',2),(4,6,11,'S1',2),
(5,1,13,'S1',2),(5,2,12,'S1',2),(5,5,14,'S1',2),(5,6,16,'S1',2);

INSERT INTO absences (etudiant_id, matiere_id, date_absence, justifiee, enseignant_id) VALUES
(2,1,'2024-10-05',0,2),(2,1,'2024-10-12',0,2),(2,2,'2024-10-08',1,2),
(4,1,'2024-10-03',0,2),(4,1,'2024-10-10',0,2),(4,1,'2024-10-17',0,2),
(4,2,'2024-10-06',0,2),(4,5,'2024-10-09',0,2);
