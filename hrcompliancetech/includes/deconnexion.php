<?php
/**
 * deconnexion.php
 * Détruit la session active et redirige vers la page de connexion.
 */

// session démarrée par config.php

// On supprime d'abord toutes les variables de session
$_SESSION = [];

// Suppression du cookie de session côté navigateur
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ../auth/connexion.html');
exit;
