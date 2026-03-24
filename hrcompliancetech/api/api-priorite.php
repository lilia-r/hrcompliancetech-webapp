<?php
/**
 * api-priorite.php
 * API de classification automatique de la priorité d'un signalement.
 * Méthode : POST JSON { "description": "...", "categorie": "..." }
 * Réponse : JSON { "priorite": "haute"|"normale", "score": int, "mots_detectes": [...] }
 *
 * Logique : analyse par mots-clés pondérés.
 * Si le score dépasse le seuil, la priorité est "haute".
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
    exit;
}

// Lecture du corps de la requête
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!isset($data['description'])) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Paramètre description manquant.']);
    exit;
}

$description = mb_strtolower($data['description'] ?? '', 'UTF-8');
$categorie   = mb_strtolower($data['categorie']   ?? '', 'UTF-8');

// ============================================================
// DICTIONNAIRE DE MOTS-CLÉS PONDÉRÉS
// Score élevé = signal fort de haute priorité
// ============================================================
$motsCles = [
    // Urgence / gravité physique
    'violence'          => 10,
    'agression'         => 10,
    'menace'            => 9,
    'harcèlement sexuel'=> 10,
    'harcèlement moral' => 8,
    'harcelement sexuel'=> 10,
    'harcelement moral' => 8,
    'viol'              => 10,
    'discrimination'    => 7,
    'licenciement abusif' => 8,
    'représailles'      => 8,
    'represailles'      => 8,
    'intimidation'      => 7,
    'pression'          => 5,
    'psychologique'     => 6,
    'détresse'          => 7,
    'detresse'          => 7,
    'urgence'           => 8,
    'immédiat'          => 6,
    'immediat'          => 6,

    // Fraude / corruption
    'fraude'            => 9,
    'corruption'        => 10,
    'détournement'      => 9,
    'detournement'      => 9,
    'malversation'      => 9,
    'falsification'     => 8,
    'pot-de-vin'        => 9,
    'escroquerie'       => 8,
    'blanchiment'       => 9,
    'irrégularité'      => 6,
    'irregularite'      => 6,
    'abus'              => 7,

    // Atteinte à l'environnement
    'pollution'         => 8,
    'danger'            => 8,
    'risque grave'      => 9,
    'accident'          => 7,
    'déversement'       => 8,
    'deversement'       => 8,
    'toxique'           => 9,

    // Atteinte aux personnes
    'victime'           => 7,
    'blessé'            => 8,
    'blesse'            => 8,
    'traumatisme'       => 8,
    'isolement'         => 6,
    'exclusion'         => 6,
    'humiliation'       => 7,
    'dénigrement'       => 6,
    'denigrement'       => 6,

    // Mots modérateurs (signaux faibles)
    'inconfort'         => 2,
    'désaccord'         => 2,
    'desaccord'         => 2,
    'tension'           => 3,
    'difficile'         => 2,
];

// Bonus selon la catégorie
$bonusCategorie = [
    'harcelement'    => 5,
    'fraude'         => 5,
    'corruption'     => 6,
    'environnement'  => 3,
    'discrimination' => 4,
    'ethique'        => 2,
    'autre'          => 0,
];

// Calcul du score
$score = 0;
$motsDetectes = [];

foreach ($motsCles as $mot => $poids) {
    if (mb_strpos($description, $mot, 0, 'UTF-8') !== false) {
        $score += $poids;
        $motsDetectes[] = $mot;
    }
}

// Ajout du bonus catégorie
$score += $bonusCategorie[$categorie] ?? 0;

// Seuils de décision
$seuilHaute  = 12;
$seuilNormale = 5;

if ($score >= $seuilHaute) {
    $priorite = 'haute';
} elseif ($score >= $seuilNormale) {
    $priorite = 'normale';
} else {
    $priorite = 'basse';
}

echo json_encode([
    'priorite'       => $priorite,
    'score'          => $score,
    'seuil_haute'    => $seuilHaute,
    'seuil_normale'  => $seuilNormale,
    'mots_detectes'  => $motsDetectes,
    'reponse_type'   => genererReponseType($categorie, $priorite),
]);

/**
 * Génère un message de réponse initiale standardisé
 * selon la catégorie et la priorité du signalement.
 */
function genererReponseType(string $categorie, string $priorite): string
{
    $intro = $priorite === 'haute'
        ? "Votre signalement a été reçu et marqué comme prioritaire. Il sera traité en urgence par notre équipe."
        : "Votre signalement a bien été enregistré et sera traité dans les meilleurs délais.";

    $corps = match($categorie) {
        'harcelement' =>
            "Nous prenons très au sérieux toute situation de harcèlement moral ou sexuel. " .
            "Un membre de notre service RH ou juridique prendra contact avec vous de manière confidentielle " .
            "pour recueillir les éléments nécessaires à l'instruction de votre dossier.",

        'discrimination' =>
            "Nous condamnons fermement toute forme de discrimination. " .
            "Votre dossier sera examiné avec attention par notre service juridique, " .
            "dans le respect strict de votre anonymat et de vos droits.",

        'fraude' =>
            "Les faits signalés font l'objet d'une attention particulière de notre service conformité. " .
            "Une enquête interne sera ouverte si les éléments fournis le justifient. " .
            "Vous serez informé(e) des suites données à votre signalement.",

        'environnement' =>
            "Votre signalement relatif à une atteinte à l'environnement a été transmis " .
            "au référent conformité. Des mesures d'investigation seront mises en place rapidement.",

        'ethique' =>
            "Les atteintes à l'éthique et à l'intégrité sont contraires aux valeurs de notre organisation. " .
            "Votre signalement sera instruit par notre comité d'éthique dans les plus brefs délais.",

        default =>
            "Votre signalement a été pris en charge par notre équipe. " .
            "Nous vous contacterons via cette messagerie confidentielle pour toute information complémentaire.",
    };

    $cloture = "Conformément à la loi Sapin 2, votre identité reste strictement confidentielle " .
               "et vous êtes protégé(e) contre toute forme de représailles.";

    return $intro . "\n\n" . $corps . "\n\n" . $cloture;
}