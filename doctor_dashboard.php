
<?php
require 'config.php';

// Sécurité : accès seulement aux médecins connectés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$message = "";
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

// Récupérer les rendez-vous à venir (du plus récent au moins récent)
try {
    $stmt = $bd->prepare("SELECT a.id, a.date, a.time, u.name AS patient_name, a.status 
                         FROM appointments a 
                         JOIN users u ON a.patient_id = u.id 
                         WHERE a.doctor_id = ? AND a.date >= CURDATE() 
                         ORDER BY a.date DESC, a.time DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'historique des rendez-vous
    $stmt = $bd->prepare("SELECT a.id, a.date, a.time, u.name AS patient_name, a.status 
                         FROM appointments a 
                         JOIN users u ON a.patient_id = u.id 
                         WHERE a.doctor_id = ? AND a.date < CURDATE() 
                         ORDER BY a.date DESC, a.time DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $past_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Accepter un rendez-vous
    if (isset($_POST['accept_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $stmt = $bd->prepare("UPDATE appointments SET status = 'accepte' WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$appointment_id, $_SESSION['user_id']]);
        $message = "Rendez-vous accepté.";
        header("Location: ?tab=upcoming");
        exit();
    }

    // Refuser un rendez-vous
    if (isset($_POST['refuse_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $stmt = $bd->prepare("UPDATE appointments SET status = 'annule' WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$appointment_id, $_SESSION['user_id']]);
        $message = "Rendez-vous annulé.";
        header("Location: ?tab=upcoming");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord médecin</title>
    <link rel="stylesheet" href="doc.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Barre latérale -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-user-md"></i> Espace Médecin</h2>
            </div>
            <nav>
                <ul>
                    <li>
                        <a href="?tab=upcoming" class="<?= $active_tab === 'upcoming' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-check"></i> Rendez-vous à venir
                        </a>
                    </li>
                    <li>
                        <a href="?tab=history" class="<?= $active_tab === 'history' ? 'active' : '' ?>">
                            <i class="fas fa-history"></i> Historique
                        </a>
                    </li>
                    <li>
                        <a href="profile.php">
                            <i class="fas fa-user"></i> Mon Profil
                        </a>
                    </li>
                    <li>
                        <a href="deconn.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <header>
                <h1>Bonjour, Dr. <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?> 👋</h1>
                <p>Voici vos rendez-vous médicaux.</p>
            </header>

            <?php if ($message): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>

            <!-- Rendez-vous à venir -->
            <div class="tab-content <?= $active_tab === 'upcoming' ? 'active' : '' ?>" id="upcoming-tab">
                <div class="card">
                    <h2><i class="fas fa-calendar-check"></i> Rendez-vous à venir</h2>
                    <?php if (empty($upcoming_appointments)): ?>
                        <p style="padding: 1rem; color: #6c757d;">Aucun rendez-vous à venir.</p>
                    <?php else: ?>
                        <ul class="appointment-list">
                            <?php foreach ($upcoming_appointments as $appt): ?>
                                <li>
                                    <strong><?= date('d/m/Y', strtotime($appt['date'])) ?> à <?= substr($appt['time'], 0, 5) ?></strong>
                                    <p>Patient : <?= htmlspecialchars($appt['patient_name']) ?></p>
                                    <span class="status-badge status-<?= 
                                        $appt['status'] === 'accepte' ? 'accepted' : 
                                        ($appt['status'] === 'annule' ? 'cancelled' : 'pending') 
                                    ?>">
                                        <?= 
                                            $appt['status'] === 'accepte' ? 'Confirmé' : 
                                            ($appt['status'] === 'annule' ? 'Annulé' : 'En attente')
                                        ?>
                                    </span>
                                    <?php if ($appt['status'] === 'en_attente'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                            <button type="submit" name="accept_appointment" class="btn btn-success"><i class="fas fa-check"></i> Accepter</button>
                                            <button type="submit" name="refuse_appointment" class="btn btn-danger"><i class="fas fa-times"></i> Refuser</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historique -->
            <div class="tab-content <?= $active_tab === 'history' ? 'active' : '' ?>" id="history-tab">
                <div class="card">
                    <h2><i class="fas fa-history"></i> Historique des rendez-vous</h2>
                    <?php if (empty($past_appointments)): ?>
                        <p style="padding: 1rem; color: #6c757d;">Aucun rendez-vous passé.</p>
                    <?php else: ?>
                        <ul class="appointment-list">
                            <?php foreach ($past_appointments as $appt): ?>
                                <li>
                                    <strong><?= date('d/m/Y', strtotime($appt['date'])) ?> à <?= substr($appt['time'], 0, 5) ?></strong>
                                    <p>Patient : <?= htmlspecialchars($appt['patient_name']) ?></p>
                                    <span class="status-badge">
                                        Terminé
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>