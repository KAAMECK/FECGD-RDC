<?php
declare(strict_types=1);
require_once __DIR__.'/backend/BackupService.php';

function full_backup_service(): \Fecgd\Backend\BackupService {
    return new \Fecgd\Backend\BackupService(db(), ROOT, PRIVATE_DIR.'/backups');
}
function security_event(string $event, int $actor = 0): void {
    // No password, token, address, message, IP or request payload is recorded.
    $allowed=['login_ok','login_failed','logout','password_changed','session_expired','backup_created'];
    if(!in_array($event,$allowed,true))return;
    $line=json_encode(['time'=>date(DATE_ATOM),'event'=>$event,'actor'=>$actor],JSON_UNESCAPED_SLASHES)."\n";
    if(file_put_contents(PRIVATE_DIR.'/security-events.jsonl',$line,FILE_APPEND|LOCK_EX)===false)error_log('FECGD: journal de sécurité indisponible');
}
/** @return array<string,bool> */
function maintenance_checks(): array {
    $checks=['PHP 8.1 ou plus récent'=>PHP_VERSION_ID>=80100];
    foreach(['pdo_sqlite','dom','fileinfo','mbstring','curl','zip','openssl'] as $module)$checks['Extension '.$module]=extension_loaded($module);
    $checks['Base SQLite accessible en écriture']=is_file(PRIVATE_DIR.'/fecgd.sqlite')&&is_writable(PRIVATE_DIR.'/fecgd.sqlite')&&is_writable(PRIVATE_DIR);
    $checks['Intégrité SQLite']=query('PRAGMA quick_check')->fetchColumn()==='ok';
    $checks['Vidéos et photos accessibles']=is_dir(ROOT.'/site/media')&&is_writable(ROOT.'/site/media');
    return $checks;
}
