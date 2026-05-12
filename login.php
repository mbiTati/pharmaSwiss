<?php
require_once 'connexion.php';

if (isset($_SESSION['db_user'])) {
    header('Location: index.php');
    exit;
}

$erreur = '';
$message = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'deconnecte': $message = 'Vous avez été déconnecté avec succès.'; break;
        case 'session_expiree': $erreur = 'Votre session a expiré. Veuillez vous reconnecter.'; break;
        case 'erreur_connexion': $erreur = 'Erreur de connexion à la base. Veuillez vous reconnecter.'; break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $erreur = 'Veuillez saisir votre identifiant et votre mot de passe.';
    } elseif (strlen($username) > 50 || strlen($password) > 100) {
        $erreur = 'Identifiants invalides.';
    } else {
        $pdo = tenter_connexion_mysql($username, $password);
        if ($pdo === false) {
            $erreur = 'Identifiants incorrects ou utilisateur sans accès à la base.';
            sleep(1);
        } else {
            session_regenerate_id(true);
            $_SESSION['db_user'] = $username;
            $_SESSION['db_pass'] = $password;
            $_SESSION['login_time'] = time();
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - PharmaSwiss</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header">
            <div class="logo-large">PS</div>
            <h1>PharmaSwiss</h1>
            <p>Plateforme de gestion pharmaceutique</p>
        </div>

        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert succes"><?= h($message) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Identifiant MySQL</label>
                <input type="text" id="username" name="username"
                       placeholder="ex: assistant_pharmacie, pharmacien_titulaire, administrateur_pharma"
                       value="<?= h($_POST['username'] ?? '') ?>"
                       required maxlength="50" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       placeholder="Votre mot de passe MySQL" required maxlength="100">
            </div>
            <button type="submit">Se connecter</button>
        </form>

        <div class="login-info">
            <strong>Comptes disponibles (créés par le script SQL) :</strong><br>
            <code>assistant_pharmacie</code> / <code>AssistantP@ss2025</code> - SELECT limité + INSERT ordonnances<br>
            <code>pharmacien_titulaire</code> / <code>TitulaireP@ss2025</code> - Lecture + INSERT/UPDATE ordonnances, médicaments<br>
            <code>administrateur_pharma</code> / <code>AdminP@ss2025</code> - Tous les droits<br>
            <br>
            <em>L'authentification se fait directement contre votre serveur MySQL local.
            Vos droits dans l'application correspondent à vos permissions GRANT MySQL.</em>
        </div>
    </div>
</body>
</html>
