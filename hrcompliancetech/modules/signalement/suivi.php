<?php
/**
 * suivi.php
 * Espace de suivi du salarié accès par code de suivi unique.
 * Permet de consulter l'état du dossier et d'envoyer un message anonyme.
 * Aucun compte utilisateur requis.
 */

// session démarrée par config.php
require_once __DIR__ . '/../../config/config.php';

$pdo        = getDB();
$erreur     = '';
$dossier    = null;
$messages   = [];
$messageOk  = false;

$ref = filter_input(INPUT_GET,  'ref',  FILTER_SANITIZE_SPECIAL_CHARS)
    ?? filter_input(INPUT_POST, 'code', FILTER_SANITIZE_SPECIAL_CHARS)
    ?? '';

// Normalisation : majuscules + trim
$ref = strtoupper(trim($ref));

// -- Traitement POST : envoi d'un message salarié --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($ref)) {

    $action  = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $contenu = trim(filter_input(INPUT_POST, 'contenu', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    if ($action === 'envoyer_message') {
        if (mb_strlen($contenu) < 2) {
            $erreur = 'Le message ne peut pas être vide.';
        } else {
            // Récupération de l'ID du signalement à partir du code
            $stmt = $pdo->prepare(
                'SELECT id FROM signalements WHERE code_suivi = :ref LIMIT 1'
            );
            $stmt->execute([':ref' => $ref]);
            $row = $stmt->fetch();

            if ($row) {
                $stmt = $pdo->prepare(
                    'INSERT INTO messages (signalement_id, auteur_type, auteur_nom, contenu)
                     VALUES (:sid, :type, NULL, :contenu)'
                );
                $stmt->execute([
                    ':sid'     => $row['id'],
                    ':type'    => 'salarie',
                    ':contenu' => $contenu,
                ]);
                $messageOk = true;
            }
        }
    }
}

// -- Récupération du dossier -----------------------------------------------
if (!empty($ref)) {
    $stmt = $pdo->prepare(
        'SELECT id, code_suivi, categorie, statut, date_creation, date_mise_a_jour
         FROM signalements
         WHERE code_suivi = :ref
         LIMIT 1'
    );
    $stmt->execute([':ref' => $ref]);
    $dossier = $stmt->fetch();

    if ($dossier) {
        $stmt = $pdo->prepare(
            'SELECT auteur_type, auteur_nom, contenu, date_envoi
             FROM messages
             WHERE signalement_id = :id
             ORDER BY date_envoi ASC'
        );
        $stmt->execute([':id' => $dossier['id']]);
        $messages = $stmt->fetchAll();
    } else {
        $erreur = 'Aucun dossier trouvé pour ce code. Vérifiez votre saisie.';
    }
}

$libStatuts = [
    'OUVERT'         => 'Ouvert en attente de prise en charge',
    'EN_COURS'       => 'En cours d\'instruction',
    'ATTENTE_INFO'   => 'En attente d\'informations complémentaires',
    'CLOS_FONDE'     => 'Clôturé signalement fondé',
    'CLOS_NON_FONDE' => 'Clôturé signalement non fondé',
];
$libCategories = [
    'harcelement'    => 'Harcèlement',
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
    <title>Suivi de signalement HRComplianceTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="connexion-body">

<nav class="navbar navbar-expand-md site-navbar" aria-label="Navigation principale">
    <div class="container">
        <a class="navbar-brand brand" href="../../index.html">
            <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
        </a>
    </div>
</nav>

<main class="dashboard-main">
<div class="container">

    <?php if (!$dossier): ?>
    <!-- ============================================================
         FORMULAIRE DE SAISIE DU CODE (aucun dossier chargé)
         ============================================================ -->
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">

            <div class="connexion-entete text-center">
                <h1 class="connexion-titre">Suivre mon signalement</h1>
                <p class="connexion-sous-titre">
                    Saisissez votre code unique pour accéder à votre dossier.
                </p>
            </div>

            <div class="connexion-carte">
                <form method="GET" action="../../modules/signalement/suivi.php" novalidate>

                    <?php if ($erreur): ?>
                        <p class="erreur-connexion" role="alert"><?= htmlspecialchars($erreur) ?></p>
                    <?php endif; ?>

                    <div class="champ-groupe">
                        <label for="ref" class="form-label">Code de suivi</label>
                        <input
                            type="text"
                            id="ref"
                            name="ref"
                            class="form-control custom-input code-suivi-input"
                            placeholder="HRC-XXXXXX"
                            value="<?= htmlspecialchars($ref) ?>"
                            autocomplete="off"
                            spellcheck="false"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit btn-connexion-submit">
                        Accéder à mon dossier
                    </button>

                </form>
            </div>

        </div>
    </div>

    <?php else: ?>
    <!-- ============================================================
         VUE DU DOSSIER
         ============================================================ -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <a href="../../modules/signalement/suivi.php" class="lien-retour">&larr; Accéder à un autre dossier</a>

            <header class="dossier-entete">
                <div>
                    <p class="dashboard-sous-titre">Votre dossier</p>
                    <h1 class="dossier-titre"><?= htmlspecialchars($dossier['code_suivi']) ?></h1>
                </div>
            </header>

            <!-- Statut actuel -->
            <section class="dossier-section">
                <h2 class="dossier-section-titre">État de votre dossier</h2>
                <dl class="infos-liste">
                    <div class="infos-ligne">
                        <dt class="infos-cle">Catégorie</dt>
                        <dd class="infos-valeur">
                            <?= htmlspecialchars($libCategories[$dossier['categorie']] ?? $dossier['categorie']) ?>
                        </dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Statut actuel</dt>
                        <dd class="infos-valeur">
                            <?= htmlspecialchars($libStatuts[$dossier['statut']] ?? $dossier['statut']) ?>
                        </dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Déposé le</dt>
                        <dd class="infos-valeur">
                            <?= date('d/m/Y', strtotime($dossier['date_creation'])) ?>
                        </dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Dernière mise à jour</dt>
                        <dd class="infos-valeur">
                            <?= date('d/m/Y à H:i', strtotime($dossier['date_mise_a_jour'])) ?>
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Messagerie anonyme -->
            <section class="dossier-section">
                <h2 class="dossier-section-titre">Échanges confidentiels</h2>

                <?php if ($messageOk): ?>
                    <p class="message-confirmation" role="status">Votre message a bien été envoyé.</p>
                <?php endif; ?>
                <?php if ($erreur): ?>
                    <p class="champ-erreur visible" role="alert"><?= htmlspecialchars($erreur) ?></p>
                <?php endif; ?>

                <div class="messagerie-zone">
                    <?php if (empty($messages)): ?>
                        <p style="color:var(--gris-texte); font-style:italic;">
                            Aucun échange pour l'instant. Les services RH vous répondront ici.
                        </p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <!--
                            Inversion de perspective : côté salarié,
                            "message-recu" = message du staff,
                            "message-envoye" = message du salarié lui-même.
                        -->
                        <div class="message <?= $msg['auteur_type'] === 'staff' ? 'message-recu' : 'message-envoye' ?>">
                            <p class="message-auteur">
                                <?= $msg['auteur_type'] === 'staff'
                                    ? htmlspecialchars($msg['auteur_nom'] ?? 'Services RH')
                                    : 'Vous'
                                ?>
                            </p>
                            <p><?= nl2br(htmlspecialchars($msg['contenu'])) ?></p>
                            <time class="message-heure" datetime="<?= htmlspecialchars($msg['date_envoi']) ?>">
                                <?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?>
                            </time>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])): ?>
                <form method="POST" action="suivi.php?ref=<?= urlencode($ref) ?>" style="margin-top:1rem;">
                    <input type="hidden" name="action" value="envoyer_message">
                    <input type="hidden" name="code"   value="<?= htmlspecialchars($ref) ?>">
                    <div class="champ-groupe">
                        <label for="contenu-salarie" class="form-label">Votre message</label>
                        <textarea
                            id="contenu-salarie"
                            name="contenu"
                            class="form-control custom-input"
                            rows="3"
                            placeholder="Écrivez votre message ici..."
                            required
                        ></textarea>
                    </div>
                    <button type="submit" class="btn-submit btn-connexion-submit" style="margin-top:0.5rem;">
                        Envoyer
                    </button>
                </form>
                <?php else: ?>
                    <p style="font-style:italic; color:var(--gris-texte); font-size:0.88rem; margin-top:1rem;">
                        Ce dossier est clôturé. La messagerie est désactivée.
                    </p>
                <?php endif; ?>

            </section>

        </div>
    </div>
    <?php endif; ?>

</div>
</main>

<footer class="site-footer">
    <div class="container text-center py-3">
        <small>HRComplianceTech &copy; 2025 &mdash; Données chiffrées Serveur européen</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>