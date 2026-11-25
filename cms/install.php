<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root=dirname(__DIR__);
require_once __DIR__.'/backend/AppConfig.php';
$installConfig=\Fecgd\Backend\AppConfig::fromEnvironment($root);
$private=$installConfig->privateDirectory;
if(is_file($private.'/fecgd.sqlite')){echo "Installation existante conservée.\n";exit;}
if(!is_dir($private))mkdir($private,0700,true);
file_put_contents($private.'/.htaccess',"Require all denied\n");
define('FECGD_INSTALLING',true);
require __DIR__.'/bootstrap.php';
$seed=json_decode(file_get_contents(__DIR__.'/content-public.json'),true,512,JSON_THROW_ON_ERROR);
db()->exec(file_get_contents(__DIR__.'/schema.sql'));
db()->exec(file_get_contents(__DIR__.'/requests-schema.sql'));
db()->beginTransaction();
foreach($seed['contents'] as $row)query('INSERT INTO contents(kind,slug,title,excerpt,content,status,posted_at,images) VALUES(?,?,?,?,?,?,?,?)',array_values($row));
$adminPassword=bin2hex(random_bytes(10));$editorPassword=bin2hex(random_bytes(10));
query('INSERT INTO users(login,password,role) VALUES(?,?,?)',['fecgd_admin',password_hash($adminPassword,PASSWORD_DEFAULT),'admin']);
query('INSERT INTO users(login,password,role) VALUES(?,?,?)',['fecgd_editeur',password_hash($editorPassword,PASSWORD_DEFAULT),'editor']);
set_setting('media',$seed['media']);set_setting('salt',bin2hex(random_bytes(32)));set_setting('indexing',false);set_setting('contact',[]);
db()->commit();
file_put_contents($private.'/acces-administration.txt',"Administration : http://localhost/FECGD-DRC/admin/\nAdministrateur : fecgd_admin\nMot de passe : $adminPassword\nÉditeur : fecgd_editeur\nMot de passe : $editorPassword\n");
echo "Administration installée. Accès privés dans local-private/acces-administration.txt.\n";
