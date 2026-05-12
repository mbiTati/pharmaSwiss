<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

$filtre_pharmacie = $_GET['pharmacie'] ?? '';
$filtre_categorie = $_GET['categorie'] ?? '';
$filtre_stock = $_GET['stock'] ?? '';
$filtre_ordonnance = $_GET['ordonnance'] ?? '';

$medicaments = [];
$erreur = '';

try {
    $sql = "SELECT m.medicament_id, m.nom_commercial, m.principe_actif, m.forme, m.dosage, m.categorie,
                   m.sur_ordonnance, m.prix_unitaire, m.stock_actuel, m.stock_minimum, m.date_peremption,
                   ph.nom_pharmacie, ph.ville
            FROM Medicaments m
            JOIN Pharmacies ph ON m.pharmacie_id = ph.pharmacie_id
            WHERE 1=1";
    $params = [];
    if ($filtre_pharmacie) { $sql .= " AND m.pharmacie_id = :ph"; $params['ph'] = (int)$filtre_pharmacie; }
    if ($filtre_categorie) { $sql .= " AND m.categorie = :cat"; $params['cat'] = $filtre_categorie; }
    if ($filtre_stock === 'rupture') $sql .= " AND m.stock_actuel <= m.stock_minimum";
    elseif ($filtre_stock === 'zero') $sql .= " AND m.stock_actuel = 0";
    if ($filtre_ordonnance === 'oui') $sql .= " AND m.sur_ordonnance = TRUE";
    elseif ($filtre_ordonnance === 'non') $sql .= " AND m.sur_ordonnance = FALSE";
    $sql .= " ORDER BY ph.nom_pharmacie, m.nom_commercial";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicaments = $stmt->fetchAll();
    $pharmacies = $pdo->query("SELECT pharmacie_id, nom_pharmacie FROM Pharmacies ORDER BY nom_pharmacie")->fetchAll();
    $categories = $pdo->query("SELECT DISTINCT categorie FROM Medicaments ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $erreur = "Vous n'avez pas les droits : " . $e->getMessage();
    $pharmacies = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Médicaments - PharmaSwiss</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">PS</div><div><h1>PharmaSwiss</h1><p>Catalogue des médicaments</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="patients.php">Patients</a><a href="medicaments.php" class="active">Médicaments</a>
        <a href="ordonnances.php">Ordonnances</a><a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Filtrer (<?= count($medicaments) ?> résultat<?= count($medicaments) > 1 ? 's' : '' ?>)</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pharmacie</label>
                            <select name="pharmacie">
                                <option value="">Toutes</option>
                                <?php foreach ($pharmacies as $p): ?>
                                    <option value="<?= h($p['pharmacie_id']) ?>" <?= $filtre_pharmacie == $p['pharmacie_id'] ? 'selected' : '' ?>><?= h($p['nom_pharmacie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Catégorie</label>
                            <select name="categorie">
                                <option value="">Toutes</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= h($c) ?>" <?= $filtre_categorie === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stock</label>
                            <select name="stock">
                                <option value="">Tous</option>
                                <option value="rupture" <?= $filtre_stock === 'rupture' ? 'selected' : '' ?>>En rupture (≤ minimum)</option>
                                <option value="zero" <?= $filtre_stock === 'zero' ? 'selected' : '' ?>>Stock à 0</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ordonnance</label>
                            <select name="ordonnance">
                                <option value="">Tous</option>
                                <option value="oui" <?= $filtre_ordonnance === 'oui' ? 'selected' : '' ?>>Sur ordonnance</option>
                                <option value="non" <?= $filtre_ordonnance === 'non' ? 'selected' : '' ?>>Vente libre</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit">Filtrer</button></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><a href="medicaments.php" class="btn btn-warning">Réinitialiser</a></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Catalogue</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Nom</th><th>Principe actif</th><th>Forme</th><th>Dosage</th><th>Catégorie</th><th>Pharmacie</th><th>Prix</th><th>Stock</th><th>Type</th><th>Péremption</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($medicaments)): ?>
                                <tr><td colspan="10" class="text-center text-muted">Aucun médicament.</td></tr>
                            <?php else: foreach ($medicaments as $m): ?>
                                <?php
                                    $stock_class = '';
                                    if ($m['stock_actuel'] == 0) $stock_class = 'badge rupture';
                                    elseif ($m['stock_actuel'] <= $m['stock_minimum']) $stock_class = 'badge alerte';
                                    else $stock_class = 'badge actif';
                                ?>
                                <tr>
                                    <td><strong><?= h($m['nom_commercial']) ?></strong></td>
                                    <td><?= h($m['principe_actif']) ?></td>
                                    <td><?= h($m['forme']) ?></td>
                                    <td><?= h($m['dosage']) ?></td>
                                    <td><?= h($m['categorie']) ?></td>
                                    <td><?= h($m['nom_pharmacie']) ?></td>
                                    <td style="text-align:right;"><?= number_format($m['prix_unitaire'], 2, '.', "'") ?> CHF</td>
                                    <td><span class="<?= $stock_class ?>"><?= $m['stock_actuel'] ?> / <?= $m['stock_minimum'] ?></span></td>
                                    <td><?= $m['sur_ordonnance'] ? '<span class="badge ordonnance">Ordonnance</span>' : '<span class="badge libre">Libre</span>' ?></td>
                                    <td><?= h(date('m/Y', strtotime($m['date_peremption']))) ?></td>
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
