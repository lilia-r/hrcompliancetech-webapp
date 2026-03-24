<?php
/**
 * logs-audit.php
 * Journal d'audit accès réservé à l'administrateur technique.
 * Affiche toutes les actions enregistrées avec filtrage.
 */

// session démarrée par config.php
require_once __DIR__ . '/../../config/config.php';

// Accès exclusif à l'admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/connexion.html');
    exit;
}

$pdo = getDB();

// Filtres GET
$filtreAction = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$filtreDate   = filter_input(INPUT_GET, 'date',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// Construction dynamique de la requête avec les filtres
$conditions = [];
$params     = [];

if (!empty($filtreAction)) {
    $conditions[] = 'l.action = :action';
    $params[':action'] = $filtreAction;
}

if (!empty($filtreDate)) {
    $conditions[] = 'DATE(l.date_log) = :date';
    $params[':date'] = $filtreDate;
}

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "
    SELECT
        l.id,
        l.action,
        l.details,
        l.adresse_ip,
        l.date_log,
        u.nom        AS user_nom,
        u.prenom     AS user_prenom,
        u.role       AS user_role
    FROM logs_audit l
    LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id
    $where
    ORDER BY l.date_log DESC
    LIMIT 500
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Liste des types d'actions pour le filtre
$stmt2    = $pdo->query('SELECT DISTINCT action FROM logs_audit ORDER BY action');
$actions  = $stmt2->fetchAll(PDO::FETCH_COLUMN);

logAudit('CONSULTATION_LOGS', 'Accès au journal d\'audit');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'audit HRComplianceTech</title>
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
        <h1 class="dashboard-titre">Journal d'audit</h1>
        <p class="dashboard-sous-titre"><?= count($logs) ?> entrée(s) affichée(s)</p>
    </header>

    <!-- Filtres -->
    <form method="GET" action="logs-audit.php" class="filtres-bloc">
        <div class="d-flex flex-wrap gap-3 align-items-end">

            <div>
                <label for="f-action" class="filtre-label">Type d'action</label>
                <select id="f-action" name="action" class="filtre-select">
                    <option value="">Toutes</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>"
                            <?= $filtreAction === $a ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="f-date" class="filtre-label">Date</label>
                <input
                    type="date"
                    id="f-date"
                    name="date"
                    class="form-control custom-input"
                    value="<?= htmlspecialchars($filtreDate) ?>"
                    style="max-width:160px;"
                >
            </div>

            <button type="submit" class="btn-ouvrir">Filtrer</button>
            <a href="logs-audit.php" class="btn-reinit">Réinitialiser</a>
        </div>
    </form>

    <div class="tableau-bloc">
        <table class="table-signalements" aria-label="Journal d'audit">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Action</th>
                    <th scope="col">Utilisateur</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Détails</th>
                    <th scope="col">Adresse IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding:2rem;color:var(--gris-texte);">
                        Aucune entrée pour ces critères.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr class="log-<?= strtolower(explode('_', $log['action'])[0]) ?>">
                    <td class="log-date">
                        <time datetime="<?= htmlspecialchars($log['date_log']) ?>">
                            <?= date('d/m/Y H:i:s', strtotime($log['date_log'])) ?>
                        </time>
                    </td>
                    <td class="log-action"><?= htmlspecialchars($log['action']) ?></td>
                    <td class="log-utilisateur">
                        <?php if ($log['user_nom']): ?>
                            <?= htmlspecialchars($log['user_prenom'] . ' ' . $log['user_nom']) ?>
                        <?php else: ?>
                            <span class="log-role">Système</span>
                        <?php endif; ?>
                    </td>
                    <td class="log-role">
                        <?= $log['user_role'] ? htmlspecialchars(strtoupper($log['user_role'])) : ' ' ?>
                    </td>
                    <td><?= htmlspecialchars($log['details'] ?? ' ') ?></td>
                    <td class="log-ip"><?= htmlspecialchars($log['adresse_ip']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="log-note-inalterable">
        Ce journal est en lecture seule. Aucune entrée ne peut être modifiée ou supprimée.
        Conservation légale : 5 ans après la clôture des dossiers associés.
    </p>

</div>
</main>

<footer class="site-footer">
    <div class="container text-center py-3">
        <small>HRComplianceTech &copy; 2026 Conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>