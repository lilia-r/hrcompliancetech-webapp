<?php
/**
 * auth.php
 * Traité la soumission du formulaire de connexion (POST depuis connexion.html).
 * En cas d'échec : redirige vers connexion.html avec un paramètre erreur dans l'URL.
 */

require_once __DIR__ . '/../config/config.php';

// Accès direct sans POST → retour au formulaire
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: connexion.html');
    exit;
}

// On détruit toute session existante avant de traiter le nouveau login
session_unset();
session_destroy();
session_start();

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
$mdp   = $_POST['mot_de_passe'] ?? '';

if (empty($email) || empty($mdp)) {
    header('Location: connexion.html?erreur=champs_vides');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    'SELECT id, nom, prenom, email, mot_de_passe, role
     FROM utilisateurs
     WHERE email = :email AND est_actif = 1
     LIMIT 1'
);
$stmt->execute([':email' => $email]);
$utilisateur = $stmt->fetch();

if ($utilisateur && password_verify($mdp, $utilisateur['mot_de_passe'])) {

    session_regenerate_id(true);

    $_SESSION['utilisateur_id'] = $utilisateur['id'];
    $_SESSION['nom']            = $utilisateur['nom'];
    $_SESSION['prenom']         = $utilisateur['prenom'];
    $_SESSION['email']          = $utilisateur['email'];
    $_SESSION['role']           = $utilisateur['role'];

    logAudit('CONNEXION', 'Connexion réussie ' . $utilisateur['role']);

    header('Location: ' . urlDashboard($utilisateur['role']));
    exit;

} else {
    logAudit('ECHEC_CONNEXION', 'Tentative échouée pour : ' . $email);
    header('Location: connexion.html?erreur=identifiants_incorrects');
    exit;
}

function urlDashboard(string $role): string
{
    return match($role) {
        'admin'   => '../dashboards/admin.php',
        'juriste' => '../dashboards/dashboard-juriste.php',
        'salarie' => '../dashboards/dashboard-salarie.php',
        default   => '../dashboards/dashboard-rh.php',
    };
}