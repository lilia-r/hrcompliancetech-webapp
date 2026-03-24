<?php
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/connexion.html');
    exit;
}

$message = '';

// Traitement du formulaire (les valeurs sont affichées mais ne modifient pas la BDD
// dans ce prototype l'action est tracée dans le journal d'audit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conservation = (int) ($_POST['duree_conservation'] ?? 24);
    $tailleMax    = (int) ($_POST['taille_max_pj']     ?? 5);
    $expiSession  = (int) ($_POST['expiration_session'] ?? 60);

    logAudit('MODIFICATION_PARAMETRES',
        'Conservation: ' . $conservation . ' mois | PJ max: ' . $tailleMax . ' Mo | Session: ' . $expiSession . ' min'
    );

    $message = 'Paramètres enregistrés et tracés dans le journal d\'audit.';
}

logAudit('ACCES_PARAMETRES', 'Accès à la page des paramètres de sécurité');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres de sécurité HRComplianceTech</title>
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
        <h1 class="dashboard-titre">Paramètres de sécurité</h1>
        <p class="dashboard-sous-titre">Toute modification est enregistrée dans le journal d'audit.</p>
    </header>

    <?php if ($message): ?>
    <div role="alert" style="background:#eef7ee;border:1px solid #1a6b3a;color:#1a6b3a;padding:0.75rem 1rem;border-radius:6px;margin-bottom:1.5rem;">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <section class="admin-section">
        <form method="POST" action="parametres.php">
            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <div class="admin-carte">
                        <h3 class="admin-carte-titre">Conservation des données</h3>
                        <p class="admin-carte-texte">
                            Durée maximale de conservation des signalements après clôture.
                            La loi Sapin 2 recommande une conservation limitée dans le temps.
                        </p>
                        <label for="duree_conservation" class="form-label filtre-label">
                            Durée de conservation (en mois après clôture)
                        </label>
                        <input
                            type="number"
                            id="duree_conservation"
                            name="duree_conservation"
                            class="form-control custom-input"
                            value="24"
                            min="6"
                            max="60"
                            style="max-width:120px;"
                        >
                        <small style="color:var(--gris-texte);display:block;margin-top:0.4rem;">
                            Valeur par défaut : 24 mois (2 ans). Plage autorisée : 6 à 60 mois.
                        </small>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="admin-carte">
                        <h3 class="admin-carte-titre">Pièces jointes</h3>
                        <p class="admin-carte-texte">
                            Taille maximale autorisée par fichier joint à un signalement.
                            Formats acceptés : PDF, JPG, PNG, MP3.
                        </p>
                        <label for="taille_max_pj" class="form-label filtre-label">
                            Taille maximale par fichier (en Mo)
                        </label>
                        <input
                            type="number"
                            id="taille_max_pj"
                            name="taille_max_pj"
                            class="form-control custom-input"
                            value="5"
                            min="1"
                            max="20"
                            style="max-width:120px;"
                        >
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="admin-carte">
                        <h3 class="admin-carte-titre">Sessions utilisateurs</h3>
                        <p class="admin-carte-texte">
                            Durée d'inactivité avant expiration automatique de la session.
                            Recommandé : 30 à 60 minutes pour les profils RH et juristes.
                        </p>
                        <label for="expiration_session" class="form-label filtre-label">
                            Délai d'expiration (en minutes)
                        </label>
                        <input
                            type="number"
                            id="expiration_session"
                            name="expiration_session"
                            class="form-control custom-input"
                            value="60"
                            min="15"
                            max="480"
                            style="max-width:120px;"
                        >
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="admin-carte">
                        <h3 class="admin-carte-titre">Politique de mots de passe</h3>
                        <p class="admin-carte-texte">
                            Règles actuellement appliquées à la création de tous les comptes.
                        </p>
                        <ul style="font-size:0.9rem;color:var(--gris-texte);padding-left:1.2rem;margin:0;">
                            <li>Longueur minimale : 8 caractères</li>
                            <li>Algorithme de hachage : BCRYPT (coût 10)</li>
                            <li>Aucun mot de passe en clair stocké en base</li>
                        </ul>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn-submit" style="padding:0.6rem 2rem;">
                    Enregistrer les paramètres
                </button>
            </div>
        </form>
    </section>

</div>
</main>

<footer class="site-footer">
    <div class="container-fluid px-4 text-center py-3">
        <small>HRComplianceTech &copy; 2026 Conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>