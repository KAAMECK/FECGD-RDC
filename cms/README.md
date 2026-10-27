# Administration FECGD

Le site est géré par une application PHP dédiée avec une base SQLite privée. Il ne nécessite pas MySQL. Apache et PHP avec PDO SQLite, DOM, Fileinfo, mbstring et cURL suffisent.

## Utilisation

- Site local : http://localhost/FECGD-DRC/
- Administration : http://localhost/FECGD-DRC/admin/
- Accès privés : `local-private/acces-administration.txt`.
- Guide intégré : administration → Guide d’utilisation.

Actualités et Activités permettent de créer, modifier, publier, programmer et retirer des contenus. L’éditeur visuel propose paragraphes, titres, gras, italique, listes et liens. Les images peuvent être téléversées, choisies dans les médias existants, décrites et ordonnées. Le résumé et les galeries sont repris automatiquement sur le site. L’accueil reprend les actualités ; la page Activités reprend les domaines gérés individuellement.

Les brouillons et aperçus sont réservés aux comptes connectés. La suppression envoie en corbeille ; la restauration crée un brouillon. Chaque modification conserve une version antérieure. Un contrôle de version empêche l’écrasement silencieux par deux éditeurs.

Les comptes éditeurs gèrent les contenus. L’administrateur dispose aussi des demandes privées, exports, statistiques, connexion Sheets et sauvegardes. Mon compte permet de changer le mot de passe. Les droits sont vérifiés côté serveur. Sessions expirant après une heure, mots de passe hachés, CSRF, limitation des connexions et validation serveur des textes et fichiers.

## Données et sauvegarde

### Boîte de réception privée

Messages et adhésions propose des catégories séparées, une recherche dans les coordonnées, objets, messages et notes, des filtres de statut, priorité, lecture et dates, un tri et une pagination de 25 dossiers. Les compteurs généraux concernent tous les dossiers ; ceux des catégories concernent l’emplacement choisi. Les dossiers sont accessibles uniquement aux administrateurs.

Le suivi lu/non lu est partagé, pas individuel. Ouvrir un dossier ne le marque pas automatiquement comme lu : utiliser le bouton ou l’action groupée. Les notes restent privées ; la sauvegarde du suivi refuse un écrasement concurrent. L’historique indique l’auteur, la date et la nature des changements. Les tables request_meta et request_events complètent les demandes sans modifier les messages d’origine. Le schéma complémentaire est créé à la première ouverture par un administrateur et lors d’une installation neuve.

Les messages utilisent les libellés À traiter, En cours, Traité et Sans suite ; les adhésions conservent Reçue, En cours, Acceptée et Refusée. Le stockage des statuts historiques reste compatible avec les statistiques. Les actions groupées changent la lecture, la priorité ou l’archivage pour les seuls dossiers sélectionnés (25 maximum). L’archivage est réversible et ne supprime rien.

Le CSV de cette boîte exporte tous les résultats filtrés, avec leurs coordonnées, sans message ni note interne ; il doit rester privé. Il est distinct des exports statistiques de Google Sheets, qui excluent les coordonnées personnelles. Le lien e-mail ouvre la messagerie du responsable pour préparer une réponse ; aucun envoi automatique n’est ajouté. L’impression du dossier masque le panneau des notes et des actions.

`local-private/fecgd.sqlite` contient comptes, contenus, demandes, statistiques, réglages et historique. Apache doit refuser toute lecture HTTP de `local-private/`. Les photos publiques sont dans `site/dist/assets/` et `site/media/`. Les téléversements autorisent uniquement JPG, PNG et WebP vérifiés, avec noms aléatoires et taille plafonnée. La limite PHP de l’hébergement peut être plus basse que les 8 Mo de l’application.

Les anciens contenus, demandes, statistiques et réglages ont été migrés. Les anciennes tables et l’installation sont sauvegardées dans `local-private/` ; la base MySQL originale n’a pas été supprimée. Le démarrage actuel du site n’exécute plus l’ancien moteur.

Le bouton Sauvegarde dans Statistiques et Sheets exporte une copie cohérente de la base avec SQLite VACUUM INTO. Sauvegarder aussi les médias. Une restauration technique doit se faire pendant une interruption contrôlée, avec copie de sécurité de la base courante. Ne jamais publier base, réglages ou identifiants dans Git ou dans un ZIP public.

Les notifications par e-mail ne sont pas activées en local. Les formulaires enregistrent les demandes pour l’équipe. Le connecteur Sheets conserve les paramètres antérieurs ; une URL Apps Script et un secret valides restent nécessaires pour la synchronisation manuelle. Les exports statistiques pour Google Sheets excluent e-mail, téléphone, nom personnel et message. Le suivi compte des sessions de trente minutes, pas des personnes uniques, et exclut les administrateurs connectés.

## Publication

Pour installer une copie du dépôt sur un autre ordinateur : démarrer Apache puis exécuter `C:\xampp\php\php.exe cms/install.php` depuis le projet. Les contenus publics sont importés et de nouveaux mots de passe sont créés dans le fichier privé des accès. Une base existante n’est jamais remplacée. Cette installation n’importe aucune demande privée ni aucun accès de la fédération.

Le site complet nécessite un hébergement PHP avec SQLite et un répertoire privé protégé. Adapter BASE_URL dans `cms/bootstrap.php` au chemin cible, configurer HTTPS, sauvegardes et messagerie, puis vérifier droits et confidentialité avant ouverture publique.

Cloudflare Pages ne peut pas exécuter les fichiers PHP. L’export statique reste un aperçu avec formulaires désactivés, sans administration distante. Pour le reconstruire localement :

```powershell
& C:\xampp\php\php.exe cms/export.php
python cms/package-pages.py
```

L’archive `output/fecgd-rdc-pages.zip` contient seulement les pages et médias publics. Aucun déploiement ni achat n’est déclenché par ces commandes. L’indexation demeure désactivée pendant validation ; le réglage privé indexing doit être activé lors de la mise en service validée.
