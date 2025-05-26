<?php
require 'config.php';

// Sécurité : accès seulement aux patients connectés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

try {
    // Récupérer les rendez-vous du patient avec le nom du médecin et le statut
    $stmt = $bd->prepare("
        SELECT a.date, a.time, a.status, u.name AS doctor_name
        FROM appointments a
        JOIN users u ON a.doctor_id = u.id
        WHERE a.patient_id = ?
        ORDER BY a.date DESC, a.time ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Patient</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #00796b;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .status-en_attente {
            color: orange;
            font-weight: bold;
        }
        .status-accepte {
            color: green;
            font-weight: bold;
        }
        .status-annule {
            color: red;
            font-weight: bold;
        }
        .status-refuse {
            color: gray;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Bienvenue <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h1>
    <h2>Mes rendez-vous</h2>

    <?php if (empty($appointments)): ?>
        <p>Aucun rendez-vous trouvé.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Médecin</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?= htmlspecialchars($appt['date']) ?></td>
                        <td><?= htmlspecialchars($appt['time']) ?></td>
                        <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                        <td class="status-<?= htmlspecialchars($appt['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($appt['status']))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
