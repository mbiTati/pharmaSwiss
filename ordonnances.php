<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

$message = '';
$type_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delivrer') {
    $id = (int)($_POST['ordonnance_id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE Ordonnances SET statut='Délivrée', date_delivrance=NOW() WHERE ordonnance_id = :id AND statut='En attente'");
            $stmt->execute(['id' => $id]);
            $message = "Ordonnance #$id marquée comme délivrée.";
            $type_message = 'succes';
        } catch (PDOException $e) {
            $message = "Action refusée par MySQL : " . $e->getMessage();
            $type_message = 'erreur';
        }
    }
}

$filtre_statut = $_GET['statut'] ?? '';
$filtre_pharmacie = $_GET['pharmacie'] ?? '';

$ordonnances = [];
$erreur = '';

try {
    $sql = "SELECT o.ordonnance_id, o.date_prescription, o.date_delivrance, o.statut, o.montant_total,
                   o.rembourse_assurance, o.montant_rembourse,
                   p.nom AS patient_nom, p.prenom AS patient_prenom,
                   pc.nom AS pharmacien_nom,
                   ph.nom_pharmacie
            FROM Ordonnances o
            JOIN Patients p ON o.patient_id = p.patient_id
            JOIN Pharmaciens pc ON o.pharmacien_id = pc.pharmacien_id
            JOIN Pharmacies ph ON o.pharmacie_id = ph.pharmacie_id
            WHERE 1=1";
    $params = [];
    if ($filtre_statut) { $sql .= " AND o.statut = :s"; $params['s'] = $filtre_statut; }
    if ($filtre_pharmacie) { $sql .= " AND o.pharmacie_id = :p"; $params['p'] = (int)$filtre_pharmacie; }
    $sql .= " ORDER BY o.date_prescription DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ordonnances = $stmt->fetchAll();
    $pharmacies = $pdo->query("SELECT pharmacie_id, nom_pharmacie FROM Pharmacies ORDER BY nom_pharmacie")->fetchAll();
} catch (PDOException $e) {
    $erreur = "Vous n'avez pas les droits : " . $e->getMessage();
    $pharmacies = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Ordonnances - PharmaSwiss</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">PS</div><div><h1>PharmaSwiss</h1><p>Ordonnances</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="patients.php">Patients</a><a href="medicaments.php">Médicaments</a>
        <a href="ordonnances.php" class="active">Ordonnances</a><a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <?php if ($message): ?>
            <div class="alert <?= h($type_message) ?>"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alert erreur"><?= h($erreur) ?></div>
        <?php else: ?>
            <div class="card">
                <h2>Filtrer (<?= count($ordonnances) ?> résultat<?= count($ordonnances) > 1 ? 's' : '' ?>)</h2>
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="statut">
                                <option value="">Tous</option>
                                <option value="Délivrée" <?= $filtre_statut === 'Délivrée' ? 'selected' : '' ?>>Délivrée</option>
                                <option value="En attente" <?= $filtre_statut === 'En attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="Annulée" <?= $filtre_statut === 'Annulée' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pharmacie</label>
                            <select name="pharmacie">
                                <option value="">Toutes</option>
                                <?php foreach ($pharmacies as $p): ?>
                                    <option value="<?= h($p['pharmacie_id']) ?>" <?= $filtre_pharmacie == $p['pharmacie_id'] ? 'selected' : '' ?>><?= h($p['nom_pharmacie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit">Filtrer</button></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><a href="ordonnances.php" class="btn btn-warning">Réinitialiser</a></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Liste des ordonnances</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Date</th><th>Patient</th><th>Pharmacien</th><th>Pharmacie</th><th>Statut</th><th>Montant</th><th>Remboursé</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ordonnances)): ?>
                                <tr><td colspan="9" class="text-center text-muted">Aucune ordonnance.</td></tr>
                            <?php else: foreach ($ordonnances as $o): ?>
                                <?php $cl = strtolower(str_replace('é','e',$o['statut'])); ?>
                                <tr>
                                    <td><?= h($o['ordonnance_id']) ?></td>
                                    <td><?= h(date('d/m/Y', strtotime($o['date_prescription']))) ?></td>
                                    <td><?= h($o['patient_prenom'] . ' ' . $o['patient_nom']) ?></td>
                                    <td><?= h($o['pharmacien_nom']) ?></td>
                                    <td><?= h($o['nom_pharmacie']) ?></td>
                                    <td><span class="badge <?= $cl ?>"><?= h($o['statut']) ?></span></td>
                                    <td style="text-align:right;"><?= number_format($o['montant_total'] ?? 0, 2, '.', "'") ?> CHF</td>
                                    <td><?= $o['rembourse_assurance'] ? number_format($o['montant_rembourse'], 2, '.', "'") . ' CHF' : '<span class="text-muted">—</span>' ?></td>
                                    <td>
                                        <?php if ($o['statut'] === 'En attente'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Délivrer cette ordonnance ?');">
                                                <input type="hidden" name="action" value="delivrer">
                                                <input type="hidden" name="ordonnance_id" value="<?= h($o['ordonnance_id']) ?>">
                                                <button type="submit" class="btn btn-success" style="padding:5px 10px; font-size:12px;">Délivrer</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="info-box" style="background:#eef5f1; border-left:3px solid #2e8b5f; padding:12px 15px; border-radius:4px; font-size:13px; margin-top:20px;">
                <strong>À tester :</strong> avec le compte <code>assistant_pharmacie</code>, le bouton "Délivrer" peut être refusé selon vos permissions GRANT. Avec <code>pharmacien_titulaire</code> ou <code>administrateur_pharma</code>, l'action fonctionnera. C'est la sécurité au niveau base de données (LO2) en action.
            </div>
        <?php endif; ?>
    </main>
    <footer>PharmaSwiss - École Schulz</footer>
</body>
</html>
