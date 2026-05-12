<?php
require_once 'connexion.php';
exiger_authentification();

$pdo = get_pdo();
$role = get_role_utilisateur();

$message = '';
$type_message = '';
$resultats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'rechercher_patient') {
        $recherche = trim($_POST['recherche'] ?? '');
        if (empty($recherche)) {
            $message = 'Veuillez saisir un terme de recherche.';
            $type_message = 'erreur';
        } elseif (strlen($recherche) < 2) {
            $message = 'La recherche doit contenir au moins 2 caractères.';
            $type_message = 'erreur';
        } elseif (strlen($recherche) > 100) {
            $message = 'Recherche trop longue (max 100 caractères).';
            $type_message = 'erreur';
        } else {
            try {
                $sql = "SELECT patient_id, nom, prenom, email, canton, assurance, allergies
                        FROM Patients
                        WHERE nom LIKE :recherche
                           OR prenom LIKE :recherche
                           OR email LIKE :recherche
                        ORDER BY nom, prenom
                        LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['recherche' => '%' . $recherche . '%']);
                $resultats = $stmt->fetchAll();
                if (empty($resultats)) {
                    $message = 'Aucun patient trouvé pour : ' . h($recherche);
                    $type_message = 'info';
                } else {
                    $message = count($resultats) . ' résultat(s) trouvé(s).';
                    $type_message = 'succes';
                }
            } catch (PDOException $e) {
                $message = 'Vous n\'avez pas les droits pour effectuer cette recherche.';
                $type_message = 'erreur';
            }
        }
    }
}

$nb_pharmacies = '-';
$nb_medicaments_rupture = '-';
$nb_ordonnances_attente = '-';
$ca_mois = '-';

try { $nb_pharmacies = $pdo->query("SELECT COUNT(*) FROM Pharmacies")->fetchColumn(); } catch (PDOException $e) {}
try { $nb_medicaments_rupture = $pdo->query("SELECT COUNT(*) FROM Medicaments WHERE stock_actuel <= stock_minimum")->fetchColumn(); } catch (PDOException $e) {}
try { $nb_ordonnances_attente = $pdo->query("SELECT COUNT(*) FROM Ordonnances WHERE statut = 'En attente'")->fetchColumn(); } catch (PDOException $e) {}
try {
    $ca_mois = $pdo->query("SELECT SUM(montant_total) FROM Ordonnances WHERE statut = 'Délivrée' AND date_delivrance >= DATE_SUB((SELECT MAX(date_delivrance) FROM Ordonnances), INTERVAL 30 DAY)")->fetchColumn();
    $ca_mois = number_format($ca_mois ?? 0, 2, '.', "'");
} catch (PDOException $e) {}

$dernieres_ordonnances = [];
try {
    $stmt = $pdo->query("SELECT o.ordonnance_id, o.date_prescription, o.statut, o.montant_total,
                                p.nom, p.prenom, ph.nom_pharmacie
                         FROM Ordonnances o
                         JOIN Patients p ON o.patient_id = p.patient_id
                         JOIN Pharmacies ph ON o.pharmacie_id = ph.pharmacie_id
                         ORDER BY o.date_prescription DESC LIMIT 5");
    $dernieres_ordonnances = $stmt->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PharmaSwiss</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <div class="logo-icon">PS</div>
            <div><h1>PharmaSwiss</h1><p>Tableau de bord - Réseau de pharmacies</p></div>
        </div>
        <div class="user-info">
            <span>Connecté : <strong><?= h($_SESSION['db_user']) ?></strong></span>
            <span class="role-badge"><?= h($role) ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <nav>
        <a href="index.php" class="active">Dashboard</a>
        <a href="console.php">Console SQL</a>
        <a href="patients.php">Patients</a>
        <a href="medicaments.php">Médicaments</a>
        <a href="ordonnances.php">Ordonnances</a>
        <a href="rapports.php">Rapports</a>
    </nav>

    <main>
        <div class="stats">
            <div class="stat-card success">
                <div class="label">Pharmacies actives</div>
                <div class="value"><?= h($nb_pharmacies) ?></div>
            </div>
            <div class="stat-card critique">
                <div class="label">Médicaments en rupture</div>
                <div class="value"><?= h($nb_medicaments_rupture) ?></div>
                <div class="sub">Stock ≤ minimum</div>
            </div>
            <div class="stat-card warning">
                <div class="label">Ordonnances en attente</div>
                <div class="value"><?= h($nb_ordonnances_attente) ?></div>
            </div>
            <div class="stat-card info">
                <div class="label">CA 30 derniers jours (CHF)</div>
                <div class="value"><?= h($ca_mois) ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Rechercher un patient</h2>
            <?php if ($message): ?>
                <div class="alert <?= h($type_message) ?>"><?= h($message) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="rechercher_patient">
                <div class="form-row">
                    <div class="form-group" style="flex:3;">
                        <label for="recherche">Nom, prénom ou email</label>
                        <input type="text" id="recherche" name="recherche"
                               placeholder="Ex: Dupuis, Aubert, h.dupuis@email.ch..."
                               value="<?= h($_POST['recherche'] ?? '') ?>"
                               required minlength="2" maxlength="100">
                    </div>
                    <div class="form-group" style="flex:0;">
                        <button type="submit">Rechercher</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($resultats)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Canton</th><th>Assurance</th><th>Allergies</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultats as $r): ?>
                                <tr>
                                    <td><?= h($r['patient_id']) ?></td>
                                    <td><strong><?= h($r['nom']) ?></strong></td>
                                    <td><?= h($r['prenom']) ?></td>
                                    <td><?= h($r['email']) ?></td>
                                    <td><?= h($r['canton']) ?></td>
                                    <td><?= h($r['assurance']) ?></td>
                                    <td><?= $r['allergies'] ? '<span class="badge alerte">'.h($r['allergies']).'</span>' : '<span class="text-muted">aucune</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($dernieres_ordonnances)): ?>
            <div class="card">
                <h2>Dernières ordonnances</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Statut</th><th>Patient</th><th>Pharmacie</th><th>Montant (CHF)</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dernieres_ordonnances as $o): ?>
                                <tr>
                                    <td><?= h(date('d/m/Y', strtotime($o['date_prescription']))) ?></td>
                                    <td><span class="badge <?= strtolower(str_replace('é','e',$o['statut'])) ?>"><?= h($o['statut']) ?></span></td>
                                    <td><?= h($o['prenom'] . ' ' . $o['nom']) ?></td>
                                    <td><?= h($o['nom_pharmacie']) ?></td>
                                    <td><strong><?= number_format($o['montant_total'] ?? 0, 2, '.', "'") ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Comment travailler sur cette plateforme</h2>
            <p class="text-muted" style="line-height:1.8;">
                <strong>•</strong> La plateforme est livrée clé en main : <a href="patients.php" style="color:#1e6b47; font-weight:600;">Patients</a>, <a href="medicaments.php" style="color:#1e6b47; font-weight:600;">Médicaments</a>, <a href="ordonnances.php" style="color:#1e6b47; font-weight:600;">Ordonnances</a> et <a href="rapports.php" style="color:#1e6b47; font-weight:600;">Rapports</a> sont fonctionnels.<br>
                <strong>•</strong> Vos exercices se concentrent sur l'écriture de <strong>requêtes SQL</strong> dans la <a href="console.php" style="color:#1e6b47; font-weight:600;">Console SQL</a>.<br>
                <strong>•</strong> Vos droits dans la console correspondent à vos permissions GRANT MySQL.<br>
                <strong>•</strong> Testez les pages avec différents comptes pour observer les différences de droits.
            </p>
        </div>
    </main>

    <footer>PharmaSwiss Demo - École Schulz - BTEC LO2/LO3</footer>
</body>
</html>
