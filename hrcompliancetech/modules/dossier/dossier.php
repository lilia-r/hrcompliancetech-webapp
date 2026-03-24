<?php
/**
 * dossier.php
 * Vue et traitement d'un dossier de signalement.
 * Accessible par les rôles RH et Juriste.
 * Gère : affichage du dossier, changement de statut, envoi de message.
 */

// session démarrée par config.php
require_once __DIR__ . '/../../config/config.php';

// Contrôle d'accès
if (!isset($_SESSION['utilisateur_id']) || !in_array($_SESSION['role'], ['rh', 'juriste', 'salarie'], true)) {
    header('Location: ../../auth/connexion.html');
    exit;
}

$pdo        = getDB();
$ref        = filter_input(INPUT_GET, 'ref', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$messageOk  = false;
$statutOk   = false;
$erreurMsg  = '';

$dashboardRetour = match($_SESSION['role']) {
    'juriste' => '../../dashboards/dashboard-juriste.php',
    'salarie' => '../../dashboards/dashboard-salarie.php',
    default   => '../../dashboards/dashboard-rh.php',
};

if (empty($ref)) {
    header('Location: ' . $dashboardRetour);
    exit;
}

// Récupération du dossier
$stmt = $pdo->prepare(
    'SELECT * FROM signalements WHERE code_suivi = :ref LIMIT 1'
);
$stmt->execute([':ref' => $ref]);
$dossier = $stmt->fetch();

if (!$dossier) {
    header('Location: ' . $dashboardRetour);
    exit;
}

$idSignalement = $dossier['id'];

// -- Traitement POST --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    // Action 0 : attribution à un responsable (RH uniquement)
    if ($action === 'attribuer_responsable' && in_array($_SESSION['role'], ['rh', 'juriste'], true)) {
        $responsableId = filter_input(INPUT_POST, 'responsable_id', FILTER_VALIDATE_INT);
        if ($responsableId) {
            $stmt = $pdo->prepare(
                'UPDATE signalements SET responsable_id = :rid WHERE id = :id'
            );
            $stmt->execute([':rid' => $responsableId, ':id' => $idSignalement]);
            logAudit('ATTRIBUTION_RESPONSABLE', 'Dossier ' . $ref . ' attribue au responsable id ' . $responsableId);
            $statutOk = true;
            $stmt = $pdo->prepare('SELECT * FROM signalements WHERE id = :id');
            $stmt->execute([':id' => $idSignalement]);
            $dossier = $stmt->fetch();
        }
    }

    // Action 1 : mise à jour du statut
    if ($action === 'modifier_statut') {
        $statutsAutorisee = ['OUVERT', 'EN_COURS', 'ATTENTE_INFO', 'CLOS_FONDE', 'CLOS_NON_FONDE'];
        $nouveauStatut    = filter_input(INPUT_POST, 'statut', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $motifCloture     = filter_input(INPUT_POST, 'motif_cloture', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

        if (in_array($nouveauStatut, $statutsAutorisee, true)) {
            $ancienStatut = $dossier['statut'];

            $stmt = $pdo->prepare(
                'UPDATE signalements
                 SET statut = :statut, date_mise_a_jour = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([':statut' => $nouveauStatut, ':id' => $idSignalement]);

            logAudit(
                'MODIFICATION_STATUT',
                'Dossier ' . $ref . ' ' . $ancienStatut . ' → ' . $nouveauStatut
                . (!empty($motifCloture) ? ' Motif : ' . $motifCloture : '')
            );

            $statutOk = true;
            // Rechargement du dossier avec le nouveau statut
            $stmt = $pdo->prepare('SELECT * FROM signalements WHERE id = :id');
            $stmt->execute([':id' => $idSignalement]);
            $dossier = $stmt->fetch();
        }
    }

    // Action 2 (juriste) : ajout d'une annotation legale
    if ($action === 'ajouter_annotation' && $_SESSION['role'] === 'juriste') {
        $contenuAnnot = trim(filter_input(INPUT_POST, 'annotation', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        if (mb_strlen($contenuAnnot) >= 2) {
            $auteurNomAnnot = $_SESSION['prenom'] . ' ' . $_SESSION['nom'];
            $stmt = $pdo->prepare(
                'INSERT INTO annotations (signalement_id, auteur_id, auteur_nom, contenu)
                 VALUES (:sid, :uid, :nom, :contenu)'
            );
            $stmt->execute([
                ':sid'     => $idSignalement,
                ':uid'     => $_SESSION['utilisateur_id'],
                ':nom'     => $auteurNomAnnot,
                ':contenu' => $contenuAnnot,
            ]);
            logAudit('ANNOTATION_LEGALE', 'Annotation ajoutee sur le dossier ' . $ref);
            $messageOk = true;
        } else {
            $erreurMsg = "L'annotation ne peut pas etre vide.";
        }
    }

    // Action 3 (juriste) : validation de la clôture
    if ($action === 'valider_cloture' && $_SESSION['role'] === 'juriste') {
        if (in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])) {
            $validateurNom = $_SESSION['prenom'] . ' ' . $_SESSION['nom'];
            $stmt = $pdo->prepare(
                'UPDATE signalements
                 SET valide_juriste = 1, date_validation = NOW(), validateur_nom = :nom
                 WHERE id = :id'
            );
            $stmt->execute([':nom' => $validateurNom, ':id' => $idSignalement]);
            logAudit('VALIDATION_CLOTURE', 'Cloture validee par le juriste sur le dossier ' . $ref);
            $statutOk = true;
            $stmt = $pdo->prepare('SELECT * FROM signalements WHERE id = :id');
            $stmt->execute([':id' => $idSignalement]);
            $dossier = $stmt->fetch();
        }
    }

    // Action 5 : ajout de pièce jointe par le salarié
    if ($action === 'ajouter_pj' && $_SESSION['role'] === 'salarie') {
        $dossierUpload = ROOT_PATH . '/uploads/';
        if (!is_dir($dossierUpload)) mkdir($dossierUpload, 0755, true);

        $extensionsAutorisees = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        $tailleMax = 5 * 1024 * 1024;

        if (!empty($_FILES['piece']['name']) && $_FILES['piece']['error'] === UPLOAD_ERR_OK) {
            $nomOriginal = basename($_FILES['piece']['name']);
            $ext = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

            if (in_array($ext, $extensionsAutorisees, true) && $_FILES['piece']['size'] <= $tailleMax) {
                $nomStockage = $ref . '_' . uniqid() . '.' . $ext;
                $chemin = $dossierUpload . $nomStockage;
                if (move_uploaded_file($_FILES['piece']['tmp_name'], $chemin)) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO pieces_jointes (signalement_id, nom_fichier, chemin_stockage, type_fichier, taille_octets)
                         VALUES (:sid, :nom, :chemin, :type, :taille)'
                    );
                    $stmt->execute([
                        ':sid'    => $idSignalement,
                        ':nom'    => $nomOriginal,
                        ':chemin' => $chemin,
                        ':type'   => $ext,
                        ':taille' => $_FILES['piece']['size'],
                    ]);
                    logAudit('AJOUT_PJ', 'Pièce jointe ajoutée sur le dossier ' . $ref);
                    $messageOk = true;
                }
            } else {
                $erreurMsg = 'Format non autorisé ou fichier trop lourd (max 5 Mo).';
            }
        }
    }
    if ($action === 'envoyer_message') {
        $contenu = trim(filter_input(INPUT_POST, 'contenu', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        if (mb_strlen($contenu) < 2) {
            $erreurMsg = 'Le message ne peut pas être vide.';
        } else {
            $estSalarie = $_SESSION['role'] === 'salarie';
            $auteurType = $estSalarie ? 'salarie' : 'staff';
            $auteurNom  = $estSalarie
                ? null
                : 'Service ' . strtoupper($_SESSION['role']) . ' ' . $_SESSION['prenom'] . ' ' . $_SESSION['nom'];

            $stmt = $pdo->prepare(
                'INSERT INTO messages (signalement_id, auteur_type, auteur_nom, contenu)
                 VALUES (:sid, :type, :nom, :contenu)'
            );
            $stmt->execute([
                ':sid'     => $idSignalement,
                ':type'    => $auteurType,
                ':nom'     => $auteurNom,
                ':contenu' => $contenu,
            ]);

            logAudit('ENVOI_MESSAGE', 'Message envoyé sur le dossier ' . $ref);
            $messageOk = true;
        }
    }
}

// Récupération des messages du dossier
$stmtMsg = $pdo->prepare(
    'SELECT * FROM messages WHERE signalement_id = :id ORDER BY date_envoi ASC'
);
$stmtMsg->execute([':id' => $idSignalement]);
$messages = $stmtMsg->fetchAll();

// Recuperation des annotations (juriste uniquement)
$annotations = [];
if ($_SESSION['role'] === 'juriste') {
    $stmtAnnot = $pdo->prepare(
        'SELECT * FROM annotations WHERE signalement_id = :id ORDER BY date_creation ASC'
    );
    $stmtAnnot->execute([':id' => $idSignalement]);
    $annotations = $stmtAnnot->fetchAll();
}

// Récupération des pièces jointes
$stmtPj = $pdo->prepare(
    'SELECT * FROM pieces_jointes WHERE signalement_id = :id ORDER BY date_upload ASC'
);
$stmtPj->execute([':id' => $idSignalement]);
$piecesJointes = $stmtPj->fetchAll();

// Recuperation de la liste des RH et juristes pour l'attribution
$stmtResp = $pdo->query(
    "SELECT id, prenom, nom, role FROM utilisateurs
     WHERE role IN ('rh', 'juriste') AND est_actif = 1
     ORDER BY nom ASC"
);
$responsablesDisponibles = $stmtResp->fetchAll();

// Responsable actuellement attribue
$responsableActuel = null;
if (!empty($dossier['responsable_id'])) {
    $stmtRA = $pdo->prepare('SELECT prenom, nom, role FROM utilisateurs WHERE id = :id');
    $stmtRA->execute([':id' => $dossier['responsable_id']]);
    $responsableActuel = $stmtRA->fetch();
}

// Log de consultation
logAudit('LECTURE', 'Consultation du dossier ' . $ref);

// Listes pour les libellés
$libStatuts = [
    'OUVERT'         => 'Ouvert',
    'EN_COURS'       => 'En cours',
    'ATTENTE_INFO'   => "En attente d'information",
    'CLOS_FONDE'     => 'Clôturé fondé',
    'CLOS_NON_FONDE' => 'Clôturé non fondé',
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
    <title>Dossier <?= htmlspecialchars($ref) ?> HRComplianceTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard-body">

<nav class="navbar site-navbar" aria-label="Navigation principale">
    <div class="container-fluid px-4">
        <a class="navbar-brand brand" href="../../dashboards/dashboard-rh.php">
            <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <?php
                if ($_SESSION['role'] === 'juriste') {
                    $lienNavDashboard = '../../dashboards/dashboard-juriste.php';
                } elseif ($_SESSION['role'] === 'salarie') {
                    $lienNavDashboard = '../../dashboards/dashboard-salarie.php';
                } else {
                    $lienNavDashboard = '../../dashboards/dashboard-rh.php';
                }
            ?>
            <a href="<?= $lienNavDashboard ?>" class="nav-link nav-link-custom">Tableau de bord</a>
            <a href="../../includes/deconnexion.php" class="nav-link nav-link-connexion">Déconnexion</a>
        </div>
    </div>
</nav>

<main class="dashboard-main">
<div class="container-fluid px-4">

    <a href="<?= $dashboardRetour ?>" class="lien-retour">&larr; Retour au tableau de bord</a>

    <?php if ($statutOk): ?>
        <div class="message-confirmation" role="status">Statut mis à jour avec succès.</div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- COLONNE PRINCIPALE -->
        <div class="<?= $_SESSION['role'] === 'salarie' ? 'col-12 col-lg-8 offset-lg-2' : 'col-12 col-lg-8' ?>">

            <header class="dossier-entete">
                <div>
                    <p class="dashboard-sous-titre">Dossier de signalement</p>
                    <h1 class="dossier-titre"><?= htmlspecialchars($ref) ?></h1>
                </div>
                <span class="statut statut-<?= strtolower(str_replace(['_', 'CLOS_'], ['-', 'clos-'], $dossier['statut'])) ?>">
                    <?= htmlspecialchars($libStatuts[$dossier['statut']] ?? $dossier['statut']) ?>
                </span>
            </header>

            <!-- Informations générales -->
            <section class="dossier-section">
                <h2 class="dossier-section-titre">Informations générales</h2>
                <dl class="infos-liste">
                    <div class="infos-ligne">
                        <dt class="infos-cle">Référence</dt>
                        <dd class="infos-valeur"><?= htmlspecialchars($dossier['code_suivi']) ?></dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Catégorie</dt>
                        <dd class="infos-valeur">
                            <?= htmlspecialchars($libCategories[$dossier['categorie']] ?? $dossier['categorie']) ?>
                        </dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Date de dépôt</dt>
                        <dd class="infos-valeur">
                            <time datetime="<?= htmlspecialchars($dossier['date_creation']) ?>">
                                <?= date('d/m/Y à H:i', strtotime($dossier['date_creation'])) ?>
                            </time>
                        </dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Priorité</dt>
                        <dd class="infos-valeur">
                            <span class="priorite <?= classePriorite($dossier['priorite']) ?>">
                                <?= libellePriorite($dossier['priorite']) ?>
                            </span>
                        </dd>
                    </div>
                    <div class="infos-ligne">
                        <dt class="infos-cle">Déclarant</dt>
                        <dd class="infos-valeur">
                            <?php if ((int)$dossier['masquer_identite'] === 1): ?>
                                <span class="identite-protegee">Identité protégée</span>
                            <?php else: ?>
                                <?php
                                    $nomComplet = trim(($dossier['prenom_declarant'] ?? '') . ' ' . ($dossier['nom_declarant'] ?? ''));
                                    echo $nomComplet ? htmlspecialchars($nomComplet) : '<em>Non renseigné</em>';
                                ?>
                                <?php if (!empty($dossier['email_declarant'])): ?>
                                    <br><small><?= htmlspecialchars($dossier['email_declarant']) ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Description des faits -->
            <section class="dossier-section">
                <h2 class="dossier-section-titre">Description des faits</h2>
                <blockquote class="description-faits">
                    <?php
                        $desc = $dossier['description'];
                        $desc = str_replace(['&#13;&#10;', '&#13;', '&#10;'], "\n", $desc);
                        $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $desc = str_replace("\r", '', $desc);
                        $desc = preg_replace("/\n{3,}/", "\n\n", trim($desc));
                        echo nl2br(htmlspecialchars($desc));
                    ?>
                </blockquote>
            </section>

            <!-- Pièces jointes -->
            <section class="dossier-section">
                <h2 class="dossier-section-titre">Pièces jointes</h2>

                <?php if (!empty($piecesJointes)): ?>
                <ul class="pieces-jointes-liste">
                    <?php foreach ($piecesJointes as $pj): ?>
                    <li>
                        <?php if (in_array($_SESSION['role'], ['rh', 'juriste', 'admin'], true)): ?>
                            <a href="telecharger.php?id=<?= (int)$pj['id'] ?>">
                                <?= htmlspecialchars($pj['nom_fichier']) ?>
                            </a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($pj['nom_fichier']) ?></span>
                        <?php endif; ?>
                        <small style="color:var(--gris-texte);">(<?= htmlspecialchars($pj['type_fichier']) ?> &mdash; <?= round($pj['taille_octets'] / 1024) ?> Ko)</small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <p style="color:var(--gris-texte); font-style:italic; font-size:0.88rem;">Aucune pièce jointe.</p>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'salarie' && !in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])): ?>
                <form method="POST" action="dossier.php?ref=<?= urlencode($ref) ?>" enctype="multipart/form-data" style="margin-top:1rem;">
                    <input type="hidden" name="action" value="ajouter_pj">
                    <div class="champ-groupe">
                        <label for="piece" class="form-label">Ajouter un fichier</label>
                        <input type="file" id="piece" name="piece"
                               class="form-control custom-input"
                               accept=".pdf,.jpg,.jpeg,.png,.docx">
                        <small style="color:var(--gris-texte);">Formats : PDF, JPG, PNG, DOCX — Max 5 Mo</small>
                    </div>
                    <button type="submit" class="btn-ouvrir" style="margin-top:0.5rem;">
                        Envoyer le fichier
                    </button>
                </form>
                <?php endif; ?>

            </section>

            <!-- Messagerie anonyme -->
            <section class="dossier-section" id="messagerie">
                <h2 class="dossier-section-titre">Messagerie confidentielle</h2>

                <?php if (!empty($erreurMsg)): ?>
                    <p class="champ-erreur visible" role="alert"><?= htmlspecialchars($erreurMsg) ?></p>
                <?php endif; ?>
                <?php if ($messageOk): ?>
                    <p class="message-confirmation" role="status">Message envoyé.</p>
                <?php endif; ?>

                <div class="messagerie-zone" id="zone-messages" aria-live="polite">
                    <?php if (empty($messages)): ?>
                        <p style="color:var(--gris-texte); font-style:italic;">Aucun message pour l'instant.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="message <?php
                            if ($_SESSION['role'] === 'salarie') {
                                echo $msg['auteur_type'] === 'salarie' ? 'message-envoye' : 'message-recu';
                            } else {
                                echo $msg['auteur_type'] === 'staff' ? 'message-envoye' : 'message-recu';
                            }
                        ?>">
                            <?php if ($_SESSION['role'] === 'salarie'): ?>
                                <p class="message-auteur">
                                    <?= $msg['auteur_type'] === 'salarie' ? 'Vous' : htmlspecialchars($msg['auteur_nom'] ?? 'Service RH') ?>
                                </p>
                            <?php else: ?>
                                <p class="message-auteur">
                                    <?= $msg['auteur_type'] === 'staff' ? htmlspecialchars($msg['auteur_nom']) : 'Lanceur d\'alerte' ?>
                                </p>
                            <?php endif; ?>
                            <p><?php
                                $c = $msg['contenu'];
                                $c = str_replace(['&#13;&#10;', '&#13;', '&#10;'], "
", $c);
                                $c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $c = str_replace("
", '', $c);
                                echo nl2br(htmlspecialchars(trim($c)));
                            ?></p>
                            <time class="message-heure" datetime="<?= htmlspecialchars($msg['date_envoi']) ?>">
                                <?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?>
                            </time>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])): ?>
                <form method="POST" action="dossier.php?ref=<?= urlencode($ref) ?>">
                    <input type="hidden" name="action" value="envoyer_message">
                    <div class="champ-groupe" style="margin-top:1rem;">
                        <label for="contenu-message" class="form-label">Votre réponse</label>
                        <textarea
                            id="contenu-message"
                            name="contenu"
                            class="form-control custom-input"
                            rows="3"
                            placeholder="Rédigez votre message..."
                            required
                        ></textarea>
                    </div>
                    <button type="submit" class="btn-ouvrir" style="margin-top:0.5rem;">
                        Envoyer le message
                    </button>
                </form>
                <?php endif; ?>
            </section>

        </div>

        <!-- COLONNE ACTIONS (sticky, visible uniquement par le staff) -->
        <?php if (in_array($_SESSION['role'], ['rh', 'juriste'])): ?>
        <div class="col-12 col-lg-4">
        <div style="position:sticky; top:80px; display:flex; flex-direction:column; gap:1.5rem;">

            <aside class="actions-bloc" style="position:static;">

                <h2 class="dossier-section-titre">Actions</h2>

                <!-- Bloc attribution responsable -->
                <div style="margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--gris-bordure);">
                    <p class="form-label" style="font-weight:600; margin-bottom:0.5rem;">Responsable attribue</p>
                    <?php if ($responsableActuel): ?>
                        <p style="font-size:0.9rem; color:var(--bleu-marine); font-weight:500; margin-bottom:0.75rem;">
                            <?= htmlspecialchars($responsableActuel['prenom'] . ' ' . $responsableActuel['nom']) ?>
                            <span style="color:var(--gris-texte); font-weight:400;">(<?= strtoupper($responsableActuel['role']) ?>)</span>
                        </p>
                    <?php else: ?>
                        <p style="font-size:0.88rem; font-style:italic; color:var(--gris-texte); margin-bottom:0.75rem;">Aucun responsable attribue.</p>
                    <?php endif; ?>

                    <?php if (!in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])): ?>
                    <form method="POST" action="dossier.php?ref=<?= urlencode($ref) ?>">
                        <input type="hidden" name="action" value="attribuer_responsable">
                        <div class="champ-groupe">
                            <label for="responsable_id" class="form-label">Attribuer a</label>
                            <select id="responsable_id" name="responsable_id" class="form-control custom-input filtre-select">
                                <option value="">-- Choisir un responsable --</option>
                                <?php foreach ($responsablesDisponibles as $resp): ?>
                                    <option value="<?= (int)$resp['id'] ?>"
                                        <?= (int)($dossier['responsable_id'] ?? 0) === (int)$resp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($resp['prenom'] . ' ' . $resp['nom']) ?>
                                        (<?= strtoupper($resp['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit btn-connexion-submit" style="margin-top:0.5rem;">
                            Attribuer
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (!in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])): ?>
                <form method="POST" action="dossier.php?ref=<?= urlencode($ref) ?>">
                    <input type="hidden" name="action" value="modifier_statut">

                    <div class="champ-groupe">
                        <label for="select-statut" class="form-label">Modifier le statut</label>
                        <select id="select-statut" name="statut" class="form-control custom-input filtre-select">
                            <?php foreach ($libStatuts as $val => $lib): ?>
                                <option
                                    value="<?= $val ?>"
                                    <?= $dossier['statut'] === $val ? 'selected' : '' ?>
                                >
                                    <?= $lib ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="champ-groupe" id="bloc-motif">
                        <label for="motif-cloture" class="form-label">
                            Motif de clôture
                            <span class="signalement-section-facultatif">(si clôture)</span>
                        </label>
                        <textarea
                            id="motif-cloture"
                            name="motif_cloture"
                            class="form-control custom-input"
                            rows="2"
                            placeholder="Motif obligatoire en cas de clôture..."
                        ></textarea>
                    </div>

                    <button type="submit" class="btn-submit btn-connexion-submit" style="margin-top:0.5rem;">
                        Enregistrer les modifications
                    </button>
                </form>
                <?php else: ?>
                    <p style="font-style:italic; color:var(--gris-texte); font-size:0.88rem;">
                        Ce dossier est clôturé. Aucune action n'est possible.
                    </p>
                <?php endif; ?>

            </aside>

            <?php if ($_SESSION['role'] === 'juriste'): ?>

            <!-- BLOC ANNOTATIONS LEGALES -->
            <aside class="actions-bloc" style="position:static;">
                <h2 class="dossier-section-titre">Annotations legales</h2>

                <?php if (!empty($annotations)): ?>
                    <?php foreach ($annotations as $a): ?>
                    <div style="border-left:3px solid var(--bleu-marine); padding-left:0.75rem; margin-bottom:1rem;">
                        <p style="font-size:0.85rem; font-weight:600; color:var(--bleu-marine); margin-bottom:0.2rem;">
                            <?= htmlspecialchars($a['auteur_nom']) ?>
                            <span style="font-weight:400; color:var(--gris-texte);">
                                &mdash; <?= date('d/m/Y', strtotime($a['date_creation'])) . ' à ' . date('H:i', strtotime($a['date_creation'])) ?>
                            </span>
                        </p>
                        <p style="font-size:0.9rem; margin:0;"><?php
                                $ca = $a['contenu'];
                                $ca = str_replace(['&#13;&#10;', '&#13;', '&#10;'], "
", $ca);
                                $ca = html_entity_decode($ca, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $ca = str_replace("
", '', $ca);
                                echo nl2br(htmlspecialchars(trim($ca)));
                            ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-style:italic; color:var(--gris-texte); font-size:0.88rem;">
                        Aucune annotation pour ce dossier.
                    </p>
                <?php endif; ?>

                <form method="POST" action="dossier.php?ref=<?= urlencode($ref) ?>" style="margin-top:1rem;">
                    <input type="hidden" name="action" value="ajouter_annotation">
                    <div class="champ-groupe">
                        <label for="annotation" class="form-label">Nouvelle annotation</label>
                        <textarea
                            id="annotation"
                            name="annotation"
                            class="form-control custom-input"
                            rows="3"
                            placeholder="Qualifications juridiques, references legales, observations..."
                            required
                        ></textarea>
                    </div>
                    <button type="submit" class="btn-submit btn-connexion-submit" style="margin-top:0.5rem;">
                        Ajouter l'annotation
                    </button>
                </form>
            </aside>

            <!-- BLOC VALIDATION DE CLOTURE -->
            <?php if (in_array($dossier['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])): ?>
            <aside class="actions-bloc" style="position:static;">
                <h2 class="dossier-section-titre">Validation de la clôture</h2>

                <?php if ((int)$dossier['valide_juriste'] === 1): ?>
                    <p style="color:#2e7d32; font-weight:600; font-size:0.9rem;">
                        Clôture validee par <?= htmlspecialchars($dossier['validateur_nom']) ?>
                        le <?= date('d/m/Y', strtotime($dossier['date_validation'])) . ' à ' . date('H:i', strtotime($dossier['date_validation'])) ?>.
                    </p>
                <?php else: ?>
                    <p style="font-size:0.88rem; color:var(--gris-texte); margin-bottom:0.75rem;">
                        Ce dossier est clos mais n'a pas encore été valide par le service juridique.
                    </p>
                    <form method="POST" action="dossier.php?ref=<?= urlencode($ref) ?>">
                        <input type="hidden" name="action" value="valider_cloture">
                        <button type="submit" class="btn-submit btn-connexion-submit">
                            Valider la clôture
                        </button>
                    </form>
                <?php endif; ?>
            </aside>
            <?php endif; ?>

            <?php endif; // fin du bloc juriste ?>
        </div><!-- fin sticky wrapper -->
        </div><!-- fin col -->

        <?php endif; // fin affichage staff ?>

    </div>
</div>
</main>

<footer class="site-footer">
    <div class="container text-center py-3">
        <small>HRComplianceTech &copy; 2026 &mdash; Conforme RGPD &amp; Loi Sapin 2 &mdash; <a href="../../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>