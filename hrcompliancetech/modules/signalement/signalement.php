<?php
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'salarie') {
    header('Location: ../../auth/connexion.html');
    exit;
}

$erreurs = $_SESSION['erreurs_signalement'] ?? [];
unset($_SESSION['erreurs_signalement']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRComplianceTech – Déposer un signalement sécurisé</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="signalement-body">

    <!-- ============================================================
         BARRE DE NAVIGATION – ESPACE PUBLIC (salarié non connecté)
         ============================================================ -->
    <nav class="navbar navbar-expand-md site-navbar" aria-label="Navigation principale">
        <div class="container">

            <a class="navbar-brand brand" href="../../index.html" aria-label="Accueil HRComplianceTech">
                <span class="brand-hr">HR</span><span class="brand-compliance">ComplianceTech</span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu-signalement"
                aria-controls="menu-signalement"
                aria-expanded="false"
                aria-label="Ouvrir le menu"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu-signalement">
                <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="../../dashboards/dashboard-salarie.php">
                            Tableau de bord
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </nav>

    <!-- ============================================================
         CONTENU PRINCIPAL
         ============================================================ -->
    <main class="signalement-main">
        <div class="container">

            <!-- En-tête -->
            <header class="signalement-entete text-center">
                <h1 class="signalement-titre">
                    Déposer un signalement sécurisé
                </h1>
                <p class="signalement-sous-titre">
                    Plateforme de recueil des alertes. Vos données sont chiffrées
                    et votre anonymat est garanti si vous le souhaitez.
                </p>
            </header>

            <!-- Carte formulaire centrée 8/12 -->
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="signalement-carte">

                        <form id="form-signalement" method="POST" action="traitement-signalement.php" novalidate>

                            <?php if (!empty($erreurs)): ?>
                            <div style="background:#fff0f0; border:1px solid #e57373; border-radius:4px; padding:1rem; margin-bottom:1.5rem;">
                                <?php foreach ($erreurs as $err): ?>
                                <p style="color:#c62828; margin:0 0 0.25rem; font-size:0.9rem;"><?= htmlspecialchars($err) ?></p>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>


                            <!-- ================================================
                                 SECTION 1 – COORDONNÉES DU DÉCLARANT
                                 L'email est obligatoire pour permettre le suivi
                                 du dossier. Nom et prenom restent facultatifs.
                                 La case "masquer_identite" permet au declarant
                                 de choisir que son identité ne soit pas affichée
                                 aux agents RH/Juridiques lors du traitement.
                                 ================================================ -->
                            <fieldset class="signalement-section">
                                <legend class="signalement-section-titre">
                                    Vos coordonnées
                                </legend>

                                <p class="field-hint">
                                    Votre adresse e-mail est nécessaire pour assurer
                                    le suivi de votre dossier. Elle est stockée de
                                    manière chiffrée et ne sera jamais transmise
                                    à des tiers. Vous pouvez choisir de masquer
                                    votre identité aux agents chargés du traitement.
                                </p>

                                <div class="row g-3">

                                    <div class="col-12 col-sm-6">
                                        <label for="champ-nom" class="form-label">
                                            Nom
                                            <span class="signalement-section-facultatif">
                                                (facultatif)
                                            </span>
                                        </label>
                                        <input
                                            type="text"
                                            id="champ-nom"
                                            name="nom"
                                            class="form-control custom-input"
                                            placeholder="Votre nom de famille"
                                            autocomplete="family-name"
                                        >
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="champ-prenom" class="form-label">
                                            Prénom
                                            <span class="signalement-section-facultatif">
                                                (facultatif)
                                            </span>
                                        </label>
                                        <input
                                            type="text"
                                            id="champ-prenom"
                                            name="prenom"
                                            class="form-control custom-input"
                                            placeholder="Votre prenom"
                                            autocomplete="given-name"
                                        >
                                    </div>

                                    <div class="col-12">
                                        <label for="champ-email" class="form-label">
                                            Adresse e-mail professionnelle
                                            <span class="champ-obligatoire" aria-hidden="true">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="champ-email"
                                            name="email"
                                            class="form-control custom-input"
                                            placeholder="prenom.nom@entreprise.fr"
                                            autocomplete="email"
                                            required
                                            aria-required="true"
                                            aria-describedby="erreur-email"
                                        >
                                        <span
                                            id="erreur-email"
                                            class="champ-erreur"
                                            role="alert"
                                            aria-live="polite"
                                        ></span>
                                    </div>

                                    <div class="col-12">
                                        <!--
                                            Case à cocher de masquage d'identité.
                                            Non cochée par défaut : le declarant
                                            décide activement de se protéger.
                                            La valeur "1" sera transmise au PHP
                                            comme booléen (masquer_identite = TRUE).
                                        -->
                                        <label class="label-checkbox-masquage" for="masquer-identite">
                                            <input
                                                type="checkbox"
                                                id="masquer-identite"
                                                name="masquer_identite"
                                                value="1"
                                                class="custom-checkbox-input"
                                            >
                                            <span class="label-checkbox-texte">
                                                Je souhaite que mon identité soit masquée
                                                lors du traitement par les services RH/Juridiques
                                            </span>
                                        </label>
                                        <p class="field-hint" style="margin-top: 0.4rem;">
                                            Votre email reste enregistré de manière sécurisée
                                            par l'administrateur système à des fins de traçabilité
                                            légale, mais il ne sera pas affiché aux agents chargés
                                            de l'instruction de votre dossier.
                                        </p>
                                    </div>

                                </div>
                            </fieldset>

                            <hr class="connexion-separateur">

                            <!-- ================================================
                                 SECTION 3 – NATURE DES FAITS
                                 ================================================ -->
                            <fieldset class="signalement-section">
                                <legend class="signalement-section-titre">
                                    Nature des faits
                                </legend>

                                <div class="champ-groupe">
                                    <label for="categorie-signalement" class="form-label">
                                        Catégorie
                                        <span class="champ-obligatoire" aria-hidden="true">*</span>
                                    </label>
                                    <select
                                        id="categorie-signalement"
                                        name="categorie"
                                        class="form-select custom-input"
                                        required
                                        aria-required="true"
                                        aria-describedby="erreur-categorie"
                                    >
                                        <option value="">Sélectionner une categorie</option>
                                        <option value="harcelement">Harcèlement</option>
                                        <option value="discrimination">Discrimination</option>
                                        <option value="fraude">Fraude financière</option>
                                        <option value="environnement">Atteinte à l'environnement</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                    <span
                                        id="erreur-categorie"
                                        class="champ-erreur"
                                        role="alert"
                                        aria-live="polite"
                                    ></span>
                                </div>

                            </fieldset>

                            <hr class="connexion-separateur">

                            <!-- ================================================
                                 SECTION 4 – DESCRIPTION DES FAITS
                                 ================================================ -->
                            <fieldset class="signalement-section">
                                <legend class="signalement-section-titre">
                                    Description des faits
                                </legend>

                                <div class="champ-groupe">
                                    <label for="description-faits" class="form-label">
                                        Récit détaillé
                                        <span class="champ-obligatoire" aria-hidden="true">*</span>
                                    </label>
                                    <p class="field-hint" id="aide-description">
                                        Précisez les dates, lieux, personnes impliquées
                                        et la nature exacte des faits. Toutes ces
                                        informations sont strictement confidentielles.
                                    </p>
                                    <textarea
                                        id="description-faits"
                                        name="description"
                                        class="form-control custom-input"
                                        rows="8"
                                        required
                                        aria-required="true"
                                        aria-describedby="aide-description erreur-description"
                                        placeholder="Détaillez les faits de la manière la plus précise possible : dates, contexte, personnes impliquées..."
                                    ></textarea>
                                    <span
                                        id="erreur-description"
                                        class="champ-erreur"
                                        role="alert"
                                        aria-live="polite"
                                    ></span>
                                </div>

                            </fieldset>

                            <hr class="connexion-separateur">

                            <!-- ================================================
                                 SECTION 5 – ÉLÉMENTS DE PREUVE (facultatif)
                                 ================================================ -->
                            <fieldset class="signalement-section">
                                <legend class="signalement-section-titre">
                                    Éléments de preuve
                                    <span class="signalement-section-facultatif">(facultatif)</span>
                                </legend>

                                <div class="champ-groupe">
                                    <label for="pieces-jointes" class="form-label">
                                        Joindre des fichiers
                                    </label>
                                    <!--
                                        accept= : filtre côté navigateur pour le confort
                                        utilisateur. La vérification réelle des formats
                                        doit être refaite côté serveur en PHP.
                                        multiple : autorise plusieurs fichiers en une sélection.
                                    -->
                                    <input
                                        type="file"
                                        id="pieces-jointes"
                                        name="pieces[]"
                                        class="form-control custom-input"
                                        accept=".pdf,.jpg,.jpeg,.png,.docx"
                                        multiple
                                        aria-describedby="aide-pj erreur-pj"
                                    >
                                    <p class="field-hint" id="aide-pj">
                                        Formats acceptés : PDF, JPG, PNG, DOCX.
                                        Taille maximale : 5 Mo par fichier.
                                    </p>
                                    <span
                                        id="erreur-pj"
                                        class="champ-erreur"
                                        role="alert"
                                        aria-live="polite"
                                    ></span>
                                </div>

                            </fieldset>

                            <hr class="connexion-separateur">

                            <!-- ================================================
                                 SOUMISSION
                                 ================================================ -->
                            <div class="signalement-soumission">

                                <p class="signalement-mention-rgpd">
                                    En soumettant ce formulaire, vous reconnaissez que
                                    vos données seront traitées conformément au
                                    <strong>RGPD</strong> et à la
                                    <strong>loi Sapin 2</strong>, et conservées pendant
                                    une durée maximale de deux ans après la clôture
                                    du dossier.
                                </p>

                                <button
                                    type="submit"
                                    id="btn-soumettre"
                                    class="btn-submit btn-connexion-submit btn-soumettre-signalement"
                                >
                                    Soumettre le signalement de manière sécurisée
                                </button>

                            </div>

                            <!-- ================================================
                                 ÉCRAN DE CONFIRMATION (masqué par défaut)
                                 Rendu visible par le JS après soumission valide.
                                 ================================================ -->
                        </form>

                    </div>
                </div>
            </div>

        </div>

        <!-- Bloc affiché après soumission réussie -->
        <div id="bloc-succes" class="cache">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-7">

                        <a href="../../dashboards/dashboard-salarie.php" class="lien-retour">&larr; Retour au tableau de bord</a>

                        <div style="background:var(--blanc); border:1px solid var(--gris-bordure); border-radius:4px; padding:2.5rem; text-align:center; margin-top:1rem;">
                            <p style="font-size:1.4rem; font-weight:700; color:var(--bleu-marine); margin-bottom:0.75rem;">
                                Signalement réalisé avec succès
                            </p>
                            <p style="color:var(--gris-texte); margin-bottom:2rem; line-height:1.7;">
                                Votre dossier a été transmis de manière sécurisée aux équipes habilitées.<br>
                                Vous pouvez suivre son avancement depuis votre tableau de bord.
                            </p>
                            <a href="../../dashboards/dashboard-salarie.php"
                               class="btn-submit btn-connexion-submit"
                               style="display:inline-block; text-decoration:none; text-align:center;">
                                Voir mon tableau de bord
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </main>

    <footer class="site-footer">
        <div class="container text-center py-3">
            <small>HRComplianceTech &copy; 2026 &mdash; Solution conforme RGPD &amp; Loi Sapin 2 &mdash; Données hébergées sur serveur européen &mdash; <a href="../../mentions-legales.html" style="color:rgba(255,255,255,0.7); text-decoration:underline;">Mentions légales</a></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>