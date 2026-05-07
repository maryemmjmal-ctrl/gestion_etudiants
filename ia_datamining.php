<?php
// ── Module IA & Data Mining ────────────────────────────────────────────────
// Prédiction du risque d'échec + analyse des tendances
// Réalisé par : Maryem Jmal | ESSAT 2024/2025

/**
 * Calcule le score de risque d'échec d'un étudiant (0-100)
 * Basé sur : moyenne, absences injustifiées, notes < 10
 */
function calculerRisque(float $moyenne = null, int $nbAbsInj = 0, int $nbNotesEchec = 0, int $nbNotes = 0): array {
    $score = 0;

    // Facteur 1 : Moyenne (poids 50%)
    if ($moyenne !== null) {
        if ($moyenne < 8)       $score += 50;
        elseif ($moyenne < 10)  $score += 35;
        elseif ($moyenne < 12)  $score += 20;
        elseif ($moyenne < 14)  $score += 10;
    } else {
        $score += 15; // Pas encore de notes = risque modéré
    }

    // Facteur 2 : Absences injustifiées (poids 30%)
    if ($nbAbsInj >= 5)      $score += 30;
    elseif ($nbAbsInj >= 3)  $score += 20;
    elseif ($nbAbsInj >= 1)  $score += 10;

    // Facteur 3 : Proportion de notes insuffisantes (poids 20%)
    if ($nbNotes > 0) {
        $tauxEchec = $nbNotesEchec / $nbNotes;
        if ($tauxEchec >= 0.7)      $score += 20;
        elseif ($tauxEchec >= 0.5)  $score += 14;
        elseif ($tauxEchec >= 0.3)  $score += 8;
    }

    // Niveau de risque
    if ($score >= 60)      { $niveau = 'eleve';  $label = 'Risque élevé';  $color = '#e74c3c'; $icon = '🔴'; }
    elseif ($score >= 30)  { $niveau = 'moyen';  $label = 'Risque moyen';  $color = '#f39c12'; $icon = '🟠'; }
    else                   { $niveau = 'faible'; $label = 'Risque faible'; $color = '#27ae60'; $icon = '🟢'; }

    return ['score' => $score, 'niveau' => $niveau, 'label' => $label, 'color' => $color, 'icon' => $icon];
}

/**
 * Récupère les prédictions IA pour tous les étudiants
 */
function getPredictions(PDO $pdo): array {
    $etudiants = $pdo->query("
        SELECT e.id, e.nom, e.prenom, e.numero_inscription, e.niveau, f.nom as filiere,
               ROUND(AVG(n.valeur), 2) as moyenne,
               COUNT(n.id) as nb_notes,
               SUM(CASE WHEN n.valeur < 10 THEN 1 ELSE 0 END) as nb_echec
        FROM etudiants e
        LEFT JOIN filieres f ON f.id = e.filiere_id
        LEFT JOIN notes n ON n.etudiant_id = e.id
        GROUP BY e.id
        ORDER BY e.nom
    ")->fetchAll();

    $absences = $pdo->query("
        SELECT etudiant_id, COUNT(*) as nb
        FROM absences WHERE justifiee = 0
        GROUP BY etudiant_id
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($etudiants as &$e) {
        $nbAbsInj = $absences[$e['id']] ?? 0;
        $risque   = calculerRisque($e['moyenne'], $nbAbsInj, (int)$e['nb_echec'], (int)$e['nb_notes']);
        $e['risque']       = $risque;
        $e['nb_abs_inj']   = $nbAbsInj;
    }

    return $etudiants;
}

/**
 * Data Mining : statistiques par matière
 */
function getStatsMatieres(PDO $pdo): array {
    return $pdo->query("
        SELECT m.nom, m.coefficient,
               COUNT(n.id) as nb_notes,
               ROUND(AVG(n.valeur), 2) as moyenne,
               ROUND(MIN(n.valeur), 2) as min_note,
               ROUND(MAX(n.valeur), 2) as max_note,
               SUM(CASE WHEN n.valeur >= 10 THEN 1 ELSE 0 END) as nb_admis,
               SUM(CASE WHEN n.valeur < 10 THEN 1 ELSE 0 END) as nb_echec
        FROM matieres m
        LEFT JOIN notes n ON n.matiere_id = m.id
        GROUP BY m.id
        ORDER BY moyenne ASC
    ")->fetchAll();
}

/**
 * Data Mining : distribution des notes (0-5, 5-10, 10-15, 15-20)
 */
function getDistributionNotes(PDO $pdo): array {
    $tranches = [
        ['label' => '0 — 5',   'min' => 0,  'max' => 5],
        ['label' => '5 — 10',  'min' => 5,  'max' => 10],
        ['label' => '10 — 15', 'min' => 10, 'max' => 15],
        ['label' => '15 — 20', 'min' => 15, 'max' => 20.01],
    ];
    foreach ($tranches as &$t) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE valeur >= ? AND valeur < ?");
        $stmt->execute([$t['min'], $t['max']]);
        $t['count'] = (int)$stmt->fetchColumn();
    }
    return $tranches;
}

/**
 * Data Mining : tendance niveau de réussite par filière
 */
function getTendancesParFiliere(PDO $pdo): array {
    return $pdo->query("
        SELECT f.nom as filiere,
               COUNT(DISTINCT e.id) as nb_etudiants,
               ROUND(AVG(n.valeur), 2) as moyenne,
               SUM(CASE WHEN n.valeur >= 10 THEN 1 ELSE 0 END) as nb_admis,
               COUNT(n.id) as nb_total
        FROM filieres f
        LEFT JOIN etudiants e ON e.filiere_id = f.id
        LEFT JOIN notes n ON n.etudiant_id = e.id
        GROUP BY f.id
        ORDER BY moyenne DESC
    ")->fetchAll();
}
