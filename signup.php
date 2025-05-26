<?php
require "config.php";

$error_nom = "";
$error_email = "";
$error_role = "";
$error_password = "";
$error_mdp = "";
$error_specialty = "";
$success_message = "";

if (isset($_POST['inscrire'])) {
    $nom = trim($_POST["nom"]);
    $email = trim($_POST["email"]);
    $role = trim($_POST["role"]);
    $password = trim($_POST["password"]);
    $mdp = trim($_POST["mdp"]);
    $specialty = isset($_POST["specialty"]) ? trim($_POST["specialty"]) : "";

    $isValid = true;

    // Validate name
    if (empty($nom)) {
        $error_nom = "Le nom complet est requis.";
        $isValid = false;
    }

    // Validate email
    if (empty($email)) {
        $error_email = "L'email est requis.";
        $isValid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_email = "Veuillez entrer un email valide.";
        $isValid = false;
    } else {
        // Check if email already exists
        try {
            $q = $bd->prepare("SELECT id FROM users WHERE email = ?");
            $q->execute([$email]);
            if ($q->fetch()) {
                $error_email = "Cet email est déjà utilisé.";
                $isValid = false;
            }
        } catch (PDOException $e) {
            $error_email = "Erreur de vérification de l'email.";
            $isValid = false;
        }
    }

    // Validate role
    if (empty($role) || !in_array($role, ["patient", "doctor"])) {
        $error_role = "Veuillez sélectionner un rôle valide.";
        $isValid = false;
    }

    // Validate specialty (required only for doctors)
    if ($role === "doctor" && empty($specialty)) {
        $error_specialty = "La spécialité est requise pour les docteurs.";
        $isValid = false;
    }

    // Validate passwords
    if (empty($password)) {
        $error_password = "Le mot de passe est requis.";
        $isValid = false;
    } elseif (strlen($password) < 6) {
        $error_password = "Le mot de passe doit contenir au moins 6 caractères.";
        $isValid = false;
    }
    if ($password !== $mdp) {
        $error_mdp = "Les mots de passe ne correspondent pas.";
        $isValid = false;
    }

    if ($isValid) {
        try {
            // Insert user into database (password should be hashed in production)
            $q = $bd->prepare("INSERT INTO users (name, email, role, password, specialty) VALUES (?, ?, ?, ?, ?)");
            $q->execute([$nom, $email, $role, $password, $role === "doctor" ? $specialty : NULL]); // Use password_hash($password, PASSWORD_DEFAULT) in production
            $success_message = "Inscription réussie ! Redirection vers la connexion...";
            header("refresh:2;url=login.php");
        } catch (PDOException $e) {
            $error_email = "Erreur d'enregistrement : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Créer un compte</title>
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

        .signup-container,
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        .signup-container h2,
        .login-container h2 {
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .signup-form,
        .login-form {
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

        .secondary-btn {
            background: var(--accent-color);
            color: var(--dark-color);
            border: 2px solid var(--primary-color);
        }

        .secondary-btn:hover {
            background: var(--primary-color);
            color: var(--light-color);
            transform: translateY(-2px);
        }

        .secondary-btn a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .link {
            margin-top: 1rem;
        }

        .link p {
            font-size: 0.85rem;
            color: var(--dark-color);
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

        .hero {
            min-height: 100vh;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                url('https://cdn.pixabay.com/photo/2017/08/06/11/39/doctor-2597291_1280.jpg') no-repeat center center/cover;
        }

        .hero-content {
            text-align: center;
            color: var(--light-color);
            padding: 1.5rem;
            max-width: 600px;
            animation: fadeIn 0.8s ease-out;
        }

        .hero-content h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-content p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
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
            .signup-container,
            .login-container,
            .hero-content {
                margin: 1rem;
                padding: 1rem;
                max-width: 90%;
            }

            .signup-container h2,
            .login-container h2,
            .hero-content h2 {
                font-size: 1.4rem;
            }

            .btn {
                font-size: 0.9rem;
            }

            .hero-content p {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .signup-container,
            .login-container,
            .hero-content {
                max-width: 100%;
                padding: 0.8rem;
            }

            .signup-container h2,
            .login-container h2,
            .hero-content h2 {
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

            .hero-content p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <h2>Créer un compte</h2>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <form action="" method="POST" class="signup-form" id="signup-form">
            <div class="input-group">
                <label for="nom">Nom complet <span class="required">*</span></label>
                <input type="text" id="nom" name="nom" placeholder="Entrez votre nom complet" value="<?php echo htmlspecialchars($nom ?? ''); ?>" required />
                <span class="error-message" id="nom-error"><?php echo $error_nom; ?></span>
            </div>
            <div class="input-group">
                <label for="email">Adresse e-mail <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Entrez votre email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required />
                <span class="error-message" id="email-error"><?php echo $error_email; ?></span>
            </div>
            <div class="input-group">
                <label for="role">Je suis <span class="required">*</span></label>
                <select id="role" name="role" required>
                    <option value="">-- Choisir --</option>
                    <option value="patient" <?php echo (isset($role) && $role === "patient") ? "selected" : ""; ?>>Patient</option>
                    <option value="doctor" <?php echo (isset($role) && $role === "doctor") ? "selected" : ""; ?>>Docteur</option>
                </select>
                <span class="error-message" id="role-error"><?php echo $error_role; ?></span>
            </div>
            <div class="input-group" id="specialtyField" style="display: none;">
                <label for="specialty">Spécialité <span class="required">*</span></label>
                <input type="text" id="specialty" name="specialty" placeholder="Entrez votre spécialité" required />
                <span class="error-message" id="specialty-error"><?php echo $error_specialty; ?></span>
            </div>
            <div class="input-group">
                <label for="password">Mot de passe <span class="required">*</span></label>
                <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required />
                <span class="error-message" id="password-error"><?php echo $error_password; ?></span>
            </div>
            <div class="input-group">
                <label for="mdp">Confirmer le mot de passe <span class="required">*</span></label>
                <input type="password" id="mdp" name="mdp" placeholder="Confirmez votre mot de passe" required />
                <span class="error-message" id="mdp-error"><?php echo $error_mdp; ?></span>
            </div>
            <button type="submit" name="inscrire" class="btn primary-btn">S'inscrire</button>
        </form>
        <div class="link">
            <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const signupForm = document.getElementById('signup-form');
            const roleSelect = document.getElementById('role');
            const specialtyField = document.getElementById('specialtyField');
            const specialtyInput = document.getElementById('specialty');

            // Function to toggle specialty field visibility
            function toggleSpecialtyField() {
                specialtyField.style.display = roleSelect.value === 'doctor' ? 'block' : 'none';
                specialtyInput.required = roleSelect.value === 'doctor';
            }

            // Initial toggle based on current role value
            toggleSpecialtyField();

            // Toggle specialty field on role change
            roleSelect.addEventListener('change', toggleSpecialtyField);

            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    const nom = document.getElementById('nom').value.trim();
                    const email = document.getElementById('email').value.trim();
                    const role = document.getElementById('role').value;
                    const specialty = document.getElementById('specialty').value;
                    const password = document.getElementById('password').value.trim();
                    const mdp = document.getElementById('mdp').value.trim();

                    const nomError = document.getElementById('nom-error');
                    const emailError = document.getElementById('email-error');
                    const roleError = document.getElementById('role-error');
                    const specialtyError = document.getElementById('specialty-error');
                    const passwordError = document.getElementById('password-error');
                    const mdpError = document.getElementById('mdp-error');

                    let isValid = true;

                    // Reset error messages
                    nomError.textContent = '';
                    emailError.textContent = '';
                    roleError.textContent = '';
                    specialtyError.textContent = '';
                    passwordError.textContent = '';
                    mdpError.textContent = '';

                    // Validate name
                    if (!nom) {
                        nomError.textContent = 'Le nom complet est requis.';
                        isValid = false;
                    }

                    // Validate email
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!email) {
                        emailError.textContent = 'L\'email est requis.';
                        isValid = false;
                    } else if (!emailPattern.test(email)) {
                        emailError.textContent = 'Veuillez entrer un email valide.';
                        isValid = false;
                    }

                    // Validate role
                    if (!role) {
                        roleError.textContent = 'Veuillez sélectionner un rôle.';
                        isValid = false;
                    }

                    // Validate specialty (only for doctors)
                    if (role === 'doctor' && !specialty) {
                        specialtyError.textContent = 'La spécialité est requise pour les docteurs.';
                        isValid = false;
                    }

                    // Validate password
                    if (!password) {
                        passwordError.textContent = 'Le mot de passe est requis.';
                        isValid = false;
                    } else if (password.length < 6) {
                        passwordError.textContent = 'Le mot de passe doit contenir au moins 6 caractères.';
                        isValid = false;
                    }

                    // Validate password confirmation
                    if (password !== mdp) {
                        mdpError.textContent = 'Les mots de passe ne correspondent pas.';
                        isValid = false;
                    }

                    if (!isValid) {
                        e.preventDefault(); // Prevent form submission if validation fails
                    }
                });
            }
        });
    </script>
</body>

</html>