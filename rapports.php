<?php
require_once 'connexion.php';
exiger_authentification();
$pdo = get_pdo();
$role = get_role_utilisateur();

function safe_query($pdo, $sql) {
    try { return $pdo->query($sql)->fetchAll(); }
    catch (PDOException $e) { return null; }
}

// R1 : CA par pharmacie
$rapport_ca = safe_query($pdo, "
    SELECT ph.nom_pharmacie, ph.ville,
           COUNT(o.ordonnance_id) AS nb_ordonnances,
           ROUND(SUM(o.montant_total), 2) AS ca_total,
           ROUND(SUM(o.montant_rembourse), 2) AS total_rembourse
    FROM Pharmacies ph
    LEFT JOIN Ordonnances o ON ph.pharmacie_id = o.pharmacie_id AND o.statut = 'Délivrée'
    GROUP BY ph.pharmacie_id, ph.nom_pharmacie, ph.ville
    ORDER BY ca_total DESC
");

// R2 : Médicaments en rupture
$rapport_rupture = safe_query($pdo, "
    SELECT m.nom_commercial, m.categorie, m.stock_actuel, m.stock_minimum, ph.nom_pharmacie
    FROM Medicaments m
    JOIN Pharmacies ph ON m.pharmacie_id = ph.pharmacie_id
    WHERE m.stock_actuel <= m.stock_minimum
    ORDER BY m.stock_actuel ASC
");

// R3 : Top catégories
$rapport_categories = safe_query($pdo, "
    SELECT m.categorie,
           COUNT(DISTINCT lo.ligne_id) AS nb_delivrances,
           ROUND(SUM(lo.montant_ligne), 2) AS ca_categorie
    FROM Medicaments m
    JOIN Lignes_Ordonnance lo ON m.medicament_id = lo.medicament_id
    JOIN Ordonnances o ON lo.ordonnance_id = o.ordonnance_id
    WHERE o.statut = 'Délivrée'
    GROUP BY m.categorie
    ORDER BY ca_categorie DESC
");

// R4 : Patients avec allergies
$rapport_allergies = safe_query($pdo, "
    SELECT nom, prenom, canton, allergies, assurance
    FROM Patients
    WHERE allergies IS NOT NULL AND allergies != ''
    ORDER BY nom
");

// R5 : Performance pharmaciens
$rapport_pharmaciens = safe_query($pdo, "
    SELECT pc.nom, pc.prenom, pc.role, ph.nom_pharmacie,
           COUNT(o.ordonnance_id) AS nb_delivrees,
           ROUND(SUM(o.montant_total), 2) AS ca_genere
    FROM Pharmaciens pc
    LEFT JOIN Ordonnances o ON pc.pharmacien_id = o.pharmacien_id AND o.statut = 'Délivrée'
    JOIN Pharmacies ph ON pc.pharmacie_id = ph.pharmacie_id
    GROUP BY pc.pharmacien_id, pc.nom, pc.prenom, pc.role, ph.nom_pharmacie
    ORDER BY nb_delivrees DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Rapports - PharmaSwiss</title><link rel="stylesheet" href="style.css"></head>
<body>
    <header>
        <div class="logo"><div class="logo-icon">PS</div><div><h1>PharmaSwiss</h1><p>Rapports et tableaux de bord</p></div></div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php">Dashboard</a><a href="console.php">Console SQL</a>
        <a href="patients.php">Patients</a><a href="medicaments.php">Médicaments</a>
        <a href="ordonnances.php">Ordonnances</a><a href="rapports.php" class="active">Rapports</a>
    </nav>

    <main>
        <div class="card">
            <h2>Chiffre d'affaires par pharmacie</h2>
            <?php if ($rapport_ca === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Pharmacie</th><th>Ville</th><th>Nb ordonnances</th><th>CA total</th><th>Remboursé</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_ca as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['nom_pharmacie']) ?></strong></td>
                                    <td><?= h($r['ville']) ?></td>
                                    <td><?= h($r['nb_ordonnances']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['ca_total'] ?? 0, 2, '.', "'") ?> CHF</td>
                                    <td style="text-align:right;"><?= number_format($r['total_rembourse'] ?? 0, 2, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Médicaments en rupture de stock</h2>
            <?php if ($rapport_rupture === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php elseif (empty($rapport_rupture)): ?>
                <p class="text-muted">Aucune rupture de stock détectée.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Médicament</th><th>Catégorie</th><th>Pharmacie</th><th>Stock actuel</th><th>Stock min</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_rupture as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['nom_commercial']) ?></strong></td>
                                    <td><?= h($r['categorie']) ?></td>
                                    <td><?= h($r['nom_pharmacie']) ?></td>
                                    <td><span class="badge <?= $r['stock_actuel'] == 0 ? 'rupture' : 'alerte' ?>"><?= h($r['stock_actuel']) ?></span></td>
                                    <td><?= h($r['stock_minimum']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Top catégories thérapeutiques (par CA)</h2>
            <?php if ($rapport_categories === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Catégorie</th><th>Nb délivrances</th><th>CA</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_categories as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['categorie']) ?></strong></td>
                                    <td><?= h($r['nb_delivrances']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['ca_categorie'], 2, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Patients avec allergies (alerte sanitaire)</h2>
            <?php if ($rapport_allergies === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php elseif (empty($rapport_allergies)): ?>
                <p class="text-muted">Aucun patient avec allergie déclarée.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Nom</th><th>Prénom</th><th>Canton</th><th>Allergies</th><th>Assurance</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_allergies as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['nom']) ?></strong></td>
                                    <td><?= h($r['prenom']) ?></td>
                                    <td><?= h($r['canton']) ?></td>
                                    <td><span class="badge alerte"><?= h($r['allergies']) ?></span></td>
                                    <td><?= h($r['assurance']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Performance par pharmacien</h2>
            <?php if ($rapport_pharmaciens === null): ?>
                <div class="alert erreur">Accès refusé.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Nom</th><th>Prénom</th><th>Rôle</th><th>Pharmacie</th><th>Délivrées</th><th>CA généré</th></tr></thead>
                        <tbody>
                            <?php foreach ($rapport_pharmaciens as $r): ?>
                                <tr>
                                    <td><strong><?= h($r['nom']) ?></strong></td>
                                    <td><?= h($r['prenom']) ?></td>
                                    <td><span class="badge <?= strtolower($r['role']) ?>"><?= h($r['role']) ?></span></td>
                                    <td><?= h($r['nom_pharmacie']) ?></td>
                                    <td><?= h($r['nb_delivrees']) ?></td>
                                    <td style="text-align:right;"><?= number_format($r['ca_genere'] ?? 0, 2, '.', "'") ?> CHF</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer>PharmaSwiss - École Schulz</footer>
</body>
</html>
