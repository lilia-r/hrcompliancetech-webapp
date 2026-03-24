<?php
/**
 * fonctions.php
 * Fonctions utilitaires partagées par tous les modules.
 * Inclus automatiquement via config.php.
 */

/**
 * Vérifie que l'utilisateur est connecté et possède le bon rôle.
 * Redirige vers la page de connexion si la vérification échoue.
 *
 * @param string|array $rolesAutorises Un rôle ou un tableau de rôles acceptés.
 */
function exigerConnexion(string|array $rolesAutorises = []): void
{
    if (!isset($_SESSION['utilisateur_id'])) {
        header('Location: ../auth/connexion.html');
        exit;
    }

    if (!empty($rolesAutorises)) {
        $roles = is_array($rolesAutorises) ? $rolesAutorises : [$rolesAutorises];
        if (!in_array($_SESSION['role'], $roles, true)) {
            header('Location: ../auth/connexion.html');
            exit;
        }
    }
}

/**
 * Retourne le libellé lisible d'une categorie de signalement.
 */
function libelleCategorie(string $categorie): string
{
    $libelles = [
        'harcelement'    => 'Harcèlement moral ou sexuel',
        'discrimination' => 'Discrimination',
        'fraude'         => 'Fraude',
        'environnement'  => 'Atteinte à l\'environnement',
        'ethique'        => 'Atteinte à l\'éthique',
        'autre'          => 'Autre',
    ];
    return $libelles[$categorie] ?? ucfirst($categorie);
}

/**
 * Retourne le libellé lisible d'un statut de dossier.
 */
function libelleStatut(string $statut): string
{
    $libelles = [
        'OUVERT'         => 'Ouvert',
        'EN_COURS'       => 'En cours',
        'ATTENTE_INFO'   => "En attente d'information",
        'CLOS_FONDE'     => 'Clôturé fondé',
        'CLOS_NON_FONDE' => 'Clôturé non fondé',
    ];
    return $libelles[$statut] ?? $statut;
}

/**
 * Retourne la classe CSS correspondant à un statut.
 */
function classeStatut(string $statut): string
{
    $classes = [
        'OUVERT'         => 'statut-nouveau',
        'EN_COURS'       => 'statut-en-cours',
        'ATTENTE_INFO'   => 'statut-attente-validation',
        'CLOS_FONDE'     => 'statut-clos-valide',
        'CLOS_NON_FONDE' => 'statut-clos',
    ];
    return $classes[$statut] ?? '';
}

/**
 * Affiche proprement le nom d'un declarant selon le masquage.
 * Retourne une chaîne HTML sécurisée.
 */
function afficherDeclarant(array $signalement): string
{
    if ((int) $signalement['masquer_identite'] === 1) {
        return '<span class="identite-protegee">Identité protégée</span>';
    }

    $nom = trim(
        htmlspecialchars($signalement['prenom_declarant'] ?? '') . ' ' .
        htmlspecialchars($signalement['nom_declarant']    ?? '')
    );
    $email = htmlspecialchars($signalement['email_declarant'] ?? '');

    if (empty($nom)) {
        return '<em>Non renseigné</em>';
    }

    return $nom . ($email ? '<br><small>' . $email . '</small>' : '');
}

/**
 * Vérifie qu'une valeur appartient à une liste autorisée (whitelist).
 * Protège contre les injections via des valeurs ENUM non contrôlées.
 */
function validerEnum(string $valeur, array $valeursAutorisees): bool
{
    return in_array($valeur, $valeursAutorisees, true);
}

/**
 * Formate une date MySQL en date française lisible.
 */
function dateFr(string $dateMysql, bool $avecHeure = false): string
{
    $format = $avecHeure ? 'd/m/Y à H:i' : 'd/m/Y';
    return date($format, strtotime($dateMysql));
}

/**
 * Retourne le libellé lisible d'une priorité.
 */
function libellePriorite(string $priorite): string
{
    $libelles = [
        'haute'   => 'Haute',
        'normale' => 'Normale',
        'basse'   => 'Basse',
    ];
    return $libelles[$priorite] ?? ucfirst($priorite);
}

/**
 * Retourne la classe CSS correspondant à une priorité.
 */
function classePriorite(string $priorite): string
{
    $classes = [
        'haute'   => 'priorite-haute',
        'normale' => 'priorite-normale',
        'basse'   => 'priorite-basse',
    ];
    return $classes[$priorite] ?? 'priorite-normale';
}