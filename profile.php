<?php
require "config.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur
try {
    $q = $bd->prepare("SELECT name, email, role, specialty FROM users WHERE id = ?");
    $q->execute([$_SESSION['user_id']]);
    $user = $q->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== 'doctor') {
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = "Erreur de récupération du profil : " . $e->getMessage();
}

// Traitement du formulaire de modification
$error_name = "";
$error_specialty = "";
$success_message = "";

if (isset($_POST['update'])) {
    $name = trim($_POST["name"]);
    $specialty = trim($_POST["specialty"]);
    $isValid = true;

    // Validate name
    if (empty($name)) {
        $error_name = "Le nom complet est requis.";
        $isValid = false;
    }

    // Validate specialty
    if (empty($specialty)) {
        $error_specialty = "La spécialité est requise.";
        $isValid = false;
    }

    if ($isValid) {
        try {
            $q = $bd->prepare("UPDATE users SET name = ?, specialty = ? WHERE id = ?");
            $q->execute([$name, $specialty, $_SESSION['user_id']]);
            $success_message = "Profil mis à jour avec succès !";
            // Mettre à jour les données affichées
            $user['name'] = $name;
            $user['specialty'] = $specialty;
        } catch (PDOException $e) {
            $error_message = "Erreur de mise à jour : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil du Docteur</title>
    <style>
        :root {
            --primary-color: #0097a7;
            --secondary-color: #00796b;
            --accent-color: #e0f7fa;
            --dark-color: #333;
            --light-color: #ffffff;
            --success-color: #00796b;
            --danger-color: #f08080;
            --warning-color: #ffd580;
            --info-color: #0097a7;
            --sidebar-width: 280px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('https://cdn.pixabay.com/photo/2017/08/06/11/39/doctor-2597291_1280.jpg') no-repeat center center/cover;
        }

        .profile-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        .profile-container h2 {
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .input-group {
            position: relative;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.85rem;
            color: var(--dark-color);
            margin-bottom: 0.4rem;
            font-weight: 500;
        }

        .required {
            color: var(--danger-color);
        }

        input,
        select {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid var(--accent-color);
            border-radius: 6px;
            font-size: 0.95rem;
            color: var(--dark-color);
            background: var(--light-color);
            transition: var(--transition);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 6px rgba(0, 151, 167, 0.3);
        }

        input::placeholder {
            color: #999;
        }

        select {
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%23333" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 0.6rem top 50%;
            background-size: 1rem;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.75rem;
            min-height: 1rem;
            display: block;
            margin-top: 0.2rem;
        }

        .success-message {
            color: var(--success-color);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 0.6rem;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .primary-btn {
            background: var(--primary-color);
            color: var(--light-color);
        }

        .primary-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 151, 167, 0.4);
        }

        .profile-info {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .profile-info p {
            font-size: 0.95rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .profile-info p strong {
            color: var(--primary-color);
        }

        .link {
            margin-top: 1rem;
        }

        .link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                padding: 1rem;
                max-width: 90%;
            }

            .profile-container h2 {
                font-size: 1.4rem;
            }

            .btn {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                max-width: 100%;
                padding: 0.8rem;
            }

            .profile-container h2 {
                font-size: 1.2rem;
            }

            input,
            select {
                font-size: 0.9rem;
                padding: 0.5rem;
            }

            .btn {
                font-size: 0.85rem;
                padding: 0.5rem;
            }

            .error-message,
            .success-message {
                font-size: 0.7rem;
            }
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <h2>Profil du Docteur</h2>
        <?php if (isset($error_message)) ?>
        <p class="success-message"><?php echo $success_message; ?></p>
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <div class="profile-info">
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Rôle :</strong> Docteur</p>
            <p><strong>Spécialité :</strong> <?php echo htmlspecialchars($user['specialty'] ?? 'Non spécifiée'); ?></p>
        </div>
        <form action="" method="POST" class="profile-form" id="profile-form">
            <div class="input-group">
                <label for="name">Nom complet <span class="required">*</span></label>
                <input type="text" id="name" name="name" placeholder="Entrez votre nom complet" value="<?php echo htmlspecialchars($user['name']); ?>" required />
                <span class="error-message" id="name-error"><?php echo $error_name; ?></span>
            </div>
            <div class="input-group">
                <label for="specialty">Spécialité <span class="required">*</span></label>
                <input type="text" id="specialty" name="specialty" placeholder="Entrez votre spécialité" required />
                <span class="error-message" id="specialty-error"><?php echo $error_specialty; ?></span>
            </div>
            <button type="submit" name="update" class="btn primary-btn">Mettre à jour</button>
        </form>
        <div class="link">
            <p><a href="doctor_dashboard.php">Retour au tableau de bord</a></p>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileForm = document.getElementById('profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const name = document.getElementById('name').value.trim();
                    const specialty = document.getElementById('specialty').value;
                    const nameError = document.getElementById('name-error');
                    const specialtyError = document.getElementById('specialty-error');

                    let isValid = true;

                    // Reset error messages
                    nameError.textContent = '';
                    specialtyError.textContent = '';

                    // Validate name
                    if (!name) {
                        nameError.textContent = 'Le nom complet est requis.';
                        isValid = false;
                    }

                    // Validate specialty
                    if (!specialty) {
                        specialtyError.textContent = 'La spécialité est requise.';
                        isValid = false;
                    }

                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>

</html>