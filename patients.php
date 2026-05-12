<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

$filtre_canton = $_GET['canton'] ?? '';
$filtre_assurance = $_GET['assurance'] ?? '';
$filtre_allergies = $_GET['allergies'] ?? '';

$patients = [];
$erreur = '';

try {
    $sql = "SELECT patient_id, nom, prenom, date_naissance, email, telephone, canton, assurance, allergies, date_inscription
            FROM Patients WHERE 1=1";
    $params = [];
    if ($filtre_canton) { $sql .= " AND canton = :canton"; $params['canton'] = $filtre_canton; }
    if ($filtre_assurance) { $sql .= " AND assurance = :assurance"; $params['assurance'] = $filtre_assurance; }
    if ($filtre_allergies === 'avec') $sql .= " AND allergies IS NOT NULL AND allergies != ''";
    elseif ($filtre_allergies === 'sans') $sql .= " AND (allergies IS NULL OR allergies = '')";
    $sql .= " ORDER BY nom, prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patients = $stmt->fetchAll();
    $cantons = $pdo->query("SELECT DISTINCT canton FROM Patients ORDER BY canton")->fetchAll(PDO::FETCH_COLUMN);
    $assurances = $pdo->query("SELECT DISTINCT assurance FROM Patients ORDER BY assurance")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $erreur = "Vous n'avez pas les droits pour consulter cette table : " . $e->getMessage();
    $cantons = [];
    $assurances = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Patients - PharmaSwiss</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">PS</div><div><h1>PharmaSwiss</h1><p>Liste des patients</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a>
        <a href="console.php">Console SQL</a>
        <a href="patients.php" class="active">Patients</a>
        <a href="medicaments.php">Médicaments</a>
        <a href="ordonnances.php">Ordonnances</a>
        <a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Filtrer (<?= count($patients) ?> résultat<?= count($patients) > 1 ? 's' : '' ?>)</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Canton</label>
                            <select name="canton">
                                <option value="">Tous</option>
                                <?php foreach ($cantons as $c): ?>
                                    <option value="<?= h($c) ?>" <?= $filtre_canton === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assurance</label>
                            <select name="assurance">
                                <option value="">Toutes</option>
                                <?php foreach ($assurances as $a): ?>
                                    <option value="<?= h($a) ?>" <?= $filtre_assurance === $a ? 'selected' : '' ?>><?= h($a) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Allergies</label>
                            <select name="allergies">
                                <option value="">Tous</option>
                                <option value="avec" <?= $filtre_allergies === 'avec' ? 'selected' : '' ?>>Avec allergies</option>
                                <option value="sans" <?= $filtre_allergies === 'sans' ? 'selected' : '' ?>>Sans allergies</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit">Filtrer</button></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><a href="patients.php" class="btn btn-warning">Réinitialiser</a></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Liste</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Né(e) le</th><th>Email</th><th>Canton</th><th>Assurance</th><th>Allergies</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patients)): ?>
                                <tr><td colspan="8" class="text-center text-muted">Aucun patient.</td></tr>
                            <?php else: foreach ($patients as $p): ?>
                                <tr>
                                    <td><?= h($p['patient_id']) ?></td>
                                    <td><strong><?= h($p['nom']) ?></strong></td>
                                    <td><?= h($p['prenom']) ?></td>
                                    <td><?= h(date('d/m/Y', strtotime($p['date_naissance']))) ?></td>
                                    <td><?= h($p['email']) ?></td>
                                    <td><?= h($p['canton']) ?></td>
                                    <td><?= h($p['assurance']) ?></td>
                                    <td><?= $p['allergies'] ? '<span class="badge alerte">'.h($p['allergies']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <footer>PharmaSwiss - École Schulz</footer>
</body>
</html>
