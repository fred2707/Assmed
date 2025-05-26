<?php
// En-tête JSON
header('Content-Type: application/json');

// Connexion à la base de données
require_once 'config.php'; // Assure-toi que $bd est bien défini dans config.php (PDO)

// Récupération des données envoyées
$data = json_decode(file_get_contents("php://input"), true);

$specialty = $data['specialty'] ?? null;
$date = $data['date'] ?? null;
$time = $data['time'] ?? null;

if (!$specialty || !$date || !$time) {
    echo json_encode(["error" => "Paramètres manquants (specialty, date ou time)."]);
    exit;
}

try {
    // Requête pour trouver les docteurs disponibles
    $stmt = $bd->prepare("
        SELECT u.id, u.name, u.specialty
        FROM users u
        WHERE u.role = 'doctor' AND u.specialty = ?
        AND NOT EXISTS (
            SELECT 1 FROM appointments a
            WHERE a.doctor_id = u.id
              AND a.date = ?
              AND a.time = ?
              AND a.status IN ('en_attente', 'accepte')
        )
    ");
    $stmt->execute([$specialty, $date, $time]);
    $available_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($available_doctors)) {
        echo json_encode([
            "available" => false,
            "message" => "Aucun docteur en $specialty n'est disponible le $date à $time."
        ]);
    } else {
        echo json_encode([
            "available" => true,
            "doctors" => $available_doctors
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "error" => "Erreur base de données : " . $e->getMessage()
    ]);
}
