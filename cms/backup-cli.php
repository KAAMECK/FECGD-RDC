<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/bootstrap.php';
try {
    $service=full_backup_service();
    if(($argv[1]??'')==='verify') {echo 'Archive vérifiée : '.$service->verify($argv[2]??'')." fichiers.\n";}
    else {$path=$service->create();security_event('backup_created');echo 'Sauvegarde privée créée : '.basename($path)."\n";}
} catch(Throwable $error) {fwrite(STDERR,"Sauvegarde non effectuée : ".$error->getMessage()."\n");exit(1);}
