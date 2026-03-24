<?php
/**
 * telecharger.php
 * Sert les pièces jointes de façon sécurisée.
 * Accessible aux RH, juristes et au salarié propriétaire du dossier.
 */
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(403);
    exit('Accès non autorisé.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Identifiant invalide.');
}

$pdo = getDB();

// Récupération de la pièce jointe et du signalement associé
$stmt = $pdo->prepare(
    'SELECT pj.*, s.utilisateur_id AS proprietaire_id
     FROM pieces_jointes pj
     JOIN signalements s ON s.id = pj.signalement_id
     WHERE pj.id = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$pj = $stmt->fetch();

if (!$pj) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

// Contrôle d'accès : RH/juriste/admin ou salarié propriétaire
$role = $_SESSION['role'];
$uid  = (int) $_SESSION['utilisateur_id'];

$autorise = in_array($role, ['rh', 'juriste', 'admin'], true)
    || ($role === 'salarie' && $uid === (int) $pj['proprietaire_id']);

if (!$autorise) {
    http_response_code(403);
    exit('Accès non autorisé.');
}

$chemin = $pj['chemin_stockage'];
if (!file_exists($chemin)) {
    http_response_code(404);
    exit('Fichier absent du serveur.');
}

logAudit('TELECHARGEMENT_PJ', 'Pièce jointe ID ' . $id . ' téléchargée');

// Envoi du fichier
$mimeTypes = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$ext  = strtolower($pj['type_fichier']);
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($pj['nom_fichier']) . '"');
header('Content-Length: ' . filesize($chemin));
readfile($chemin);
exit;