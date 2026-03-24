<?php
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/connexion.html');
    exit;
}

$pdo = getDB();

// Statistiques réelles depuis la BDD
$nbSignalements  = $pdo->query('SELECT COUNT(*) FROM signalements')->fetchColumn();
$nbUtilisateurs  = $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
$nbMessages      = $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$nbLogs          = $pdo->query('SELECT COUNT(*) FROM logs_audit')->fetchColumn();
$nbPJ            = $pdo->query('SELECT COUNT(*) FROM pieces_jointes')->fetchColumn();

$dernierLog = $pdo->query(
    'SELECT date_log FROM logs_audit ORDER BY date_log DESC LIMIT 1'
)->fetchColumn();

$dernierSignalement = $pdo->query(
    'SELECT date_creation FROM signalements ORDER BY date_creation DESC LIMIT 1'
)->fetchColumn();

logAudit('CONSULTATION_SAUVEGARDES', 'Accès à la page sauvegardes');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sauvegardes HRComplianceTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard-body">

<nav class="navbar site-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand brand" href="../../dashboards/admin.php">
            <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="../../dashboards/admin.php" class="nav-link nav-link-custom">Tableau de bord</a>
            <a href="../../includes/deconnexion.php" class="nav-link nav-link-connexion">Déconnexion</a>
        </div>
    </div>
</nav>

<main class="dashboard-main">
<div class="container-fluid px-4">

    <a href="../../dashboards/admin.php" class="lien-retour">&larr; Retour au tableau de bord</a>

    <header class="dashboard-entete">
        <h1 class="dashboard-titre">Sauvegardes et intégrité</h1>
        <p class="dashboard-sous-titre">État actuel de la base de données données en temps réel</p>
    </header>

    <section class="admin-section">
        <h2 class="admin-section-titre">Contenu de la base de données</h2>
        <div class="row g-4">

            <div class="col-12 col-md-4">
                <div class="admin-carte text-center">
                    <p class="admin-carte-titre" style="font-size:2rem;color:var(--bleu-marine);">
                        <?= $nbSignalements ?>
                    </p>
                    <p class="admin-carte-texte">Signalement(s) enregistré(s)</p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="admin-carte text-center">
                    <p class="admin-carte-titre" style="font-size:2rem;color:var(--bleu-marine);">
                        <?= $nbUtilisateurs ?>
                    </p>
                    <p class="admin-carte-texte">Utilisateur(s) enregistré(s)</p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="admin-carte text-center">
                    <p class="admin-carte-titre" style="font-size:2rem;color:var(--bleu-marine);">
                        <?= $nbMessages ?>
                    </p>
                    <p class="admin-carte-texte">Message(s) dans la messagerie anonyme</p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="admin-carte text-center">
                    <p class="admin-carte-titre" style="font-size:2rem;color:var(--bleu-marine);">
                        <?= $nbPJ ?>
                    </p>
                    <p class="admin-carte-texte">Pièce(s) jointe(s) stockée(s)</p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="admin-carte text-center">
                    <p class="admin-carte-titre" style="font-size:2rem;color:var(--bleu-marine);">
                        <?= $nbLogs ?>
                    </p>
                    <p class="admin-carte-texte">Entrée(s) dans le journal d'audit</p>
                </div>
            </div>

        </div>
    </section>

    <section class="admin-section">
        <h2 class="admin-section-titre">Dernières activités</h2>
        <div class="tableau-bloc">
            <table class="table-signalements">
                <thead>
                    <tr>
                        <th scope="col">Indicateur</th>
                        <th scope="col">Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dernière entrée de log</td>
                        <td><?= $dernierLog ? date('d/m/Y à H:i:s', strtotime($dernierLog)) : 'Aucune' ?></td>
                    </tr>
                    <tr>
                        <td>Dernier signalement déposé</td>
                        <td><?= $dernierSignalement ? date('d/m/Y à H:i:s', strtotime($dernierSignalement)) : 'Aucun' ?></td>
                    </tr>
                    <tr>
                        <td>Durée de conservation légale</td>
                        <td>2 ans après clôture du dossier (conformité Sapin 2)</td>
                    </tr>
                    <tr>
                        <td>Hébergement</td>
                        <td>Serveur local données sur territoire européen</td>
                    </tr>
                    <tr>
                        <td>Chiffrement des mots de passe</td>
                        <td>BCRYPT (coût 10)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</div>
</main>

<footer class="site-footer">
    <div class="container-fluid px-4 text-center py-3">
        <small>HRComplianceTech &copy; 2026 Conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>