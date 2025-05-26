<?php
require 'config.php';
// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$message = "";
$available_slots = [];
$doctor_id = null;
$date = null;
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'appointments';

try {
    // Récupération des informations de l'utilisateur
    $stmt = $bd->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Liste des médecins
    $stmt = $bd->query("SELECT * FROM users WHERE role = 'doctor'");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les rendez-vous à venir
    $stmt = $bd->prepare("SELECT a.id, a.date, a.time, u.name AS doctor_name, a.status 
                         FROM appointments a 
                         JOIN users u ON a.doctor_id = u.id 
                         WHERE a.patient_id = ? AND a.date >= CURDATE() 
                         ORDER BY a.date DESC, a.time DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'historique des rendez-vous
    $stmt = $bd->prepare("SELECT a.id, a.date, a.time, u.name AS doctor_name, a.status 
                         FROM appointments a 
                         JOIN users u ON a.doctor_id = u.id 
                         WHERE a.patient_id = ? AND a.date < CURDATE() 
                         ORDER BY a.date DESC, a.time DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $past_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Réinitialiser la sélection du médecin
    if (isset($_POST['change_doctor'])) {
        unset($doctor_id, $date, $available_slots);
    }

    // Sélection du médecin
    if (isset($_POST['doctor_id']) && !isset($_POST['date'])) {
        $doctor_id = $_POST['doctor_id'];
    }

    // Sélection de la date pour afficher les créneaux
    if (isset($_POST['doctor_id']) && isset($_POST['date'])) {
        $doctor_id = $_POST['doctor_id'];
        $date = $_POST['date'];
        $active_tab = 'appointments';

        // Créneaux de 9h à 17h avec intervalle de 30 minutes
        $slots = [];
        for ($hour = 9; $hour <= 17; $hour++) {
            $slots[] = sprintf("%02d:00:00", $hour);
            if ($hour < 17) {
                $slots[] = sprintf("%02d:30:00", $hour);
            }
        }

        // Créneaux déjà pris
        $stmt = $bd->prepare("SELECT time FROM appointments 
                             WHERE doctor_id = ? AND date = ? 
                             AND status IN ('en_attente', 'accepte')");
        $stmt->execute([$doctor_id, $date]);
        $taken_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Créneaux disponibles
        $available_slots = array_diff($slots, $taken_slots);
    }

    // Confirmation de rendez-vous
    if (isset($_POST['confirm_rdv'])) {
        $doctor_id = $_POST['selected_doctor'];
        $slot = $_POST['slot'];
        $date = $_POST['date'];
        $active_tab = 'appointments';

        // Vérification de la disponibilité
        $stmt = $bd->prepare("SELECT COUNT(*) FROM appointments 
                             WHERE doctor_id = ? AND date = ? AND time = ? 
                             AND status IN ('en_attente', 'accepte')");
        $stmt->execute([$doctor_id, $date, $slot]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $bd->prepare("INSERT INTO appointments 
                                (patient_id, doctor_id, date, time, status, created_at) 
                                VALUES (?, ?, ?, ?, 'en_attente', NOW())");
            $stmt->execute([$_SESSION['user_id'], $doctor_id, $date, $slot]);
            
            $message = [
                'type' => 'success',
                'text' => "Rendez-vous confirmé pour le " . date('d/m/Y', strtotime($date)) . " à " . substr($slot, 0, 5)
            ];
            
            // Actualiser la liste des rendez-vous à venir
            $stmt = $bd->prepare("SELECT a.id, a.date, a.time, u.name AS doctor_name, a.status 
                                 FROM appointments a 
                                 JOIN users u ON a.doctor_id = u.id 
                                 WHERE a.patient_id = ? AND a.date >= CURDATE() 
                                 ORDER BY a.date DESC, a.time DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            unset($doctor_id, $date, $available_slots);
        } else {
            $message = [
                'type' => 'error',
                'text' => "Ce créneau est déjà réservé. Veuillez en choisir un autre."
            ];
        }
    }

    // Annulation de rendez-vous
    if (isset($_POST['cancel_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $active_tab = 'upcoming';
        
        $stmt = $bd->prepare("UPDATE appointments SET status = 'annule' 
                             WHERE id = ? AND patient_id = ?");
        $stmt->execute([$appointment_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $message = [
                'type' => 'success',
                'text' => "Le rendez-vous a été annulé avec succès."
            ];
            
            // Actualiser la liste des rendez-vous à venir
            $stmt = $bd->prepare("SELECT a.id, a.date, a.time, u.name AS doctor_name, a.status 
                                 FROM appointments a 
                                 JOIN users u ON a.doctor_id = u.id 
                                 WHERE a.patient_id = ? AND a.date >= CURDATE() 
                                 ORDER BY a.date DESC, a.time DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $message = [
                'type' => 'error',
                'text' => "Impossible d'annuler ce rendez-vous."
            ];
        }
    }

    // Gestion du profil (onglet "profile")
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'profile') {
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $errors = [];

            // Vérification du nom
            if ($name === '') {
                $errors[] = "Le nom ne peut pas être vide.";
            }

            // Vérification du mot de passe si l'utilisateur veut le changer
            $password_changed = false;
            if ($old_password || $new_password || $confirm_password) {
                // Vérifier que l'ancien mot de passe est correct
                if (!$old_password) {
                    $errors[] = "Veuillez saisir votre ancien mot de passe.";
                } elseif ($old_password !== $user['password']) {
                    $errors[] = "L'ancien mot de passe est incorrect.";
                } else {
                    if (!$new_password) {
                        $errors[] = "Veuillez saisir le nouveau mot de passe.";
                    } elseif ($new_password === $old_password) {
                        $errors[] = "Le nouveau mot de passe doit être différent de l'ancien.";
                    } elseif ($new_password !== $confirm_password) {
                        $errors[] = "La confirmation du mot de passe ne correspond pas.";
                    } else {
                        $password_changed = true;
                    }
                }
            }

            $name_changed = ($name !== $user['name']);

            if (empty($errors)) {
                // Mise à jour du nom
                if ($name_changed) {
                    $stmt = $bd->prepare("UPDATE users SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $_SESSION['user_id']]);
                    $user['name'] = $name;
                }
                // Mise à jour du mot de passe
                if ($password_changed) {
                    $stmt = $bd->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password, $_SESSION['user_id']]);
                }

                // Message adapté
                if ($name_changed && $password_changed) {
                    $message = ['type' => 'success', 'text' => "Nom et mot de passe modifiés avec succès."];
                } elseif ($name_changed) {
                    $message = ['type' => 'success', 'text' => "Nom modifié avec succès."];
                } elseif ($password_changed) {
                    $message = ['type' => 'success', 'text' => "Mot de passe changé avec succès."];
                } else {
                    $message = ['type' => 'info', 'text' => "Aucune modification effectuée."];
                }
            } else {
                $message = ['type' => 'error', 'text' => implode('<br>', $errors)];
            }
        }
        // Suppression du compte
        if (isset($_POST['delete_account'])) {
            $stmt = $bd->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            session_destroy();
            header("Location: login.php");
            exit();
        }
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord patient</title>
    <link rel="stylesheet" href="patient.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Barre latérale -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-heartbeat"></i> MediCare</h2>
            </div>
            <nav>
                <ul>
                    <li>
                        <a href="?tab=appointments" class="<?= $active_tab === 'appointments' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-alt"></i> Prendre RDV
                        </a>
                    </li>
                    <li>
                        <a href="?tab=upcoming" class="<?= $active_tab === 'upcoming' ? 'active' : '' ?>">
                            <i class="fas fa-clock"></i> RDV à venir
                        </a>
                    </li>
                    <li>
                        <a href="?tab=history" class="<?= $active_tab === 'history' ? 'active' : '' ?>">
                            <i class="fas fa-history"></i> Historique
                        </a>
                    </li>
                    <li>
                        <a href="?tab=profile" class="<?= $active_tab === 'profile' ? 'active' : '' ?>">
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
                <h1>Bonjour, <?= htmlspecialchars($user['name']) ?> <span class="waving-hand">👋</span></h1>
                <p>Bienvenue sur votre espace patient</p>
            </header>

            <!-- Message de feedback -->
            <?php if (!empty($message)): ?>
                <div class="message <?= $message['type'] ?>">
                    <i class="fas fa-<?= $message['type'] === 'success' ? 'check' : ($message['type'] === 'info' ? 'info' : 'exclamation') ?>-circle"></i>
                    <?= $message['text'] ?>
                </div>
            <?php endif; ?>

            <!-- Onglets -->
            <div class="tabs">
                <div class="tab <?= $active_tab === 'appointments' ? 'active' : '' ?>" onclick="window.location.href='?tab=appointments'">Prendre RDV</div>
                <div class="tab <?= $active_tab === 'upcoming' ? 'active' : '' ?>" onclick="window.location.href='?tab=upcoming'">RDV à venir</div>
                <div class="tab <?= $active_tab === 'history' ? 'active' : '' ?>" onclick="window.location.href='?tab=history'">Historique</div>
                <div class="tab <?= $active_tab === 'profile' ? 'active' : '' ?>" onclick="window.location.href='?tab=profile'">Mon Profil</div>
            </div>

            <!-- Prendre RDV -->
            <div class="tab-content <?= $active_tab === 'appointments' ? 'active' : '' ?>" id="appointments-tab">
                <div class="card">
                    <h2><i class="fas fa-calendar-plus"></i> Nouveau rendez-vous</h2>
                    <?php if (empty($doctor_id)): ?>
                        <div class="doctor-cards">
                            <?php foreach ($doctors as $doctor): ?>
                                <div class="doctor-card">
                                    <h3>Dr. <?= htmlspecialchars($doctor['name']) ?></h3>
                                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($doctor['email']) ?></p>
                                    <p><i class=""></i> <?= htmlspecialchars($doctor['specialty']) ?></p>
                                    <form method="POST" class="choose-doctor-form">
                                        <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-check"></i> Choisir
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?php
                            $selected_doctor = null;
                            foreach ($doctors as $doc) {
                                if ($doc['id'] == $doctor_id) {
                                    $selected_doctor = $doc;
                                    break;
                                }
                            }
                        ?>
                        <div class="selected-doctor">
                            <h3>Vous avez choisi Dr. <?= htmlspecialchars($selected_doctor['name']) ?></h3>
                            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($selected_doctor['email']) ?></p>
                            <form method="POST" style="display:inline;">
                                <button type="submit" class="btn btn-secondary" name="change_doctor">Changer de médecin</button>
                            </form>
                        </div>
                        <?php if (!$date): ?>
                            <form method="POST" class="appointment-form">
                                <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($doctor_id) ?>">
                                <div class="form-group">
                                    <label for="date">Date :</label>
                                    <input type="date" name="date" id="date" required min="<?= date('Y-m-d') ?>" 
                                           value="<?= isset($date) ? htmlspecialchars($date) : '' ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Voir les disponibilités
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($available_slots)): ?>
                            <form method="POST" class="appointment-form" style="margin-top: 1.5rem;">
                                <input type="hidden" name="selected_doctor" value="<?= htmlspecialchars($doctor_id) ?>">
                                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                                <div class="form-group">
                                    <label for="slot">Créneau horaire :</label>
                                    <select name="slot" id="slot" required>
                                        <?php foreach ($available_slots as $slot): ?>
                                            <option value="<?= $slot ?>"><?= substr($slot, 0, 5) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="confirm_rdv" class="btn btn-success">
                                    <i class="fas fa-check"></i> Confirmer le RDV
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RDV à venir -->
            <div class="tab-content <?= $active_tab === 'upcoming' ? 'active' : '' ?>" id="upcoming-tab">
                <div class="card">
                    <h2><i class="fas fa-calendar-check"></i> Mes rendez-vous à venir</h2>
                    <?php if (empty($upcoming_appointments)): ?>
                        <p style="padding: 1rem; color: #6c757d;">Aucun rendez-vous à venir.</p>
                    <?php else: ?>
                        <ul class="appointment-list">
                            <?php foreach ($upcoming_appointments as $appt): ?>
                                <li>
                                    <strong><?= date('d/m/Y', strtotime($appt['date'])) ?> à <?= substr($appt['time'], 0, 5) ?></strong>
                                    <p>Avec Dr. <?= htmlspecialchars($appt['doctor_name']) ?></p>
                                    <span class="status-badge status-<?= 
                                        $appt['status'] === 'accepte' ? 'accepted' : 
                                        ($appt['status'] === 'annule' ? 'cancelled' : 'pending') 
                                    ?>">
                                        <?= 
                                            $appt['status'] === 'accepte' ? 'Confirmé' : 
                                            ($appt['status'] === 'annule' ? 'Annulé' : 'En attente')
                                        ?>
                                    </span>
                                    
                                    <?php if ($appt['status'] !== 'annule'): ?>
                                        <form method="POST" style="margin-top: 0.5rem;">
                                            <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                            <button type="submit" name="cancel_appointment" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
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
                                    <p>Avec Dr. <?= htmlspecialchars($appt['doctor_name']) ?></p>
                                    <span class="status-badge">
                                        Terminé
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profil -->
            <div class="tab-content <?= $active_tab === 'profile' ? 'active' : '' ?>" id="profile-tab">
                <div class="profile-card">
                    <h2><i class="fas fa-user"></i> Mon Profil</h2>
                    <form method="POST" class="profile-form" action="?tab=profile">
                        <div class="profile-row">
                            <label for="name">Nom :</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="profile-row">
                            <label for="email">Email :</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f3f3f3; color:#888;">
                        </div>
                        <div class="profile-row">
                            <label for="old_password">Ancien mot de passe :</label>
                            <input type="password" id="old_password" name="old_password" placeholder="Pour changer le mot de passe">
                        </div>
                        <div class="profile-row">
                            <label for="new_password">Nouveau mot de passe :</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Laisser vide si inchangé">
                        </div>
                        <div class="profile-row">
                            <label for="confirm_password">Confirmer :</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Laisser vide si inchangé">
                        </div>
                        <div class="profile-row-btn">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                    <form method="POST" action="?tab=profile" class="profile-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.');">
                        <div class="profile-row-btn">
                            <button type="submit" name="delete_account" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Supprimer mon compte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>