<?php
/**
 * config.php
 * Point d'entrée unique pour toute la configuration du projet.
 * Inclus en tête de chaque fichier PHP via :
 *   require_once __DIR__ . '/<profondeur>/config/config.php';
 *
 * Ce fichier :
 *  - Définit ROOT_PATH (chemin absolu du projet)
 *  - Configure la connexion PDO via getDB()
 *  - Inclut les fonctions utilitaires
 *  - Démarre la session si elle n'est pas déjà active
 */

// Chemin absolu vers la racine du projet (dossier HRCOMPLIANCETECH/)
// Utilisable dans n'importe quel fichier PHP : ROOT_PATH . '/modules/...'
define('ROOT_PATH', dirname(__DIR__));

// Paramètres de connexion à la base de données
define('DB_HOST',    'localhost');
define('DB_NAME',    'hr_compliance_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Démarrage automatique de la session (une seule fois par requête)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement des fonctions utilitaires partagées
require_once ROOT_PATH . '/includes/fonctions.php';


/**
 * Retourne une instance PDO partagée (connexion unique par requête).
 * Le mot-clé static préserve la valeur entre les appels dans le même script.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname='    . DB_NAME
             . ';charset='   . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Erreur de connexion à la base de données.');
        }
    }

    return $pdo;
}

/**
 * Enregistre une action dans le journal d'audit.
 * Appelé après toute action significative (connexion, lecture, modification).
 */
function logAudit(string $action, string $details = ''): void
{
    $pdo = getDB();
    $uid = $_SESSION['utilisateur_id'] ?? null;
    $ip  = $_SERVER['REMOTE_ADDR']     ?? '0.0.0.0';

    $stmt = $pdo->prepare(
        'INSERT INTO logs_audit (utilisateur_id, action, details, adresse_ip)
         VALUES (:uid, :action, :details, :ip)'
    );
    $stmt->execute([
        ':uid'     => $uid,
        ':action'  => $action,
        ':details' => $details,
        ':ip'      => $ip,
    ]);
}
