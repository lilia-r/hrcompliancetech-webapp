<?php
/**
 * admin.php
 * Console d'administration accès réservé au rôle 'admin'.
 */
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/connexion.html');
    exit;
}

$pdo = getDB();
$messageRetour = '';

// --- Traitement suspension / réactivation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_statut') {
    $uid     = (int) ($_POST['uid'] ?? 0);
    $actif   = (int) ($_POST['actif'] ?? 0);
    $nouveau = $actif === 1 ? 0 : 1;

    // L'admin ne peut pas se suspendre lui-même
    if ($uid !== (int) $_SESSION['utilisateur_id'] && $uid > 0) {
        $upd = $pdo->prepare('UPDATE utilisateurs SET est_actif = :actif WHERE id = :id');
        $upd->execute([':actif' => $nouveau, ':id' => $uid]);
        $action = $nouveau === 1 ? 'REACTIVATION_COMPTE' : 'SUSPENSION_COMPTE';
        logAudit($action, 'Utilisateur ID : ' . $uid);
        $messageRetour = 'Statut mis à jour.';
    }
}

// --- Chargement des utilisateurs ---
$utilisateurs = $pdo->query(
    'SELECT id, nom, prenom, email, role, est_actif
     FROM utilisateurs
     ORDER BY role, nom'
)->fetchAll();

logAudit('ACCES_ADMIN', 'Accès à la console d\'administration');

$libelleRole = ['admin' => 'Administrateur', 'rh' => 'RH', 'juriste' => 'Juriste', 'salarie' => 'Salarié'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRComplianceTech – Console d'administration technique</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">

    <nav class="navbar navbar-expand-md site-navbar" aria-label="Navigation principale">
        <div class="container-fluid px-4">
            <a class="navbar-brand brand" href="../dashboards/admin.php" aria-label="Accueil HRComplianceTech">
                <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menu-admin" aria-controls="menu-admin"
                aria-expanded="false" aria-label="Ouvrir le menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu-admin">
                <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom active" href="../dashboards/admin.php" aria-current="page">
                            Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-connexion" href="../includes/deconnexion.php">
                            Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="container-fluid px-4">

            <header class="dashboard-entete">
                <div>
                    <p class="dashboard-sous-titre" style="margin-bottom:0.25rem;">Mon espace administrateur</p>
                    <h1 class="dashboard-titre">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></h1>
                </div>
            </header>

            <?php if ($messageRetour): ?>
            <div style="background:#eef7ee;border:1px solid #1a6b3a;color:#1a6b3a;padding:0.75rem 1rem;border-radius:6px;margin-bottom:1rem;">
                <?= htmlspecialchars($messageRetour) ?>
            </div>
            <?php endif; ?>

            <!-- SECTION : SÉCURITÉ ET AUDIT -->
            <section aria-labelledby="titre-audit-admin" class="admin-section">
                <h2 class="admin-section-titre" id="titre-audit-admin">Sécurité et audit</h2>

                <div class="row g-4">

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="admin-carte d-flex flex-column">
                            <h3 class="admin-carte-titre">Journal d'audit</h3>
                            <p class="admin-carte-texte flex-grow-1">
                                Historique inaltérable de toutes les actions réalisées sur
                                la plateforme : connexions, consultations, modifications de
                                statut, clôtures et exports.
                            </p>
                            <a href="../modules/audit/logs-audit.php" class="btn-ouvrir mt-auto w-100 text-center">
                                Voir les journaux d'audit
                            </a>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="admin-carte d-flex flex-column">
                            <h3 class="admin-carte-titre">Sauvegardes et intégrité</h3>
                            <p class="admin-carte-texte flex-grow-1">
                                Supervision des sauvegardes automatiques de la base de données.
                                Vérification de l'intégrité des fichiers et des journaux.
                            </p>
                            <a href="../modules/admin/sauvegardes.php" class="btn-ouvrir mt-auto w-100 text-center">
                                Consulter les sauvegardes
                            </a>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="admin-carte d-flex flex-column">
                            <h3 class="admin-carte-titre">Paramètres de sécurité</h3>
                            <p class="admin-carte-texte flex-grow-1">
                                Durée de conservation des données, politique de mots de passe,
                                délai d'expiration des sessions, et règles de filtrage des pièces jointes.
                            </p>
                            <a href="../modules/admin/parametres.php" class="btn-ouvrir mt-auto w-100 text-center">
                                Modifier les paramètres
                            </a>
                        </div>
                    </div>

                </div>
            </section>

            <!-- SECTION : GESTION DES UTILISATEURS -->
            <section aria-labelledby="titre-utilisateurs" class="admin-section">

                <div class="admin-section-entete">
                    <h2 class="admin-section-titre" id="titre-utilisateurs">Gestion des utilisateurs</h2>
                </div>

                <!-- Tableau des utilisateurs depuis la BDD -->
                <div class="tableau-bloc" style="margin-top:1rem;">
                    <div class="table-responsive">
                        <table class="table-signalements" id="tableau-utilisateurs">
                            <thead>
                                <tr>
                                    <th scope="col">Nom</th>
                                    <th scope="col">Adresse e-mail</th>
                                    <th scope="col">Rôle</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($utilisateurs as $u): ?>
                                <tr class="<?= $u['est_actif'] ? '' : 'ligne-compte-suspendu' ?>">
                                    <td class="log-utilisateur">
                                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="log-role">
                                        <?= htmlspecialchars($libelleRole[$u['role']] ?? $u['role']) ?>
                                    </td>
                                    <td>
                                        <?php if ($u['est_actif']): ?>
                                            <span class="statut-compte statut-compte-actif">Actif</span>
                                        <?php else: ?>
                                            <span class="statut-compte statut-compte-suspendu">Suspendu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$u['id'] !== (int)$_SESSION['utilisateur_id']): ?>
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_statut">
                                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="actif" value="<?= $u['est_actif'] ?>">
                                            <button type="submit" class="btn-action-table <?= $u['est_actif'] ? 'btn-action-suspendre' : 'btn-action-reactiver' ?>">
                                                <?= $u['est_actif'] ? 'Suspendre' : 'Réactiver' ?>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span style="color:var(--gris-texte);font-size:0.8rem;">Votre compte</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

        </div>
    </main>

    <footer class="site-footer">
        <div class="container-fluid px-4 text-center py-3">
            <small>HRComplianceTech &copy; 2026 Conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>

</body>
</html>