/**
 * HRComplianceTech – script.js
 * Page : Dépôt de signalement
 *
 * Responsabilités :
 *  1. Afficher/masquer les champs identité selon le bouton radio sélectionné.
 *  2. Valider le poids des fichiers joints (max 5 Mo par fichier).
 *  3. Simuler la soumission du formulaire et afficher un accusé de réception.
 */

// On attend que tout le DOM soit chargé avant de manipuler les éléments.
/* ============================================================
   PAGE CONNEXION sélecteur de profil + formulaires
   Garde : on vérifie la présence du sélecteur avant tout.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var selecteur = document.getElementById("div-selecteur");
  if (!selecteur) return; // Ce bloc ne s'exécute que sur connexion.html

  /* ----------------------------------------------------------
       Identifiants de tous les blocs gérés sur cette page
       ---------------------------------------------------------- */
  var VUES = ["div-selecteur", "div-form-salarie", "div-form-rh"];

  /* ----------------------------------------------------------
       FONCTION CENTRALE : afficher une vue, masquer toutes les autres
       On ajoute la classe .cache sur chaque bloc sauf celui demandé.
       ---------------------------------------------------------- */
  function afficherVue(idVueActive) {
    VUES.forEach(function (id) {
      var bloc = document.getElementById(id);
      if (!bloc) return;

      if (id === idVueActive) {
        bloc.classList.remove("cache");
      } else {
        bloc.classList.add("cache");
      }
    });
  }

  /* ----------------------------------------------------------
       ÉCOUTEURS SUR LES BOUTONS DE SÉLECTION DE PROFIL
       Chaque bouton porte data-cible-vue="id-du-bloc-à-afficher".
       ---------------------------------------------------------- */
  var boutonsProfil = document.querySelectorAll("[data-cible-vue]");
  boutonsProfil.forEach(function (bouton) {
    bouton.addEventListener("click", function () {
      var idCible = bouton.getAttribute("data-cible-vue");
      afficherVue(idCible);
    });
  });

  /* ----------------------------------------------------------
       ÉCOUTEURS SUR LES BOUTONS RETOUR
       Tous ramènent vers le sélecteur de profil.
       ---------------------------------------------------------- */
  var boutonsRetour = document.querySelectorAll("[data-retour]");
  boutonsRetour.forEach(function (bouton) {
    bouton.addEventListener("click", function () {
      afficherVue("div-selecteur");
    });
  });

  /* ----------------------------------------------------------
       AFFICHER / MASQUER MOT DE PASSE générique pour N boutons
       ---------------------------------------------------------- */
  var boutonsToggle = document.querySelectorAll("[data-toggle-mdp]");
  boutonsToggle.forEach(function (bouton) {
    bouton.addEventListener("click", function () {
      var idCible = bouton.getAttribute("data-cible");
      var champ = document.getElementById(idCible);
      if (!champ) return;

      var estMasque = champ.type === "password";
      if (estMasque) {
        champ.type = "text";
        bouton.textContent = "Masquer";
        bouton.setAttribute("aria-label", "Masquer le mot de passe");
        bouton.setAttribute("aria-pressed", "true");
      } else {
        champ.type = "password";
        bouton.textContent = "Afficher";
        bouton.setAttribute("aria-label", "Afficher le mot de passe");
        bouton.setAttribute("aria-pressed", "false");
      }
      champ.focus();
    });
  });

  /* ----------------------------------------------------------
       FONCTIONS UTILITAIRES
       ---------------------------------------------------------- */
  function setErreurChamp(element, message) {
    if (!element) return;
    element.textContent = message;
    element.classList.toggle("visible", message !== "");
  }

  /* ----------------------------------------------------------
       VALIDATION GÉNÉRIQUE : reçoit un objet de configuration
       pour éviter de dupliquer la logique entre les deux formulaires
       ---------------------------------------------------------- */
  function validerEtSoumettre(config) {
    var valide = true;

    // Réinitialisation des messages d'erreur
    config.champs.forEach(function (c) {
      setErreurChamp(c.erreur, "");
    });
    setErreurChamp(config.erreurGlobale, "");

    // Validation champ par champ
    config.champs.forEach(function (c) {
      var valeur = c.champ.value.trim();
      if (valeur === "") {
        setErreurChamp(c.erreur, c.messageVide);
        valide = false;
      } else if (c.regex && !c.regex.test(valeur)) {
        setErreurChamp(c.erreur, c.messageInvalide);
        valide = false;
      }
    });

    if (!valide) {
      // Focus sur le premier champ en erreur
      for (var i = 0; i < config.champs.length; i++) {
        if (config.champs[i].erreur.classList.contains("visible")) {
          config.champs[i].champ.focus();
          break;
        }
      }
      return;
    }

    // Simulation d'un appel backend PHP
    // En production : fetch('auth.php', { method:'POST', body: new FormData(form) })
    config.btnSoumettre.disabled = true;
    config.btnSoumettre.textContent = "Connexion en cours…";

    setTimeout(function () {
      setErreurChamp(
        config.erreurGlobale,
        "Identifiants incorrects. Veuillez vérifier vos informations.",
      );
      config.btnSoumettre.disabled = false;
      config.btnSoumettre.textContent = config.texteBouton;
    }, 1000);
  }

  /* ----------------------------------------------------------
       SOUMISSION : FORMULAIRE SALARIÉ
       ---------------------------------------------------------- */
  var formSalarie = document.getElementById("form-salarie");
  if (formSalarie) {
    formSalarie.addEventListener("submit", function (e) {
      var identifiant = document.getElementById("identifiant-salarie");
      var mdp = document.getElementById("mdp-salarie");
      var erreurIdent = document.getElementById("identifiant-salarie-erreur");
      var erreurMdp = document.getElementById("mdp-salarie-erreur");
      var ok = true;

      if (erreurIdent) erreurIdent.textContent = "";
      if (erreurMdp) erreurMdp.textContent = "";

      if (!identifiant || identifiant.value.trim() === "") {
        if (erreurIdent)
          erreurIdent.textContent =
            "Veuillez saisir votre email ou votre code de suivi.";
        ok = false;
      }

      if (!mdp || mdp.value.trim() === "") {
        if (erreurMdp)
          erreurMdp.textContent = "Veuillez saisir votre mot de passe.";
        ok = false;
      }

      if (!ok) {
        e.preventDefault();
      }
      // Si ok = true, le formulaire se soumet normalement vers auth.php
    });
  }

  /* ----------------------------------------------------------
       FORMULAIRE RH / JURIDIQUE
       Pas de listener JS le formulaire se soumet nativement vers auth.php.
       La validation et les erreurs sont gérées côté PHP + paramètre ?erreur=
       ---------------------------------------------------------- */
});

/* ============================================================
   PAGE DASHBOARD – Filtrage du tableau des signalements
   Garde : on vérifie la présence du tableau avant d'aller plus loin.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var tableau = document.getElementById("tableau-signalements");
  if (!tableau) return; // Ce bloc ne s'exécute que sur dashboard.html

  var filtreStatut = document.getElementById("filtre-statut");
  var filtrePriorite = document.getElementById("filtre-priorite");
  var filtreCategorie = document.getElementById("filtre-categorie");
  var filtreTriDate = document.getElementById("filtre-tri-date");
  var btnReinit = document.getElementById("btn-reinit-filtres");
  var compteur = document.getElementById("compteur-resultats");
  var messageVide = document.getElementById("message-vide");

  function appliquerFiltres() {
    var valStatut = filtreStatut.value;
    var valPriorite = filtrePriorite.value;
    var valCategorie = filtreCategorie.value;

    var tbody = tableau.querySelector("tbody");
    var lignes = Array.from(tbody.querySelectorAll("tr"));
    var nbVisibles = 0;

    lignes.forEach(function (ligne) {
      var statut = ligne.getAttribute("data-statut");
      var priorite = ligne.getAttribute("data-priorite");
      var categorie = ligne.getAttribute("data-categorie");

      var ok =
        (valStatut === "" || statut === valStatut) &&
        (valPriorite === "" || priorite === valPriorite) &&
        (valCategorie === "" || categorie === valCategorie);

      ligne.style.display = ok ? "" : "none";
      if (ok) nbVisibles++;
    });

    // Tri par date si demandé
    if (filtreTriDate && filtreTriDate.value !== "") {
      var sens = filtreTriDate.value;
      var lignesVisibles = lignes.filter(function (l) {
        return l.style.display !== "none";
      });
      lignesVisibles.sort(function (a, b) {
        // Convertit "2026-03-16 10:30:00" en timestamp numérique
        var rawA = (a.getAttribute("data-date") || "1970-01-01").replace(
          /\s/,
          "T",
        );
        var rawB = (b.getAttribute("data-date") || "1970-01-01").replace(
          /\s/,
          "T",
        );
        var tA = Date.parse(rawA) || 0;
        var tB = Date.parse(rawB) || 0;
        return sens === "asc" ? tA - tB : tB - tA;
      });
      lignesVisibles.forEach(function (l) {
        tbody.appendChild(l);
      });
    }

    compteur.textContent = nbVisibles + " dossier(s) affiché(s)";
    if (nbVisibles === 0) {
      messageVide.classList.remove("cache");
    } else {
      messageVide.classList.add("cache");
    }
  }

  filtreStatut.addEventListener("change", appliquerFiltres);
  filtrePriorite.addEventListener("change", appliquerFiltres);
  filtreCategorie.addEventListener("change", appliquerFiltres);
  if (filtreTriDate) filtreTriDate.addEventListener("change", appliquerFiltres);

  btnReinit.addEventListener("click", function () {
    filtreStatut.value = "";
    filtrePriorite.value = "";
    filtreCategorie.value = "";
    if (filtreTriDate) filtreTriDate.value = "";
    appliquerFiltres();
  });

  appliquerFiltres();
});

/* ============================================================
   PAGE DOSSIER – Sauvegarde du traitement d'un dossier
   Garde : on vérifie la présence du formulaire de traitement.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formTraitement = document.getElementById("form-traitement");
  if (!formTraitement) return; // Ce bloc ne s'exécute que sur dossier.html

  var btnEnregistrer = document.getElementById("btn-enregistrer");
  var messageConfirmation = document.getElementById("message-sauvegarde");

  // Variable pour stocker le timer et pouvoir l'annuler si besoin
  var timerConfirmation = null;

  formTraitement.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    // Si un timer précédent est encore actif, on l'annule
    // (évite que le message clignote si l'utilisateur sauvegarde plusieurs fois)
    if (timerConfirmation) {
      clearTimeout(timerConfirmation);
    }

    // Simulation d'un envoi au backend PHP
    // En production : fetch('traitement.php', { method:'POST', body: new FormData(formTraitement) })
    btnEnregistrer.disabled = true;
    btnEnregistrer.textContent = "Enregistrement…";

    setTimeout(function () {
      // Affichage du message de confirmation
      messageConfirmation.classList.remove("cache");

      // Réactivation du bouton
      btnEnregistrer.disabled = false;
      btnEnregistrer.textContent = "Enregistrer les modifications";

      // Disparition automatique du message après 3 secondes
      timerConfirmation = setTimeout(function () {
        messageConfirmation.classList.add("cache");
      }, 3000);
    }, 800);
  });
});

/* ============================================================
   PAGE SUIVI – Envoi d'un message dans la messagerie sécurisée
   Garde : on vérifie la présence du formulaire de messagerie.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formMessagerie = document.getElementById("form-messagerie");
  if (!formMessagerie) return; // Ce bloc ne s'exécute que sur suivi.html

  var champMessage = document.getElementById("nouveau-message");
  var zoneMessages = document.getElementById("zone-messages");
  var erreurMessage = document.getElementById("message-erreur");

  formMessagerie.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    // Réinitialisation de l'erreur précédente
    erreurMessage.textContent = "";
    erreurMessage.classList.remove("visible");

    var texte = champMessage.value.trim();

    // Validation : le message ne doit pas être vide
    if (texte === "") {
      erreurMessage.textContent =
        "Veuillez rédiger un message avant de l'envoyer.";
      erreurMessage.classList.add("visible");
      champMessage.focus();
      return;
    }

    /* ----------------------------------------------------------
           Création du nouveau bloc de message avec createElement.

           IMPORTANT Sécurité XSS :
           On n'utilise JAMAIS innerHTML pour insérer le texte saisi
           par l'utilisateur. innerHTML interpréterait des balises HTML
           comme <script> ou <img onerror="...">, ce qui permettrait
           à un attaquant d'injecter du code malveillant.
           On utilise exclusivement textContent qui traite toute entrée
           comme du texte brut, jamais comme du HTML.
           ---------------------------------------------------------- */

    // Création de la structure du message
    var article = document.createElement("article");
    article.className = "message message-envoye";
    article.setAttribute("aria-label", "Message envoyé");

    // En-tête du message
    var entete = document.createElement("header");
    entete.className = "message-entete";

    var expediteur = document.createElement("span");
    expediteur.className = "message-expediteur";
    expediteur.textContent = "Vous"; // textContent, jamais innerHTML

    var maintenant = new Date();
    var horodatage =
      maintenant.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
      }) +
      " à " +
      maintenant.getHours() +
      "h" +
      String(maintenant.getMinutes()).padStart(2, "0");

    var dateElement = document.createElement("time");
    dateElement.className = "message-date";
    dateElement.textContent = horodatage;

    entete.appendChild(expediteur);
    entete.appendChild(dateElement);

    // Corps du message
    var corps = document.createElement("div");
    corps.className = "message-corps";

    var paragraphe = document.createElement("p");
    // textContent garantit que le contenu est traité comme du texte brut
    paragraphe.textContent = texte;

    corps.appendChild(paragraphe);

    // Assemblage du message complet
    article.appendChild(entete);
    article.appendChild(corps);

    // Ajout dans la zone de messages
    zoneMessages.appendChild(article);

    // Défilement automatique vers le bas pour voir le nouveau message
    zoneMessages.scrollTop = zoneMessages.scrollHeight;

    // Réinitialisation du champ de saisie et focus pour un prochain message
    champMessage.value = "";
    champMessage.focus();
  });
});

/* ============================================================
   PAGE MESSAGERIE-RH – Envoi d'un message vers le lanceur d'alerte
   Garde : identifiant de formulaire propre à cette page uniquement.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formMessagerieRhPage = document.getElementById("form-messagerie-rh-page");
  if (!formMessagerieRhPage) return;

  var champMessage = document.getElementById("nouveau-message-messagerie-rh");
  var zoneMessages = document.getElementById("zone-messages-messagerie-rh");
  var erreurMessage = document.getElementById("erreur-messagerie-rh-page");

  formMessagerieRhPage.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    erreurMessage.textContent = "";
    erreurMessage.classList.remove("visible");

    var texte = champMessage.value.trim();

    if (texte === "") {
      erreurMessage.textContent =
        "Veuillez rédiger un message avant de l'envoyer.";
      erreurMessage.classList.add("visible");
      champMessage.focus();
      return;
    }

    /*
            Création du message envoyé (vue RH).
            textContent partout jamais innerHTML pour prévenir les injections XSS.
        */
    var article = document.createElement("article");
    article.className = "message message-envoye";
    article.setAttribute("aria-label", "Message envoyé par le service RH");

    var entete = document.createElement("header");
    entete.className = "message-entete";

    var expediteur = document.createElement("span");
    expediteur.className = "message-expediteur";
    expediteur.textContent = "Service RH C. Moreau";

    var maintenant = new Date();
    var horodatage =
      maintenant.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
      }) +
      " à " +
      maintenant.getHours() +
      "h" +
      String(maintenant.getMinutes()).padStart(2, "0");

    var dateElement = document.createElement("time");
    dateElement.className = "message-date";
    dateElement.textContent = horodatage;

    entete.appendChild(expediteur);
    entete.appendChild(dateElement);

    var corps = document.createElement("div");
    corps.className = "message-corps";

    var paragraphe = document.createElement("p");
    paragraphe.textContent = texte;
    corps.appendChild(paragraphe);

    article.appendChild(entete);
    article.appendChild(corps);

    zoneMessages.appendChild(article);

    // Défilement vers le bas pour rendre le nouveau message visible
    zoneMessages.scrollTop = zoneMessages.scrollHeight;

    champMessage.value = "";
    champMessage.focus();
  });
});

/* ============================================================
   PAGE DOSSIER JURISTE – Sauvegarde des annotations légales
   et validation de clôture du dossier.
   Garde : on vérifie la présence des éléments de cette page.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  /* ----------------------------------------------------------
       BLOC 1 : Sauvegarde des annotations légales
       ---------------------------------------------------------- */
  var formAnnotations = document.getElementById("form-annotations-juriste");
  if (formAnnotations) {
    var btnAnnotations = document.getElementById("btn-sauvegarder-annotations");
    var messageAnnotations = document.getElementById(
      "message-sauvegarde-annotations",
    );
    var timerAnnotations = null;

    formAnnotations.addEventListener("submit", function (evenement) {
      evenement.preventDefault();

      if (timerAnnotations) clearTimeout(timerAnnotations);

      btnAnnotations.disabled = true;
      btnAnnotations.textContent = "Enregistrement…";

      // Simulation de l'envoi au backend PHP
      setTimeout(function () {
        messageAnnotations.classList.remove("cache");
        btnAnnotations.disabled = false;
        btnAnnotations.textContent = "Sauvegarder les annotations";

        timerAnnotations = setTimeout(function () {
          messageAnnotations.classList.add("cache");
        }, 3000);
      }, 800);
    });
  }

  /* ----------------------------------------------------------
       BLOC 2 : Validation de clôture du dossier
       La clôture nécessite qu'un motif soit sélectionné.
       ---------------------------------------------------------- */
  var btnCloture = document.getElementById("btn-valider-cloture");
  if (btnCloture) {
    var selectMotif = document.getElementById("select-motif-cloture");
    var erreurMotif = document.getElementById("erreur-motif-cloture");
    var messageCloture = document.getElementById("message-cloture");

    btnCloture.addEventListener("click", function () {
      // Réinitialisation du message d'erreur
      erreurMotif.textContent = "";
      erreurMotif.classList.remove("visible");

      // Validation : un motif doit être sélectionné avant de clôturer
      if (selectMotif.value === "") {
        erreurMotif.textContent =
          "Veuillez sélectionner un motif de clôture avant de valider.";
        erreurMotif.classList.add("visible");
        selectMotif.focus();
        return;
      }

      // Confirmation explicite : acte irréversible
      var confirme = window.confirm(
        "Vous êtes sur le point de clôturer définitivement le dossier HRC-1042.\n\n" +
          "Motif sélectionné : " +
          selectMotif.options[selectMotif.selectedIndex].text +
          "\n\n" +
          "Cette action sera consignée dans le journal d'audit avec horodatage.\n" +
          "Confirmez-vous la clôture ?",
      );

      if (!confirme) return;

      // Désactivation des contrôles après validation la clôture est irréversible
      btnCloture.disabled = true;
      btnCloture.style.opacity = "0.6";
      selectMotif.disabled = true;

      messageCloture.classList.remove("cache");
    });
  }
});

/* ============================================================
   PAGE DOSSIER JURISTE – Ajout dynamique d'une annotation
   et validation de clôture du dossier.
   Garde : on vérifie la présence du formulaire d'annotation.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formAnnotation = document.getElementById("form-nouvelle-annotation");
  if (!formAnnotation) return;

  var champAnnotation = document.getElementById("nouvelle-annotation");
  var erreurAnnotation = document.getElementById("erreur-annotation");
  var historique = document.querySelector(".annotations-historique");
  var btnAjouter = document.getElementById("btn-ajouter-annotation");

  /* ----------------------------------------------------------
       AJOUT D'UNE NOUVELLE ANNOTATION DANS L'HISTORIQUE
       On construit le bloc avec createElement + textContent
       pour éviter toute injection XSS.
       ---------------------------------------------------------- */
  formAnnotation.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    erreurAnnotation.textContent = "";
    erreurAnnotation.classList.remove("visible");

    var texte = champAnnotation.value.trim();

    if (texte === "") {
      erreurAnnotation.textContent =
        "Veuillez rédiger une annotation avant de l'ajouter.";
      erreurAnnotation.classList.add("visible");
      champAnnotation.focus();
      return;
    }

    btnAjouter.disabled = true;
    btnAjouter.textContent = "Enregistrement…";

    // Simulation de l'envoi au backend PHP
    setTimeout(function () {
      // Horodatage de la nouvelle annotation
      var maintenant = new Date();
      var horodatage =
        maintenant.toLocaleDateString("fr-FR", {
          day: "numeric",
          month: "long",
          year: "numeric",
        }) +
        " à " +
        maintenant.getHours() +
        "h" +
        String(maintenant.getMinutes()).padStart(2, "0");

      // Construction du bloc annotation
      var article = document.createElement("article");
      article.className = "annotation-entree";
      article.setAttribute("aria-label", "Annotation du " + horodatage);

      var entete = document.createElement("header");
      entete.className = "annotation-entete";

      var auteur = document.createElement("span");
      auteur.className = "annotation-auteur";
      auteur.textContent = "P. Durand Juriste";

      var dateElement = document.createElement("time");
      dateElement.className = "message-date";
      dateElement.textContent = horodatage;

      entete.appendChild(auteur);
      entete.appendChild(dateElement);

      var corps = document.createElement("div");
      corps.className = "annotation-corps";

      var paragraphe = document.createElement("p");
      paragraphe.textContent = texte; // textContent : jamais innerHTML
      corps.appendChild(paragraphe);

      article.appendChild(entete);
      article.appendChild(corps);

      // Ajout à la fin de l'historique
      historique.appendChild(article);

      // Défilement vers la nouvelle annotation
      article.scrollIntoView({ behavior: "smooth", block: "nearest" });

      // Réinitialisation du formulaire
      champAnnotation.value = "";
      btnAjouter.disabled = false;
      btnAjouter.textContent = "Ajouter l'annotation";
    }, 600);
  });

  /* ----------------------------------------------------------
       VALIDATION DE CLÔTURE DU DOSSIER
       ---------------------------------------------------------- */
  var btnCloture = document.getElementById("btn-valider-cloture-j");
  if (!btnCloture) return;

  var selectMotif = document.getElementById("motif-cloture-j");
  var erreurMotif = document.getElementById("erreur-motif-j");
  var messageCloture = document.getElementById("message-cloture-j");

  btnCloture.addEventListener("click", function () {
    erreurMotif.textContent = "";
    erreurMotif.classList.remove("visible");

    // Un motif est obligatoire avant toute clôture
    if (selectMotif.value === "") {
      erreurMotif.textContent =
        "Veuillez sélectionner un motif de clôture avant de valider.";
      erreurMotif.classList.add("visible");
      selectMotif.focus();
      return;
    }

    // Confirmation explicite : acte irréversible
    var motifTexte = selectMotif.options[selectMotif.selectedIndex].text;
    var confirme = window.confirm(
      "Vous êtes sur le point de clôturer définitivement le dossier HRC-1042.\n\n" +
        "Motif : " +
        motifTexte +
        "\n\n" +
        "Cette action est irréversible et sera consignée dans le journal d'audit " +
        "avec votre identité et un horodatage.\n\n" +
        "Confirmez-vous la validation légale et la clôture de ce dossier ?",
    );

    if (!confirme) return;

    // Désactivation de tous les contrôles après validation
    btnCloture.disabled = true;
    btnCloture.style.opacity = "0.6";
    selectMotif.disabled = true;
    champAnnotation.disabled = true;
    btnAjouter.disabled = true;

    messageCloture.classList.remove("cache");
  });
});

/* ============================================================
   PAGE LOGS D'AUDIT – Filtrage combiné (select + recherche texte)
   Garde : on vérifie la présence du tableau de logs.
   ============================================================ -->
   Cette page combine trois filtres simultanés :
     1. Un <select> par type d'action
     2. Un <select> par utilisateur
     3. Un <input type="search"> pour la recherche libre dans data-texte
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var tableauLogs = document.getElementById("tableau-logs");
  if (!tableauLogs) return;

  var filtreAction = document.getElementById("filtre-action");
  var filtreUtilisateur = document.getElementById("filtre-utilisateur");
  var filtreRecherche = document.getElementById("filtre-recherche");
  var btnReinit = document.getElementById("btn-reinit-logs");
  var compteur = document.getElementById("compteur-logs");
  var messageVide = document.getElementById("message-vide-logs");

  /* ----------------------------------------------------------
       FONCTION CENTRALE : appliquer les trois filtres ensemble.
       Pour chaque ligne, on vérifie les trois conditions
       simultanément. La ligne est visible uniquement si
       les trois conditions sont vraies en même temps.
       ---------------------------------------------------------- */
  function appliquerFiltresLogs() {
    var valAction = filtreAction.value;
    var valUtilisateur = filtreUtilisateur.value;
    /*
            Recherche libre : on met tout en minuscules pour une
            comparaison insensible à la casse.
        */
    var valRecherche = filtreRecherche.value.trim().toLowerCase();

    var lignes = tableauLogs.querySelectorAll("tbody tr");
    var nbVisibles = 0;

    lignes.forEach(function (ligne) {
      var action = ligne.getAttribute("data-action");
      var utilisateur = ligne.getAttribute("data-utilisateur");
      /*
                data-texte contient une version normalisée (minuscules)
                de toutes les cellules de la ligne, préparée dans le HTML.
                On y cherche la chaîne saisie avec includes().
            */
      var texte = ligne.getAttribute("data-texte") || "";

      var okAction = valAction === "" || action === valAction;
      var okUtilisateur =
        valUtilisateur === "" || utilisateur === valUtilisateur;
      var okRecherche = valRecherche === "" || texte.includes(valRecherche);

      if (okAction && okUtilisateur && okRecherche) {
        ligne.style.display = "";
        nbVisibles++;
      } else {
        ligne.style.display = "none";
      }
    });

    compteur.textContent = nbVisibles + " entrée(s) affichée(s)";

    if (nbVisibles === 0) {
      messageVide.classList.remove("cache");
    } else {
      messageVide.classList.add("cache");
    }
  }

  /* ----------------------------------------------------------
       ÉCOUTEURS sur les trois contrôles de filtrage.
       L'événement 'input' sur le champ de recherche déclenche
       le filtrage à chaque frappe, sans attendre une validation.
       ---------------------------------------------------------- */
  filtreAction.addEventListener("change", appliquerFiltresLogs);
  filtreUtilisateur.addEventListener("change", appliquerFiltresLogs);
  filtreRecherche.addEventListener("input", appliquerFiltresLogs);

  /* ----------------------------------------------------------
       RÉINITIALISATION : remet les trois contrôles à zéro.
       ---------------------------------------------------------- */
  btnReinit.addEventListener("click", function () {
    filtreAction.value = "";
    filtreUtilisateur.value = "";
    filtreRecherche.value = "";
    appliquerFiltresLogs();
  });

  // Initialisation du compteur au chargement
  appliquerFiltresLogs();
});

/* ============================================================
   PAGE ADMIN – Affichage/masquage du formulaire d'ajout
   et création d'un nouveau compte utilisateur.
   Garde : on vérifie la présence du bouton d'ajout.
   ============================================================ */
/* ============================================================
   PAGE SIGNALEMENT - gestion anonymat et validation legere
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formSignalement = document.getElementById("form-signalement");
  if (!formSignalement) return;

  var radioAnonyme = document.getElementById("radio-anonyme");
  var radioIdentifie = document.getElementById("radio-identifie");
  var blocIdentite = document.getElementById("bloc-identite");

  function gererAnonyme() {
    if (!radioAnonyme || !radioIdentifie || !blocIdentite) return;
    if (radioIdentifie.checked) {
      blocIdentite.classList.add("visible");
      blocIdentite.setAttribute("aria-hidden", "false");
    } else {
      blocIdentite.classList.remove("visible");
      blocIdentite.setAttribute("aria-hidden", "true");
      blocIdentite.querySelectorAll("input").forEach(function (c) {
        c.required = false;
        c.value = "";
      });
    }
  }

  if (radioAnonyme) radioAnonyme.addEventListener("change", gererAnonyme);
  if (radioIdentifie) radioIdentifie.addEventListener("change", gererAnonyme);
  gererAnonyme();
});

/* ============================================================
   PAGE DOSSIER – Sauvegarde du traitement d'un dossier
   Garde : on vérifie la présence du formulaire de traitement.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formTraitement = document.getElementById("form-traitement");
  if (!formTraitement) return; // Ce bloc ne s'exécute que sur dossier.html

  var btnEnregistrer = document.getElementById("btn-enregistrer");
  var messageConfirmation = document.getElementById("message-sauvegarde");

  // Variable pour stocker le timer et pouvoir l'annuler si besoin
  var timerConfirmation = null;

  formTraitement.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    // Si un timer précédent est encore actif, on l'annule
    // (évite que le message clignote si l'utilisateur sauvegarde plusieurs fois)
    if (timerConfirmation) {
      clearTimeout(timerConfirmation);
    }

    // Simulation d'un envoi au backend PHP
    // En production : fetch('traitement.php', { method:'POST', body: new FormData(formTraitement) })
    btnEnregistrer.disabled = true;
    btnEnregistrer.textContent = "Enregistrement…";

    setTimeout(function () {
      // Affichage du message de confirmation
      messageConfirmation.classList.remove("cache");

      // Réactivation du bouton
      btnEnregistrer.disabled = false;
      btnEnregistrer.textContent = "Enregistrer les modifications";

      // Disparition automatique du message après 3 secondes
      timerConfirmation = setTimeout(function () {
        messageConfirmation.classList.add("cache");
      }, 3000);
    }, 800);
  });
});

/* ============================================================
   PAGE SUIVI – Envoi d'un message dans la messagerie sécurisée
   Garde : on vérifie la présence du formulaire de messagerie.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formMessagerie = document.getElementById("form-messagerie");
  if (!formMessagerie) return; // Ce bloc ne s'exécute que sur suivi.html

  var champMessage = document.getElementById("nouveau-message");
  var zoneMessages = document.getElementById("zone-messages");
  var erreurMessage = document.getElementById("message-erreur");

  formMessagerie.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    // Réinitialisation de l'erreur précédente
    erreurMessage.textContent = "";
    erreurMessage.classList.remove("visible");

    var texte = champMessage.value.trim();

    // Validation : le message ne doit pas être vide
    if (texte === "") {
      erreurMessage.textContent =
        "Veuillez rédiger un message avant de l'envoyer.";
      erreurMessage.classList.add("visible");
      champMessage.focus();
      return;
    }

    /* ----------------------------------------------------------
           Création du nouveau bloc de message avec createElement.

           IMPORTANT Sécurité XSS :
           On n'utilise JAMAIS innerHTML pour insérer le texte saisi
           par l'utilisateur. innerHTML interpréterait des balises HTML
           comme <script> ou <img onerror="...">, ce qui permettrait
           à un attaquant d'injecter du code malveillant.
           On utilise exclusivement textContent qui traite toute entrée
           comme du texte brut, jamais comme du HTML.
           ---------------------------------------------------------- */

    // Création de la structure du message
    var article = document.createElement("article");
    article.className = "message message-envoye";
    article.setAttribute("aria-label", "Message envoyé");

    // En-tête du message
    var entete = document.createElement("header");
    entete.className = "message-entete";

    var expediteur = document.createElement("span");
    expediteur.className = "message-expediteur";
    expediteur.textContent = "Vous"; // textContent, jamais innerHTML

    var maintenant = new Date();
    var horodatage =
      maintenant.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
      }) +
      " à " +
      maintenant.getHours() +
      "h" +
      String(maintenant.getMinutes()).padStart(2, "0");

    var dateElement = document.createElement("time");
    dateElement.className = "message-date";
    dateElement.textContent = horodatage;

    entete.appendChild(expediteur);
    entete.appendChild(dateElement);

    // Corps du message
    var corps = document.createElement("div");
    corps.className = "message-corps";

    var paragraphe = document.createElement("p");
    // textContent garantit que le contenu est traité comme du texte brut
    paragraphe.textContent = texte;

    corps.appendChild(paragraphe);

    // Assemblage du message complet
    article.appendChild(entete);
    article.appendChild(corps);

    // Ajout dans la zone de messages
    zoneMessages.appendChild(article);

    // Défilement automatique vers le bas pour voir le nouveau message
    zoneMessages.scrollTop = zoneMessages.scrollHeight;

    // Réinitialisation du champ de saisie et focus pour un prochain message
    champMessage.value = "";
    champMessage.focus();
  });
});

/* ============================================================
   PAGE MESSAGERIE-RH – Envoi d'un message vers le lanceur d'alerte
   Garde : identifiant de formulaire propre à cette page uniquement.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formMessagerieRhPage = document.getElementById("form-messagerie-rh-page");
  if (!formMessagerieRhPage) return;

  var champMessage = document.getElementById("nouveau-message-messagerie-rh");
  var zoneMessages = document.getElementById("zone-messages-messagerie-rh");
  var erreurMessage = document.getElementById("erreur-messagerie-rh-page");

  formMessagerieRhPage.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    erreurMessage.textContent = "";
    erreurMessage.classList.remove("visible");

    var texte = champMessage.value.trim();

    if (texte === "") {
      erreurMessage.textContent =
        "Veuillez rédiger un message avant de l'envoyer.";
      erreurMessage.classList.add("visible");
      champMessage.focus();
      return;
    }

    /*
            Création du message envoyé (vue RH).
            textContent partout jamais innerHTML pour prévenir les injections XSS.
        */
    var article = document.createElement("article");
    article.className = "message message-envoye";
    article.setAttribute("aria-label", "Message envoyé par le service RH");

    var entete = document.createElement("header");
    entete.className = "message-entete";

    var expediteur = document.createElement("span");
    expediteur.className = "message-expediteur";
    expediteur.textContent = "Service RH C. Moreau";

    var maintenant = new Date();
    var horodatage =
      maintenant.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
      }) +
      " à " +
      maintenant.getHours() +
      "h" +
      String(maintenant.getMinutes()).padStart(2, "0");

    var dateElement = document.createElement("time");
    dateElement.className = "message-date";
    dateElement.textContent = horodatage;

    entete.appendChild(expediteur);
    entete.appendChild(dateElement);

    var corps = document.createElement("div");
    corps.className = "message-corps";

    var paragraphe = document.createElement("p");
    paragraphe.textContent = texte;
    corps.appendChild(paragraphe);

    article.appendChild(entete);
    article.appendChild(corps);

    zoneMessages.appendChild(article);

    // Défilement vers le bas pour rendre le nouveau message visible
    zoneMessages.scrollTop = zoneMessages.scrollHeight;

    champMessage.value = "";
    champMessage.focus();
  });
});

/* ============================================================
   PAGE DOSSIER JURISTE – Sauvegarde des annotations légales
   et validation de clôture du dossier.
   Garde : on vérifie la présence des éléments de cette page.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  /* ----------------------------------------------------------
       BLOC 1 : Sauvegarde des annotations légales
       ---------------------------------------------------------- */
  var formAnnotations = document.getElementById("form-annotations-juriste");
  if (formAnnotations) {
    var btnAnnotations = document.getElementById("btn-sauvegarder-annotations");
    var messageAnnotations = document.getElementById(
      "message-sauvegarde-annotations",
    );
    var timerAnnotations = null;

    formAnnotations.addEventListener("submit", function (evenement) {
      evenement.preventDefault();

      if (timerAnnotations) clearTimeout(timerAnnotations);

      btnAnnotations.disabled = true;
      btnAnnotations.textContent = "Enregistrement…";

      // Simulation de l'envoi au backend PHP
      setTimeout(function () {
        messageAnnotations.classList.remove("cache");
        btnAnnotations.disabled = false;
        btnAnnotations.textContent = "Sauvegarder les annotations";

        timerAnnotations = setTimeout(function () {
          messageAnnotations.classList.add("cache");
        }, 3000);
      }, 800);
    });
  }

  /* ----------------------------------------------------------
       BLOC 2 : Validation de clôture du dossier
       La clôture nécessite qu'un motif soit sélectionné.
       ---------------------------------------------------------- */
  var btnCloture = document.getElementById("btn-valider-cloture");
  if (btnCloture) {
    var selectMotif = document.getElementById("select-motif-cloture");
    var erreurMotif = document.getElementById("erreur-motif-cloture");
    var messageCloture = document.getElementById("message-cloture");

    btnCloture.addEventListener("click", function () {
      // Réinitialisation du message d'erreur
      erreurMotif.textContent = "";
      erreurMotif.classList.remove("visible");

      // Validation : un motif doit être sélectionné avant de clôturer
      if (selectMotif.value === "") {
        erreurMotif.textContent =
          "Veuillez sélectionner un motif de clôture avant de valider.";
        erreurMotif.classList.add("visible");
        selectMotif.focus();
        return;
      }

      // Confirmation explicite : acte irréversible
      var confirme = window.confirm(
        "Vous êtes sur le point de clôturer définitivement le dossier HRC-1042.\n\n" +
          "Motif sélectionné : " +
          selectMotif.options[selectMotif.selectedIndex].text +
          "\n\n" +
          "Cette action sera consignée dans le journal d'audit avec horodatage.\n" +
          "Confirmez-vous la clôture ?",
      );

      if (!confirme) return;

      // Désactivation des contrôles après validation la clôture est irréversible
      btnCloture.disabled = true;
      btnCloture.style.opacity = "0.6";
      selectMotif.disabled = true;

      messageCloture.classList.remove("cache");
    });
  }
});

/* ============================================================
   PAGE DOSSIER JURISTE – Ajout dynamique d'une annotation
   et validation de clôture du dossier.
   Garde : on vérifie la présence du formulaire d'annotation.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formAnnotation = document.getElementById("form-nouvelle-annotation");
  if (!formAnnotation) return;

  var champAnnotation = document.getElementById("nouvelle-annotation");
  var erreurAnnotation = document.getElementById("erreur-annotation");
  var historique = document.querySelector(".annotations-historique");
  var btnAjouter = document.getElementById("btn-ajouter-annotation");

  /* ----------------------------------------------------------
       AJOUT D'UNE NOUVELLE ANNOTATION DANS L'HISTORIQUE
       On construit le bloc avec createElement + textContent
       pour éviter toute injection XSS.
       ---------------------------------------------------------- */
  formAnnotation.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    erreurAnnotation.textContent = "";
    erreurAnnotation.classList.remove("visible");

    var texte = champAnnotation.value.trim();

    if (texte === "") {
      erreurAnnotation.textContent =
        "Veuillez rédiger une annotation avant de l'ajouter.";
      erreurAnnotation.classList.add("visible");
      champAnnotation.focus();
      return;
    }

    btnAjouter.disabled = true;
    btnAjouter.textContent = "Enregistrement…";

    // Simulation de l'envoi au backend PHP
    setTimeout(function () {
      // Horodatage de la nouvelle annotation
      var maintenant = new Date();
      var horodatage =
        maintenant.toLocaleDateString("fr-FR", {
          day: "numeric",
          month: "long",
          year: "numeric",
        }) +
        " à " +
        maintenant.getHours() +
        "h" +
        String(maintenant.getMinutes()).padStart(2, "0");

      // Construction du bloc annotation
      var article = document.createElement("article");
      article.className = "annotation-entree";
      article.setAttribute("aria-label", "Annotation du " + horodatage);

      var entete = document.createElement("header");
      entete.className = "annotation-entete";

      var auteur = document.createElement("span");
      auteur.className = "annotation-auteur";
      auteur.textContent = "P. Durand Juriste";

      var dateElement = document.createElement("time");
      dateElement.className = "message-date";
      dateElement.textContent = horodatage;

      entete.appendChild(auteur);
      entete.appendChild(dateElement);

      var corps = document.createElement("div");
      corps.className = "annotation-corps";

      var paragraphe = document.createElement("p");
      paragraphe.textContent = texte; // textContent : jamais innerHTML
      corps.appendChild(paragraphe);

      article.appendChild(entete);
      article.appendChild(corps);

      // Ajout à la fin de l'historique
      historique.appendChild(article);

      // Défilement vers la nouvelle annotation
      article.scrollIntoView({ behavior: "smooth", block: "nearest" });

      // Réinitialisation du formulaire
      champAnnotation.value = "";
      btnAjouter.disabled = false;
      btnAjouter.textContent = "Ajouter l'annotation";
    }, 600);
  });

  /* ----------------------------------------------------------
       VALIDATION DE CLÔTURE DU DOSSIER
       ---------------------------------------------------------- */
  var btnCloture = document.getElementById("btn-valider-cloture-j");
  if (!btnCloture) return;

  var selectMotif = document.getElementById("motif-cloture-j");
  var erreurMotif = document.getElementById("erreur-motif-j");
  var messageCloture = document.getElementById("message-cloture-j");

  btnCloture.addEventListener("click", function () {
    erreurMotif.textContent = "";
    erreurMotif.classList.remove("visible");

    // Un motif est obligatoire avant toute clôture
    if (selectMotif.value === "") {
      erreurMotif.textContent =
        "Veuillez sélectionner un motif de clôture avant de valider.";
      erreurMotif.classList.add("visible");
      selectMotif.focus();
      return;
    }

    // Confirmation explicite : acte irréversible
    var motifTexte = selectMotif.options[selectMotif.selectedIndex].text;
    var confirme = window.confirm(
      "Vous êtes sur le point de clôturer définitivement le dossier HRC-1042.\n\n" +
        "Motif : " +
        motifTexte +
        "\n\n" +
        "Cette action est irréversible et sera consignée dans le journal d'audit " +
        "avec votre identité et un horodatage.\n\n" +
        "Confirmez-vous la validation légale et la clôture de ce dossier ?",
    );

    if (!confirme) return;

    // Désactivation de tous les contrôles après validation
    btnCloture.disabled = true;
    btnCloture.style.opacity = "0.6";
    selectMotif.disabled = true;
    champAnnotation.disabled = true;
    btnAjouter.disabled = true;

    messageCloture.classList.remove("cache");
  });
});

/* ============================================================
   PAGE LOGS D'AUDIT – Filtrage combiné (select + recherche texte)
   Garde : on vérifie la présence du tableau de logs.
   ============================================================ -->
   Cette page combine trois filtres simultanés :
     1. Un <select> par type d'action
     2. Un <select> par utilisateur
     3. Un <input type="search"> pour la recherche libre dans data-texte
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var tableauLogs = document.getElementById("tableau-logs");
  if (!tableauLogs) return;

  var filtreAction = document.getElementById("filtre-action");
  var filtreUtilisateur = document.getElementById("filtre-utilisateur");
  var filtreRecherche = document.getElementById("filtre-recherche");
  var btnReinit = document.getElementById("btn-reinit-logs");
  var compteur = document.getElementById("compteur-logs");
  var messageVide = document.getElementById("message-vide-logs");

  /* ----------------------------------------------------------
       FONCTION CENTRALE : appliquer les trois filtres ensemble.
       Pour chaque ligne, on vérifie les trois conditions
       simultanément. La ligne est visible uniquement si
       les trois conditions sont vraies en même temps.
       ---------------------------------------------------------- */
  function appliquerFiltresLogs() {
    var valAction = filtreAction.value;
    var valUtilisateur = filtreUtilisateur.value;
    /*
            Recherche libre : on met tout en minuscules pour une
            comparaison insensible à la casse.
        */
    var valRecherche = filtreRecherche.value.trim().toLowerCase();

    var lignes = tableauLogs.querySelectorAll("tbody tr");
    var nbVisibles = 0;

    lignes.forEach(function (ligne) {
      var action = ligne.getAttribute("data-action");
      var utilisateur = ligne.getAttribute("data-utilisateur");
      /*
                data-texte contient une version normalisée (minuscules)
                de toutes les cellules de la ligne, préparée dans le HTML.
                On y cherche la chaîne saisie avec includes().
            */
      var texte = ligne.getAttribute("data-texte") || "";

      var okAction = valAction === "" || action === valAction;
      var okUtilisateur =
        valUtilisateur === "" || utilisateur === valUtilisateur;
      var okRecherche = valRecherche === "" || texte.includes(valRecherche);

      if (okAction && okUtilisateur && okRecherche) {
        ligne.style.display = "";
        nbVisibles++;
      } else {
        ligne.style.display = "none";
      }
    });

    compteur.textContent = nbVisibles + " entrée(s) affichée(s)";

    if (nbVisibles === 0) {
      messageVide.classList.remove("cache");
    } else {
      messageVide.classList.add("cache");
    }
  }

  /* ----------------------------------------------------------
       ÉCOUTEURS sur les trois contrôles de filtrage.
       L'événement 'input' sur le champ de recherche déclenche
       le filtrage à chaque frappe, sans attendre une validation.
       ---------------------------------------------------------- */
  filtreAction.addEventListener("change", appliquerFiltresLogs);
  filtreUtilisateur.addEventListener("change", appliquerFiltresLogs);
  filtreRecherche.addEventListener("input", appliquerFiltresLogs);

  /* ----------------------------------------------------------
       RÉINITIALISATION : remet les trois contrôles à zéro.
       ---------------------------------------------------------- */
  btnReinit.addEventListener("click", function () {
    filtreAction.value = "";
    filtreUtilisateur.value = "";
    filtreRecherche.value = "";
    appliquerFiltresLogs();
  });

  // Initialisation du compteur au chargement
  appliquerFiltresLogs();
});

/* ============================================================
   PAGE ADMIN – Affichage/masquage du formulaire d'ajout
   et création d'un nouveau compte utilisateur.
   Garde : on vérifie la présence du bouton d'ajout.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var btnAjouter = document.getElementById("btn-ajouter-collaborateur");
  if (!btnAjouter) return;

  var formAjout = document.getElementById("form-ajout-collaborateur");
  var btnAnnuler = document.getElementById("btn-annuler-ajout");
  var formUtilisateur = document.getElementById("form-nouveau-utilisateur");
  var erreurForm = document.getElementById("erreur-form-utilisateur");
  var confirmationAjout = document.getElementById("confirmation-ajout");
  var timerConfirmation = null;

  /* ----------------------------------------------------------
       Afficher le formulaire d'ajout au clic sur le bouton principal
       ---------------------------------------------------------- */
  btnAjouter.addEventListener("click", function () {
    formAjout.classList.remove("cache");
    formAjout.querySelector("input").focus();
    btnAjouter.disabled = true;
  });

  /* ----------------------------------------------------------
       Masquer le formulaire si l'utilisateur annule
       ---------------------------------------------------------- */
  btnAnnuler.addEventListener("click", function () {
    formAjout.classList.add("cache");
    formUtilisateur.reset();
    erreurForm.textContent = "";
    erreurForm.classList.remove("visible");
    confirmationAjout.classList.add("cache");
    btnAjouter.disabled = false;
  });

  /* ----------------------------------------------------------
       Soumission du formulaire de création de compte
       ---------------------------------------------------------- */
  formUtilisateur.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    erreurForm.textContent = "";
    erreurForm.classList.remove("visible");

    var nom = document.getElementById("new-nom").value.trim();
    var email = document.getElementById("new-email").value.trim();
    var role = document.getElementById("new-role").value;

    if (!nom || !email || !role) {
      erreurForm.textContent = "Tous les champs sont obligatoires.";
      erreurForm.classList.add("visible");
      return;
    }

    // Simulation de la création côté PHP
    if (timerConfirmation) clearTimeout(timerConfirmation);

    confirmationAjout.classList.remove("cache");

    timerConfirmation = setTimeout(function () {
      formAjout.classList.add("cache");
      formUtilisateur.reset();
      confirmationAjout.classList.add("cache");
      btnAjouter.disabled = false;
    }, 2500);
  });
});

/* ============================================================
   PAGE SIGNALEMENT – Logique complète du formulaire
   Garde : on vérifie la présence du formulaire avant tout.
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  var formSignalement = document.getElementById("form-signalement");
  if (!formSignalement) return;

  /* ----------------------------------------------------------
       Références aux éléments du formulaire
       ---------------------------------------------------------- */
  var radioAnonyme = document.getElementById("radio-anonyme");
  var radioIdentifie = document.getElementById("radio-identifie");
  var blocIdentite = document.getElementById("bloc-identite");
  var description = document.getElementById("description-faits");
  var compteur = document.getElementById("compteur-caracteres");
  var categorieSelect = document.getElementById("categorie-signalement");
  var inputFichiers = document.getElementById("pieces-jointes");
  var listeFichiers = document.getElementById("liste-fichiers");
  var erreurCategorie = document.getElementById("erreur-categorie");
  var erreurDescription = document.getElementById("erreur-description");
  var erreurEmail = document.getElementById("erreur-email");
  var erreurPj = document.getElementById("erreur-pj");
  var btnSoumettre = document.getElementById("btn-soumettre");
  /* ----------------------------------------------------------
       Affichage/masquage du bloc identité selon le radio coché
       ---------------------------------------------------------- */
  function gererAffichageIdentite() {
    if (radioIdentifie.checked) {
      blocIdentite.classList.remove("cache");
    } else {
      blocIdentite.classList.add("cache");
    }
  }

  radioAnonyme.addEventListener("change", gererAffichageIdentite);
  radioIdentifie.addEventListener("change", gererAffichageIdentite);

  /* ----------------------------------------------------------
       Compteur de caractères sur le textarea
       ---------------------------------------------------------- */
  description.addEventListener("input", function () {
    compteur.textContent = description.value.length;
  });

  /* ----------------------------------------------------------
       Affichage des fichiers sélectionnés
       Extensions autorisées : pdf, jpg, jpeg, png, docx.
       Taille max : 5 Mo par fichier.
       ---------------------------------------------------------- */
  var extensionsAutorisees = ["pdf", "jpg", "jpeg", "png", "docx"];
  var tailleMaxOctet = 5 * 1024 * 1024; // 5 Mo

  inputFichiers.addEventListener("change", function () {
    listeFichiers.innerHTML = "";
    erreurPj.textContent = "";
    erreurPj.classList.remove("visible");

    var fichiers = Array.from(inputFichiers.files);

    if (fichiers.length === 0) {
      listeFichiers.classList.add("cache");
      return;
    }

    var erreur = false;

    fichiers.forEach(function (fichier) {
      var extension = fichier.name.split(".").pop().toLowerCase();
      var taille = fichier.size;

      if (extensionsAutorisees.indexOf(extension) === -1) {
        erreurPj.textContent =
          "Format non accepté : " +
          fichier.name +
          ". Formats autorisés : PDF, JPG, PNG, DOCX.";
        erreurPj.classList.add("visible");
        erreur = true;
        return;
      }

      if (taille > tailleMaxOctet) {
        erreurPj.textContent =
          "Fichier trop volumineux : " + fichier.name + " dépasse 5 Mo.";
        erreurPj.classList.add("visible");
        erreur = true;
        return;
      }

      // Création d'un élément de liste pour chaque fichier valide
      var li = document.createElement("li");

      var spanNom = document.createElement("span");
      spanNom.className = "pj-nom";
      spanNom.textContent = fichier.name;

      var spanPoids = document.createElement("span");
      spanPoids.className = "pj-poids";
      // Affichage en Ko si < 1 Mo, en Mo sinon
      if (taille < 1024 * 1024) {
        spanPoids.textContent = Math.round(taille / 1024) + " Ko";
      } else {
        spanPoids.textContent = (taille / (1024 * 1024)).toFixed(1) + " Mo";
      }

      li.appendChild(spanNom);
      li.appendChild(spanPoids);
      listeFichiers.appendChild(li);
    });

    if (!erreur) {
      listeFichiers.classList.remove("cache");
    }
  });

  /* ----------------------------------------------------------
       Validation et soumission du formulaire
       ---------------------------------------------------------- */
  formSignalement.addEventListener("submit", function (evenement) {
    evenement.preventDefault();

    // Réinitialisation des messages d'erreur
    erreurCategorie.textContent = "";
    erreurCategorie.classList.remove("visible");
    erreurDescription.textContent = "";
    erreurDescription.classList.remove("visible");
    erreurEmail.textContent = "";
    erreurEmail.classList.remove("visible");

    var valide = true;

    // Validation : catégorie obligatoire
    if (!categorieSelect.value) {
      erreurCategorie.textContent = "Veuillez sélectionner une catégorie.";
      erreurCategorie.classList.add("visible");
      valide = false;
    }

    // Validation : description obligatoire (minimum 20 caractères)
    if (description.value.trim().length < 20) {
      erreurDescription.textContent =
        "La description doit comporter au moins 20 caractères.";
      erreurDescription.classList.add("visible");
      valide = false;
    }

    // Validation : email si le mode identifié est coché
    if (radioIdentifie.checked) {
      var emailInput = document.getElementById("email-salarie");
      // Expression régulière simple pour valider la structure d'un email
      var formatEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (
        emailInput.value.trim() &&
        !formatEmail.test(emailInput.value.trim())
      ) {
        erreurEmail.textContent = "L'adresse e-mail saisie n'est pas valide.";
        erreurEmail.classList.add("visible");
        valide = false;
      }
    }

    if (!valide) return;

    // Envoi réel vers le serveur via fetch()
    btnSoumettre.disabled = true;
    btnSoumettre.textContent = "Envoi en cours...";

    var formData = new FormData(formSignalement);

    fetch("traitement-signalement.php", {
      method: "POST",
      body: formData,
    })
      .then(function (reponse) {
        return reponse.json();
      })
      .then(function (data) {
        if (data.succes) {
          var form = document.getElementById("form-signalement");
          if (form) form.style.display = "none";
          var blocSucces = document.getElementById("bloc-succes");
          if (blocSucces) {
            blocSucces.classList.remove("cache");
            blocSucces.scrollIntoView({ behavior: "smooth", block: "center" });
          } else {
            window.location.href = "../../dashboards/dashboard-salarie.php";
          }
        } else {
          btnSoumettre.disabled = false;
          btnSoumettre.textContent = "Envoyer le signalement";
          var msg =
            data.erreur ||
            (data.erreurs && data.erreurs.join("\n")) ||
            "Erreur inconnue.";
          alert("Une erreur est survenue :\n" + msg);
        }
      })
      .catch(function () {
        btnSoumettre.disabled = false;
        btnSoumettre.textContent = "Envoyer le signalement";
        alert("Impossible de contacter le serveur. Vérifiez votre connexion.");
      });
  });
});
