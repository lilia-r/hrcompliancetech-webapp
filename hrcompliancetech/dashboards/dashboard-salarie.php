<?php
/**
 * dashboard-salarie.php
 * Tableau de bord du salarié connecté.
 * Affiche ses propres signalements récupérés depuis la BDD.
 */

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'salarie') {
    header('Location: ../auth/connexion.html');
    exit;
}

$pdo = getDB();

// Recuperation des signalements lies a ce compte
$stmt = $pdo->prepare(
    'SELECT code_suivi, categorie, statut, date_creation
     FROM signalements
     WHERE utilisateur_id = :uid
     ORDER BY date_creation DESC'
);
$stmt->execute([':uid' => $_SESSION['utilisateur_id']]);
$signalements = $stmt->fetchAll();

logAudit('CONSULTATION_TABLEAU_BORD', 'Acces au tableau de bord salarie');

$libStatuts = [
    'OUVERT'         => 'Ouvert',
    'EN_COURS'       => 'En cours',
    'ATTENTE_INFO'   => "En attente d'information",
    'CLOS_FONDE'     => 'Clôturé - fondé',
    'CLOS_NON_FONDE' => 'Clôturé - non fondé',
];

$libCategories = [
    'harcelement'    => 'Harcèlement moral ou sexuel',
    'discrimination' => 'Discrimination',
    'fraude'         => 'Fraude',
    'environnement'  => 'Atteinte à l\'environnement',
    'ethique'        => 'Atteinte à l\'éthique',
    'autre'          => 'Autre',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRComplianceTech - Mon espace sécurisé</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">

    <nav class="navbar site-navbar" aria-label="Navigation principale">
        <div class="container-fluid px-4">
            <a class="navbar-brand brand" href="../index.html">
                <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="../modules/signalement/signalement.php" class="nav-link nav-link-custom">Déposer un signalement</a>
                <a href="../dashboards/dashboard-salarie.php" class="nav-link nav-link-custom">Tableau de bord</a>
                <a href="../includes/deconnexion.php" class="nav-link nav-link-connexion">Deconnexion</a>
            </div>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="container-fluid px-4">

            <header class="dashboard-entete">
                <div>
                    <p class="dashboard-sous-titre" style="margin-bottom:0.25rem;">Mon espace sécurisé</p>
                    <h1 class="dashboard-titre">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></h1>
                </div>
                <p class="dashboard-compteur" aria-live="polite">
                    <?= count($signalements) ?> signalement(s) enregistré(s)
                </p>
            </header>

            <!-- Bouton dépôt -->
            <section class="salarie-action-section">
                <div class="salarie-action-carte">
                    <div class="salarie-action-contenu">
                        <h2 class="salarie-action-titre">Vous avez été témoin ou victime d'un incident ?</h2>
                        <p class="salarie-action-texte">
                            Déposez un signalement de manière <strong>sécurisée et confidentielle</strong>,
                            avec ou sans révéler votre identité.
                        </p>
                    </div>
                    <div class="salarie-action-bouton-zone">
                        <a href="../modules/signalement/signalement.php"
                           class="btn-submit btn-connexion-submit salarie-btn-action"
                           role="button">
                            Déposer un nouveau signalement
                        </a>
                    </div>
                </div>
            </section>

            <!-- Tableau des dossiers -->
            <section class="salarie-historique-section">
                <h2 class="dashboard-section-titre">Mes dossiers</h2>

                <div class="tableau-bloc">
                    <table class="table-signalements">
                        <thead>
                            <tr>
                                <th scope="col">Référence</th>
                                <th scope="col">Date de dépôt</th>
                                <th scope="col">Catégorie</th>
                                <th scope="col">Statut</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($signalements)): ?>
                            <tr>
                                <td colspan="5" class="text-center" style="padding:2rem; color:var(--gris-texte); font-style:italic;">
                                    Vous n'avez déposé aucun signalement pour le moment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($signalements as $s): ?>
                            <tr>
                                <td class="cellule-ref"><?= htmlspecialchars($s['code_suivi']) ?></td>
                                <td>
                                    <time datetime="<?= htmlspecialchars($s['date_creation']) ?>">
                                        <?= date('d/m/Y', strtotime($s['date_creation'])) ?>
                                    </time>
                                </td>
                                <td><?= htmlspecialchars($libCategories[$s['categorie']] ?? $s['categorie']) ?></td>
                                <td>
                                    <span class="statut <?= classeStatut($s['statut']) ?>">
                                        <?= htmlspecialchars($libStatuts[$s['statut']] ?? $s['statut']) ?>
                                    </span>
                                </td>
                                <td style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                    <a href="../modules/dossier/dossier.php?ref=<?= urlencode($s['code_suivi']) ?>"
                                       class="btn-ouvrir">
                                        Voir le dossier
                                    </a>
                                    <a href="../modules/dossier/dossier.php?ref=<?= urlencode($s['code_suivi']) ?>#messagerie"
                                       class="btn-ouvrir" style="background:transparent; border:1.5px solid var(--bleu-marine); color:var(--bleu-marine);">
                                        Messagerie
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

    <footer class="site-footer">
        <div class="container-fluid px-4 text-center py-3">
            <small>HRComplianceTech &copy; 2026 &mdash; Solution conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>