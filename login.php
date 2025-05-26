<?php
require "config.php";

$error_email = "";
$error_password = "";

if (isset($_POST['connect'])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Prepare and execute query to check if email exists
    $q = $bd->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $q->execute([$email]);
    $u = $q->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        $error_email = "Cet email n'existe pas.";
    } elseif ($password !== $u['password']) {
        $error_password = "Mot de passe incorrect.";
    } else {
        // Successful login
        $_SESSION["user_id"] = $u["id"];
        $_SESSION["user_name"] = $u["name"];
        $_SESSION["role"] = $u["role"];

        // Redirect based on role
        if ($u["role"] === "patient") {
            header("Location: dashboard_patient.php");
            exit();
        } elseif ($u["role"] === "doctor") {
            header("Location: doctor_dashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion</title>
  <link rel="stylesheet" href="style1.css" />
</head>
<body>
  <div class="login-container">
    <h2>Connexion</h2>
    <form action="" method="POST" class="login-form" id="login-form">
      <div class="input-group">
        <label for="email">Email <span class="required"></span></label>
        <input type="email" id="email" placeholder="Entrez votre email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required />
        <span class="error-message" id="email-error"><?php echo $error_email; ?></span>
      </div>
      <div class="input-group">
        <label for="password">Mot de passe <span class="required"></span></label>
        <input type="password" id="password" placeholder="Entrez votre mot de passe" name="password" required />
        <span class="error-message" id="password-error"><?php echo $error_password; ?></span>
      </div>
      <button type="submit" name="connect" class="btn primary-btn">Se connecter</button>
      <button type="button" class="btn secondary-btn"><a href="signup.php">S'inscrire</a></button>
    </form>
  </div>
  <script src="script.js"></script>
</body>
</html>