<?php
/**
 * message-ajax.php
 * Endpoint AJAX pour l'envoi de messages depuis les pages HTML statiques.
 * Reçoit une requête POST avec les données JSON ou FormData,
 * insère le message, retourne une réponse JSON.
 *
 * Appelé via fetch() depuis script.js (formulaires messagerie).
 */

// session démarrée par config.php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['succes' => false, 'erreur' => 'Méthode non autorisée.']);
    exit;
}

$pdo         = getDB();
$ref         = filter_input(INPUT_POST, 'ref',         FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$contenu     = filter_input(INPUT_POST, 'contenu',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$auteurType  = filter_input(INPUT_POST, 'auteur_type', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// Validation
if (empty($ref) || mb_strlen(trim($contenu)) < 2) {
    http_response_code(422);
    echo json_encode(['succes' => false, 'erreur' => 'Données manquantes ou invalides.']);
    exit;
}

if (!in_array($auteurType, ['salarie', 'staff'], true)) {
    http_response_code(422);
    echo json_encode(['succes' => false, 'erreur' => 'Type d\'auteur invalide.']);
    exit;
}

// Si l'auteur est du staff, il doit être connecté
if ($auteurType === 'staff') {
    if (!isset($_SESSION['utilisateur_id']) || !in_array($_SESSION['role'], ['rh', 'juriste'], true)) {
        http_response_code(403);
        echo json_encode(['succes' => false, 'erreur' => 'Accès non autorisé.']);
        exit;
    }
    $auteurNom = 'Service ' . strtoupper($_SESSION['role'])
               . ' — ' . $_SESSION['prenom'] . ' ' . $_SESSION['nom'];
} else {
    $auteurNom = null; // Salarié anonyme
}

// Récupération du signalement
$stmt = $pdo->prepare('SELECT id, statut FROM signalements WHERE code_suivi = :ref LIMIT 1');
$stmt->execute([':ref' => $ref]);
$sig = $stmt->fetch();

if (!$sig) {
    http_response_code(404);
    echo json_encode(['succes' => false, 'erreur' => 'Dossier introuvable.']);
    exit;
}

// Messagerie désactivée sur dossier clôturé
if (in_array($sig['statut'], ['CLOS_FONDE', 'CLOS_NON_FONDE'])) {
    http_response_code(403);
    echo json_encode(['succes' => false, 'erreur' => 'Ce dossier est clôturé.']);
    exit;
}

// Insertion du message
$stmt = $pdo->prepare(
    'INSERT INTO messages (signalement_id, auteur_type, auteur_nom, contenu)
     VALUES (:sid, :type, :nom, :contenu)'
);
$stmt->execute([
    ':sid'     => $sig['id'],
    ':type'    => $auteurType,
    ':nom'     => $auteurNom,
    ':contenu' => trim($contenu),
]);

if ($auteurType === 'staff') {
    logAudit('ENVOI_MESSAGE', 'Message sur dossier ' . $ref);
}

echo json_encode([
    'succes'     => true,
    'auteur'     => $auteurNom ?? 'Lanceur d\'alerte',
    'contenu'    => htmlspecialchars(trim($contenu)),
    'date_envoi' => date('d/m/Y à H:i'),
]);
