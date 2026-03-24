<?php
/**
 * dashboard-juriste.php
 * Tableau de bord Service juridique.
 * Accès reserve au role 'juriste'.
 */

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'juriste') {
    header('Location: ../auth/connexion.html');
    exit;
}

$pdo = getDB();

$pdo = getDB();

$triDate = filter_input(INPUT_GET, 'tri_date', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$orderBy = match($triDate) {
    'asc'  => 'date_creation ASC',
    'desc' => 'date_creation DESC',
    default => 'FIELD(priorite, "haute", "normale", "basse"), date_creation DESC',
};

$stmt = $pdo->query('
    SELECT id, code_suivi, categorie, statut, priorite,
           date_creation, masquer_identite,
           nom_declarant, prenom_declarant, email_declarant
    FROM signalements
    ORDER BY ' . $orderBy
);
$signalements = $stmt->fetchAll();

logAudit('CONSULTATION_TABLEAU_BORD', 'Acces au tableau de bord juridique');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRComplianceTech - Tableau de bord juridique</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">

    <nav class="navbar site-navbar" aria-label="Navigation principale">
        <div class="container-fluid px-4">
            <a class="navbar-brand brand" href="../dashboards/dashboard-juriste.php">
                <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="../dashboards/dashboard-juriste.php" class="nav-link nav-link-custom">Tableau de bord</a>
                <a href="../includes/deconnexion.php" class="nav-link nav-link-connexion">Deconnexion</a>
            </div>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="container-fluid px-4">

            <header class="dashboard-entete">
                <div>
                    <p class="dashboard-sous-titre" style="margin-bottom:0.25rem;">Mon espace juridique</p>
                    <h1 class="dashboard-titre">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></h1>
                </div>
                <p class="dashboard-compteur" id="compteur-resultats" aria-live="polite">
                    <?= count($signalements) ?> signalement(s) affiché(s)
                </p>
            </header>

            <!-- Barre de filtres côté client (JS) -->
            <div class="filtres-barre">

                <div class="filtre-groupe">
                    <label for="filtre-statut" class="filtre-label">Statut</label>
                    <select id="filtre-statut" class="filtre-select">
                        <option value="">Tous</option>
                        <option value="OUVERT">Ouvert</option>
                        <option value="EN_COURS">En cours</option>
                        <option value="ATTENTE_INFO">En attente</option>
                        <option value="CLOS_FONDE">Clos fonde</option>
                        <option value="CLOS_NON_FONDE">Clos non fonde</option>
                    </select>
                </div>

                <div class="filtre-groupe">
                    <label for="filtre-priorite" class="filtre-label">Priorité</label>
                    <select id="filtre-priorite" class="filtre-select">
                        <option value="">Toutes</option>
                        <option value="haute">Haute</option>
                        <option value="normale">Normale</option>
                        <option value="basse">Basse</option>
                    </select>
                </div>

                <div class="filtre-groupe">
                    <label for="filtre-categorie" class="filtre-label">Catégorie</label>
                    <select id="filtre-categorie" class="filtre-select">
                        <option value="">Toutes</option>
                        <option value="harcelement">Harcelement</option>
                        <option value="discrimination">Discrimination</option>
                        <option value="fraude">Fraude</option>
                        <option value="environnement">Environnement</option>
                        <option value="ethique">Ethique</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>

                <div class="filtre-groupe">
                    <label for="filtre-tri-date" class="filtre-label">Trier par date</label>
                    <select id="filtre-tri-date" class="filtre-select"
                            onchange="window.location.href='dashboard-juriste.php?tri_date='+this.value">
                        <option value="">Par défaut</option>
                        <option value="asc" <?= (($_GET['tri_date'] ?? '') === 'asc')  ? 'selected' : '' ?>>Plus ancien d'abord</option>
                        <option value="desc" <?= (($_GET['tri_date'] ?? '') === 'desc') ? 'selected' : '' ?>>Plus récent d'abord</option>
                    </select>
                </div>

                <div class="filtre-actions">
                    <button id="btn-reinit-filtres" class="btn-reinit">Réinitialiser</button>
                </div>

            </div>
            <p id="message-vide" class="cache" style="color:var(--gris-texte); font-style:italic; margin-top:1rem;">
                Aucun dossier ne correspond aux criteres selectionnes.
            </p>

            <section>
                <h2 class="dashboard-section-titre">Liste des signalements</h2>

                <div class="tableau-bloc">
                    <table class="table-signalements" id="tableau-signalements" aria-label="Liste des signalements">
                        <thead>
                            <tr>
                                <th scope="col">Référence</th>
                                <th scope="col">Catégorie</th>
                                <th scope="col" class="col-declarant">Déclarant</th>
                                <th scope="col">Statut</th>
                                <th scope="col">Priorité</th>
                                <th scope="col" class="col-date">Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if (empty($signalements)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding:2rem;color:var(--gris-texte);">
                                    Aucun signalement enregistré pour le moment.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($signalements as $s): ?>
                            <tr class="<?= $s['priorite'] === 'haute' ? 'ligne-haute' : 'ligne-normale' ?>"
                                data-statut="<?= htmlspecialchars($s['statut']) ?>"
                                data-priorite="<?= htmlspecialchars($s['priorite']) ?>"
                                data-categorie="<?= htmlspecialchars($s['categorie']) ?>"
                                data-date="<?= htmlspecialchars($s['date_creation']) ?>">

                                <td class="cellule-ref">
                                    <?= htmlspecialchars($s['code_suivi']) ?>
                                </td>

                                <td><?= libelleCategorie($s['categorie']) ?></td>

                                <td class="col-declarant">
                                    <?php if ((int) $s['masquer_identite'] === 1): ?>
                                        <span class="identite-protegee">Identité protégée</span>
                                    <?php else: ?>
                                        <?php
                                            $nomComplet = trim(
                                                ($s['prenom_declarant'] ?? '') . ' ' .
                                                ($s['nom_declarant']    ?? '')
                                            );
                                        ?>
                                        <?= $nomComplet
                                            ? htmlspecialchars($nomComplet)
                                            : '<em style="color:var(--gris-texte)">Non renseigne</em>'
                                        ?>
                                        <?php if (!empty($s['email_declarant'])): ?>
                                            <br><small><?= htmlspecialchars($s['email_declarant']) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="statut <?= classeStatut($s['statut']) ?>">
                                        <?= libelleStatut($s['statut']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="priorite <?= classePriorite($s['priorite']) ?>">
                                        <?= libellePriorite($s['priorite']) ?>
                                    </span>
                                </td>

                                <td class="col-date">
                                    <time datetime="<?= htmlspecialchars($s['date_creation']) ?>">
                                        <?= dateFr($s['date_creation']) ?>
                                    </time>
                                </td>

                                <td>
                                    <a href="../modules/dossier/dossier.php?ref=<?= urlencode($s['code_suivi']) ?>"
                                       class="btn-ouvrir">
                                        Ouvrir
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
        <div class="container text-center py-3">
            <small>HRComplianceTech &copy; 2026 &mdash; Solution conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>