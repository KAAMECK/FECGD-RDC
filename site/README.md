# Site FECGD RDC

Le site complet fonctionne avec l'administration FECGD et une base SQLite privée : http://localhost/FECGD-DRC/.
Actualités, activités, pages et photos se gèrent sans écrire de code : http://localhost/FECGD-DRC/admin/.

Le dossier dist contient l'export HTML destiné aux aperçus publics. Son index.php rejoint le site principal sous Apache. Un hébergement statique ne peut pas exécuter l'administration PHP, les formulaires ou les statistiques du site complet.

Installer une copie locale avec C:\xampp\php\php.exe cms/install.php depuis la racine du projet. Les nouveaux accès sont créés dans local-private/acces-administration.txt, sans importer les demandes privées.

Voir cms/README.md pour la gestion, les sauvegardes et la publication. Les statuts confidentiels, accès et demandes restent exclus des archives publiques.
