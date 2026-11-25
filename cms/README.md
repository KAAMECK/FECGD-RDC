# Administration FECGD

Le site est géré par une application PHP dédiée avec une base SQLite privée. Il ne nécessite pas MySQL. L’installation locale utilise PHP 8.1 ; PDO SQLite, DOM, Fileinfo, mbstring, cURL, OpenSSL et ZIP sont nécessaires pour l’ensemble des outils, dont les sauvegardes. Pour le futur hébergement, retenir une version PHP encore maintenue, puis la valider en préproduction.

## Utilisation

Les publications de revue de presse utilisent une introduction courte, une attribution explicite et un lien vers la source. Les articles originaux ne sont pas reproduits intégralement. Les dates de publication du site correspondent à la mise en ligne locale ; les dates originales vérifiées sont indiquées dans le texte de source.

Le marqueur `[fecgd_youtube:yZ-IUsaO3HE]` insère le replay Green Impact TV dans la présentation vidéo de l’article. Seuls les identifiants YouTube de onze caractères sont acceptés. Le lecteur tiers ne se charge qu’après un clic ; un lien direct reste utilisable sans JavaScript. Aucun iframe libre n’est accepté dans l’éditeur. Le test `C:\xampp\php\php.exe tests/external-video.php` vérifie le rendu serveur et la validation de ce marqueur. La disponibilité réelle du programme dépend de YouTube et de la chaîne source.

- Site local : http://localhost/FECGD-DRC/
- Administration : http://localhost/FECGD-DRC/admin/
- Accès privés : `local-private/acces-administration.txt`.
- Guide intégré : administration → Guide d’utilisation.

### Identité institutionnelle — observations client du 4 novembre 2026

La direction visuelle partagée est définie dans `cms/assets/identity.css`, chargée après les styles publics et administratifs. Palette : vert forêt, bleu institutionnel, bleu profond, blanc et sauge. Le logo et les photographies restent inchangés. Les règles publiques sont séparées des règles `.admin-ui`, avec conservation des couleurs sémantiques des dossiers. Les choix sont documentés dans `design-system/fecgd-rdc/MASTER.md`. L’export statique recopie automatiquement cette feuille de style ; aucun service externe ni déploiement n’est activé par son chargement.

La navigation et le pied de page utilisent « Qui sommes-nous ? ». Le chemin `/federation/` est conservé pour les liens existants. La page reprend les informations déjà présentes sur l’identité, la mission et les objectifs, avec une photographie réelle et une navigation interne vers les rubriques renseignées.

Les administrateurs disposent de **Identité et coordonnées** : histoire, organisation, objectifs, vision, mission, valeurs, engagements et téléphone officiel. Les textes sont du texte simple, échappé à l’affichage. Une rubrique vide n’apparaît ni dans le sommaire ni sur la page publique ; aucun historique, organigramme ou engagement officiel n’a été inventé. Le numéro reste vide jusqu’à sa communication par le client. Une fois validé, il apparaît dans Contact et dans le pied de page, avec un lien d’appel. WhatsApp est facultatif et explicitement activé pour ce même numéro.

L’enregistrement exige une confirmation d’autorisation de publication, le rôle administrateur et un jeton CSRF valide. Une version empêche l’écrasement d’une modification concurrente. La confirmation concerne ce site local ; elle ne déclenche aucun déploiement externe. Les valeurs sont stockées dans le réglage privé `institution` et incluses dans les sauvegardes de la base. Les éditeurs ne disposent pas de ce formulaire de coordonnées.

Dans Pages du site, conserver les marqueurs `[fecgd_institution_sections]` et `[fecgd_telephone]` pour l’affichage dynamique. Les titres et introductions restent modifiables dans l’éditeur habituel. Les versions antérieures des pages réorganisées sont conservées dans l’historique ; une sauvegarde préalable du code et de la base est également disponible dans le dossier privé.

En attente du client : date et circonstances de création, étapes historiques, organes et responsabilités, vision officielle, valeurs, engagements et numéro autorisé à être publié. Les objectifs et la mission initialement affichés reprennent le contenu existant ; les nouvelles formulations devront être validées.

Vérification isolée des données institutionnelles : `C:\xampp\php\php.exe tests/institution-profile.php`. Ce contrôle utilise une base de test distincte sous `tmp/` ; il vérifie les champs vides, l’échappement des textes, le téléphone, WhatsApp, la confirmation de publication et les conflits de version, sans modifier les données réelles.

Actualités et Activités permettent de créer, modifier, publier, programmer et retirer des contenus. L’éditeur visuel propose paragraphes, titres, gras, italique, listes et liens. Les images peuvent être téléversées, choisies dans les médias existants, décrites et ordonnées. Le résumé et les galeries sont repris automatiquement sur le site. L’accueil reprend les actualités ; la page Activités reprend les domaines gérés individuellement.

Les brouillons et aperçus sont réservés aux comptes connectés. La suppression envoie en corbeille ; la restauration crée un brouillon. Chaque modification conserve une version antérieure. Un contrôle de version empêche l’écrasement silencieux par deux éditeurs.

Les comptes éditeurs gèrent les contenus. L’administrateur dispose aussi des demandes privées, exports, statistiques, connexion Sheets et sauvegardes. Mon compte permet de changer le mot de passe. Les droits sont vérifiés côté serveur. Sessions expirant après une heure, mots de passe hachés, CSRF, limitation des connexions et validation serveur des textes et fichiers.

## Données et sauvegarde

### Boîte de réception privée

Messages et adhésions propose des catégories séparées, une recherche dans les coordonnées, objets, messages et notes, des filtres de statut, priorité, lecture et dates, un tri et une pagination de 25 dossiers. Les compteurs généraux concernent tous les dossiers ; ceux des catégories concernent l’emplacement choisi. Les dossiers sont accessibles uniquement aux administrateurs.

La recherche reste visible ; les filtres avancés sont repliés par défaut et accessibles au clavier. Leur résumé reste affiché, avec le nombre de critères différents des valeurs par défaut. Au départ : toutes les catégories, tous les statuts et dossiers de la boîte de réception, aucune limite de dates, plus récents en premier. Réinitialiser restaure cette vue. Les critères soumis restent conservés dans la navigation, les dossiers et les exports.

Le suivi lu/non lu est partagé, pas individuel. Ouvrir un dossier ne le marque pas automatiquement comme lu : utiliser le bouton ou l’action groupée. Les notes restent privées ; la sauvegarde du suivi refuse un écrasement concurrent. L’historique indique l’auteur, la date et la nature des changements. Les tables request_meta et request_events complètent les demandes sans modifier les messages d’origine. Le schéma complémentaire est créé à la première ouverture par un administrateur et lors d’une installation neuve.

Les messages utilisent les libellés À traiter, En cours, Traité et Sans suite ; les adhésions conservent Reçue, En cours, Acceptée et Refusée. Le stockage des statuts historiques reste compatible avec les statistiques. Les actions groupées changent la lecture, la priorité ou l’archivage pour les seuls dossiers sélectionnés (25 maximum). L’archivage est réversible et ne supprime rien.

Le CSV de cette boîte exporte tous les résultats filtrés, avec leurs coordonnées, sans message ni note interne ; il doit rester privé. Il est distinct des exports statistiques de Google Sheets, qui excluent les coordonnées personnelles. Le lien e-mail ouvre la messagerie du responsable pour préparer une réponse ; aucun envoi automatique n’est ajouté. L’impression du dossier masque le panneau des notes et des actions.

`local-private/fecgd.sqlite` contient comptes, contenus, demandes, statistiques, réglages et historique. Apache doit refuser toute lecture HTTP de `local-private/`. Les photos publiques sont dans `site/dist/assets/` et `site/media/`. Les téléversements autorisent uniquement JPG, PNG et WebP vérifiés, avec noms aléatoires et taille plafonnée. La limite PHP de l’hébergement peut être plus basse que les 8 Mo de l’application.

Les anciens contenus, demandes, statistiques et réglages ont été migrés. Les anciennes tables et l’installation sont sauvegardées dans `local-private/` ; la base MySQL originale n’a pas été supprimée. Le démarrage actuel du site n’exécute plus l’ancien moteur.

Le bouton Sauvegarde dans Statistiques et Sheets exporte une copie cohérente de la base avec SQLite VACUUM INTO. Sauvegarder aussi les médias. Une restauration technique doit se faire pendant une interruption contrôlée, avec copie de sécurité de la base courante. Ne jamais publier base, réglages ou identifiants dans Git ou dans un ZIP public.

Les notifications par e-mail ne sont pas activées en local. Les formulaires enregistrent les demandes pour l’équipe. Le connecteur Sheets conserve les paramètres antérieurs ; une URL Apps Script et un secret valides restent nécessaires pour la synchronisation manuelle. Les exports statistiques pour Google Sheets excluent e-mail, téléphone, nom personnel et message. Le suivi compte des sessions de trente minutes, pas des personnes uniques, et exclut les administrateurs connectés.

## Publication

Pour installer une copie du dépôt sur un autre ordinateur : démarrer Apache puis exécuter `C:\xampp\php\php.exe cms/install.php` depuis le projet. Les contenus publics sont importés et de nouveaux mots de passe sont créés dans le fichier privé des accès. Une base existante n’est jamais remplacée. Cette installation n’importe aucune demande privée ni aucun accès de la fédération.

Le site complet nécessite un hébergement PHP avec SQLite et un répertoire privé protégé. La configuration serveur décrite ci-dessous remplace la modification manuelle de BASE_URL dans le code. Configurer HTTPS, sauvegardes et messagerie, puis vérifier droits et confidentialité avant ouverture publique.

Cloudflare Pages ne peut pas exécuter les fichiers PHP. L’export statique reste un aperçu avec formulaires désactivés, sans administration distante. Pour le reconstruire localement :

```powershell
& C:\xampp\php\php.exe cms/export.php
python cms/package-pages.py
```

L’archive `output/fecgd-rdc-pages.zip` contient seulement les pages et médias publics. Aucun déploiement ni achat n’est déclenché par ces commandes. L’indexation demeure désactivée pendant validation ; le réglage privé indexing doit être activé lors de la mise en service validée.

## Améliorations backend locales du 29 octobre 2026

### Configuration pour le futur hébergement

Les services de configuration et de sauvegarde sont isolés dans `cms/backend/`, sans imposer un nouveau framework. Les variables d’environnement serveur sont facultatives en local :

| Variable | Valeur locale par défaut | Usage futur |
| --- | --- | --- |
| FECGD_ENV | local | local, test ou production |
| FECGD_BASE_URL | /FECGD-DRC | Valeur vide pour servir le site à la racine du domaine |
| FECGD_PRIVATE_DIR | dossier local-private du projet | Chemin absolu privé, de préférence hors de la racine publique |

Ces variables se configurent sur le serveur ; aucun fichier `.env` n’est chargé automatiquement. Un chemin privé incorrect provoque une erreur plutôt que la création silencieuse d’une base vide. L’installateur conserve toute base déjà présente. Une migration devra également adapter les anciens liens de médias enregistrés en base et les règles Apache : changer ces variables ne migre pas les données à lui seul.

### Sessions et traçabilité

Les cookies de session sont HttpOnly et SameSite=Lax, et Secure lorsque PHP détecte HTTPS. PHP utilise uniquement les cookies et le mode strict pour les identifiants de session. Derrière un proxy, il faudra vérifier la détection HTTPS côté hébergeur, sans faire confiance arbitrairement à un en-tête fourni par un visiteur.

La session expire après une heure d’inactivité ou douze heures au total. Un changement de mot de passe invalide les autres sessions lors de leur prochaine requête. Les permissions administrateur/éditeur et les jetons CSRF sont vérifiés côté serveur. Les événements de connexion, déconnexion, expiration, modification de mot de passe et création de sauvegarde sont consignés dans un journal privé, avec date et identifiant interne uniquement. Ce journal nécessite une politique de conservation à définir lors de l’hébergement ; il ne dispose pas encore de rotation automatique.

### Maintenance et sauvegarde complète

Accès : administration → Maintenance et sauvegardes, réservé aux administrateurs. Cette page contrôle les extensions PHP, l’accès aux médias et l’intégrité SQLite, affiche les derniers événements et permet de créer puis télécharger une sauvegarde complète.

L’archive contient une copie cohérente SQLite obtenue par VACUUM INTO, les images, vidéos MP4 et polices des dossiers médias prévus. Le manifeste SHA-256 est vérifié avant livraison. La configuration externe et les fichiers d’accès sont exclus ; la base contient néanmoins les comptes avec mots de passe hachés, les réglages et les demandes privées. L’archive doit donc rester confidentielle. Il s’agit d’une sauvegarde des données et médias, pas d’une image complète du serveur ni d’un remplacement du dépôt de code.

Une copie reste dans `local-private/backups/` (ou le dossier privé configuré). Le dossier est refusé en HTTP par Apache. Aucun transfert distant, chiffrement, suppression automatique ou tâche planifiée n’est activé. Sur l’hébergement, prévoir une copie indépendante, surveiller l’espace disque et définir une rétention.

Commandes de création et de vérification, depuis la racine du projet :

```powershell
& C:\xampp\php\php.exe cms/backup-cli.php
& C:\xampp\php\php.exe cms/backup-cli.php verify 'CHEMIN_ABSOLU_ARCHIVE.zip'
```

Pour restaurer : vérifier l’archive, extraire dans un dossier privé distinct, vérifier l’intégrité SQLite, sauvegarder l’état courant, interrompre les écritures et fermer les connexions avant de remplacer la base et les médias. Réadapter les chemins si le site change d’emplacement, puis tester avant réouverture. Aucune restauration automatique ne remplace les données du site.

### Vérifications de développement

Les deux nouveaux services disposent de tests PHPUnit et d’une analyse PHPStan niveau 9. Les dépendances sont des outils de développement, isolés dans `tmp/backend-tools/`, absents de l’application et exclus de la publication. Après installation de Composer dans ce dossier :

```powershell
& C:\xampp\php\php.exe tmp/backend-tools/composer.phar require --working-dir=tmp/backend-tools 'phpunit/phpunit:^9.6' 'phpstan/phpstan:^2.1' --no-interaction --prefer-dist --no-plugins --no-scripts
& C:\xampp\php\php.exe tmp/backend-tools/vendor/phpunit/phpunit/phpunit -c tests/backend/phpunit.xml
& C:\xampp\php\php.exe tmp/backend-tools/vendor/phpstan/phpstan/phpstan.phar analyse -c tests/backend/phpstan.neon --no-progress
```

La couverture est limitée aux nouveaux services de configuration et de sauvegarde ; elle ne décrit pas toute l’application historique. Les contrôles HTTP locaux couvrent aussi les permissions, les formulaires, le classement des demandes et le téléchargement/restauration d’une sauvegarde dans un dossier séparé.

Les notifications e-mail, l’automatisation Google Sheets, la sauvegarde distante et la migration de l’administration vers un hébergement restent des travaux séparés. Cette étape n’active aucun service externe.
