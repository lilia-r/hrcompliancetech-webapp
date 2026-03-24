<?php
/**
 * confirmation.php
 * Page affichée après un dépôt de signalement réussi.
 * Accessible uniquement si la session salarié est active.
 */
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'salarie') {
    header('Location: ../../auth/connexion.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRComplianceTech - Signalement enregistré</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="connexion-body">

    <nav class="navbar site-navbar">
        <div class="container-fluid px-4">
            <a class="navbar-brand brand" href="../../index.html">
                <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
            </a>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">


                    <a href="../../dashboards/dashboard-salarie.php" class="lien-retour">&larr; Retour au tableau de bord</a>

                    <div style="background:var(--blanc); border:1px solid var(--gris-bordure); border-radius:4px; padding:2.5rem; text-align:center; margin-top:1rem;">

                        <div style="width:56px; height:56px; background:#e8f5e9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>

                        <h1 style="font-family:'Playfair Display', serif; font-size:1.6rem; color:var(--bleu-marine); margin-bottom:0.75rem;">
                            Signalement enregistré avec succès
                        </h1>
                        <p style="color:var(--gris-texte); line-height:1.7; margin-bottom:0;">
                            Votre dossier a été transmis de manière sécurisée aux équipes habilitées.
                            Vous pouvez suivre son avancement depuis votre tableau de bord.
                        </p>


                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container text-center py-3">
            <small>HRComplianceTech &copy; 2026 &mdash; Solution conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>