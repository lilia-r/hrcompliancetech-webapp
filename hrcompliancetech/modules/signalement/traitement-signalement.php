<?php
require_once __DIR__ . '/../../config/config.php';

/**
 * traitement-signalement.php
 * Reçoit les données du formulaire signalement.php (méthode POST).
 * Valide, nettoie, enregistré en base de données et retourne
 * le code de suivi en JSON.
 *
 * Appel attendu depuis signalement.php via fetch() :
 *   fetch('traitement-signalement.php', { method: 'POST', body: formData })
 */

// -- Configuration de la réponse ------------------------------------------
// En production : désactiver l'affichage des erreurs PHP dans la réponse HTTP
// et les rediriger vers un fichier de log serveur.
ini_set('display_errors', 0);
error_reporting(E_ALL);


// -- Connexion à la base de données ----------------------------------------
// Les identifiants sont dans un fichier de configuration hors racine web,
// jamais en dur dans le code source versionné.
// Connexion via config.php
$pdo = getDB();


// -- Vérification de la méthode HTTP ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signalement.php');
    exit;
}


// -- Récupération et nettoyage des données POST ----------------------------
// filter_input() nettoie les données avant toute manipulation.
// FILTER_SANITIZE_SPECIAL_CHARS retire les caractères HTML dangereux.

$categorie   = filter_input(INPUT_POST, 'categorie',    FILTER_DEFAULT) ?? '';
$description = filter_input(INPUT_POST, 'description',  FILTER_DEFAULT) ?? '';
$nom         = filter_input(INPUT_POST, 'nom',          FILTER_DEFAULT) ?? '';
$prenom      = filter_input(INPUT_POST, 'prenom',       FILTER_DEFAULT) ?? '';
$email       = filter_input(INPUT_POST, 'email',        FILTER_SANITIZE_EMAIL)         ?? '';

// La checkbox envoie "1" si cochée, rien si décochée.
// On normalise en entier : 1 (masquer) ou 0 (afficher).
$masquerIdentite = isset($_POST['masquer_identite']) && $_POST['masquer_identite'] === '1' ? 1 : 0;


// -- Validation côté serveur -----------------------------------------------
// La validation JS du formulaire est une aide à l'utilisateur,
// mais elle ne constitue JAMAIS une sécurité : n'importe qui peut
// envoyer une requête POST manuellement sans passer par le formulaire.

$erreurs = [];

$categoriesAutorisees = ['harcelement', 'discrimination', 'fraude', 'environnement', 'ethique', 'autre'];
if (!in_array($categorie, $categoriesAutorisees, true)) {
    $erreurs[] = 'Catégorie invalide.';
}

if (mb_strlen(trim($description)) < 20) {
    $erreurs[] = 'La description doit comporter au moins 20 caractères.';
}

// Email facultatif, mais valide s'il est fourni
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = "L'adresse e-mail n'est pas valide.";
}

if (!empty($erreurs)) {
    $_SESSION['erreurs_signalement'] = $erreurs;
header('Location: signalement.php');
    exit;
}


// -- Génération du code de suivi unique ------------------------------------
// random_bytes() est la fonction PHP recommandée pour la génération
// de données aléatoires à usage cryptographique.
// On génère 4 octets → 8 caractères hexadécimaux → on garde 6 en majuscules.
function genererCodeSuivi(PDO $pdo): string
{
    $tentatives = 0;
    do {
        $code = 'HRC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        // On vérifie que le code n'existe pas déjà en base (contrainte UNIQUE)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM signalements WHERE code_suivi = :code');
        $stmt->execute([':code' => $code]);
        $existe = (int) $stmt->fetchColumn();

        $tentatives++;
        // Sécurité : éviter une boucle infinie si la base est saturée
        if ($tentatives > 10) {
            throw new RuntimeException('Impossible de générer un code de suivi unique.');
        }
    } while ($existe > 0);

    return $code;
}


// -- Enregistrement en base de données -------------------------------------
try {

    $codeSuivi = genererCodeSuivi($pdo);

    // -- Classification automatique de la priorité via l'API ---------------
    $priorite = 'normale'; // valeur par défaut
    try {
        $apiUrl  = 'http://localhost/projet_final/api/api-priorite.php';
        $payload = json_encode(['description' => trim($description), 'categorie' => $categorie]);
        $opts    = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => 3,
            ]
        ];
        $reponse = file_get_contents($apiUrl, false, stream_context_create($opts));
        if ($reponse !== false) {
            $resultat = json_decode($reponse, true);
            if (isset($resultat['priorite'])) {
                $priorite = $resultat['priorite'];
            }
            $reponseType = $resultat['reponse_type'] ?? null;
        }
    } catch (Exception $e) {
        // En cas d'échec de l'API, on garde la priorité normale par défaut
    }

    // Requête préparée : les :parametres empêchent les injections SQL.
    // Les valeurs ne sont jamais concaténées dans la chaîne SQL.
    // Si le salarié est connecté, on lie le signalement a son compte
    $utilisateurId = (isset($_SESSION['utilisateur_id']) && $_SESSION['role'] === 'salarie')
        ? (int) $_SESSION['utilisateur_id']
        : null;

    $sql = '
        INSERT INTO signalements (
            code_suivi,
            categorie,
            description,
            statut,
            priorite,
            email_declarant,
            nom_declarant,
            prenom_declarant,
            masquer_identite,
            utilisateur_id
        ) VALUES (
            :code_suivi,
            :categorie,
            :description,
            :statut,
            :priorite,
            :email,
            :nom,
            :prenom,
            :masquer_identite,
            :utilisateur_id
        )
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':code_suivi'       => $codeSuivi,
        ':categorie'        => $categorie,
        ':description'      => trim($description),
        ':statut'           => 'OUVERT',
        ':priorite'         => $priorite,
        ':email'            => !empty($email) ? $email : null,
        ':nom'              => !empty($nom)    ? $nom    : null,
        ':prenom'           => !empty($prenom) ? $prenom : null,
        ':masquer_identite' => $masquerIdentite,
        ':utilisateur_id'   => $utilisateurId,
    ]);

    $idSignalement = (int) $pdo->lastInsertId();

    // -- Traitement des pièces jointes -------------------------------------
    $dossierUpload = ROOT_PATH . '/uploads/';
    if (!is_dir($dossierUpload)) {
        mkdir($dossierUpload, 0755, true);
    }

    $extensionsAutorisees = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
    $tailleMax = 5 * 1024 * 1024; // 5 Mo

    if (!empty($_FILES['pieces']['name'][0])) {
        $nbFichiers = count($_FILES['pieces']['name']);
        for ($i = 0; $i < $nbFichiers; $i++) {
            if ($_FILES['pieces']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $nomOriginal = basename($_FILES['pieces']['name'][$i]);
            $ext = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

            if (!in_array($ext, $extensionsAutorisees, true)) continue;
            if ($_FILES['pieces']['size'][$i] > $tailleMax) continue;

            $nomStockage = $codeSuivi . '_' . uniqid() . '.' . $ext;
            $chemin = $dossierUpload . $nomStockage;

            if (move_uploaded_file($_FILES['pieces']['tmp_name'][$i], $chemin)) {
                $stmtPj = $pdo->prepare(
                    'INSERT INTO pieces_jointes (signalement_id, nom_fichier, chemin_stockage, type_fichier, taille_octets)
                     VALUES (:sid, :nom, :chemin, :type, :taille)'
                );
                $stmtPj->execute([
                    ':sid'    => $idSignalement,
                    ':nom'    => $nomOriginal,
                    ':chemin' => $chemin,
                    ':type'   => $ext,
                    ':taille' => $_FILES['pieces']['size'][$i],
                ]);
            }
        }
    }

    // -- Log d'audit : création du dossier ----------------------------------
    // L'adresse IP est enregistrée pour la traçabilité légale.
    // En production derrière un reverse proxy, utiliser HTTP_X_FORWARDED_FOR.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $logSql = '
        INSERT INTO logs_audit (utilisateur_id, action, details, adresse_ip)
        VALUES (NULL, :action, :details, :ip)
    ';
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':action'  => 'CREATION_DOSSIER',
        ':details' => 'Nouveau signalement code ' . $codeSuivi . ' categorie : ' . $categorie,
        ':ip'      => $ip,
    ]);

    // -- Insertion de la réponse type générée par l'API -------------------
    if (!empty($reponseType)) {
        $stmtMsg = $pdo->prepare(
            'INSERT INTO messages (signalement_id, auteur_type, auteur_nom, contenu)
             VALUES (:sid, :type, :nom, :contenu)'
        );
        $stmtMsg->execute([
            ':sid'     => $idSignalement,
            ':type'    => 'staff',
            ':nom'     => 'Service RH - Réponse automatique',
            ':contenu' => $reponseType,
        ]);
    }

    // -- Redirection vers la page de confirmation ---------------------------
    header('Location: confirmation.php');
    exit;

} catch (RuntimeException $e) {
    $_SESSION['erreurs_signalement'] = [$e->getMessage()];
header('Location: signalement.php');
} catch (PDOException $e) {
    $_SESSION['erreurs_signalement'] = ["Erreur lors de l'enregistrement."];
header('Location: signalement.php');
}