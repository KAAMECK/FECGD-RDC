<?php
declare(strict_types=1);
$fixture=dirname(__DIR__).'/tmp/institution-test-'.bin2hex(random_bytes(6));mkdir($fixture,0700);
putenv('FECGD_PRIVATE_DIR='.$fixture);
$database=new PDO('sqlite:'.$fixture.'/fecgd.sqlite');$database->exec('CREATE TABLE settings(name TEXT PRIMARY KEY,value TEXT)');$database=null;
require dirname(__DIR__).'/cms/bootstrap.php';require dirname(__DIR__).'/cms/institution.php';
function expect(bool $condition): void{if(!$condition)throw new RuntimeException('Échec test institutionnel.');}
expect(institution_phone()==='');expect(!str_contains(institution_sections(),'institution-history'));
expect(str_contains(institution_sections(),'institution-mission'));
$payload=institution_defaults();$payload['version']='0';$payload['confirmed']='1';$payload['telephone']='+243 800 000 000';$payload['whatsapp']='1';$payload['history']='<script>Test</script>';
try {
    save_institution_profile($payload);expect(institution_profile()['version']===1);
    expect(str_contains(institution_phone(),'tel:+243800000000'));expect(str_contains(institution_phone(),'https://wa.me/243800000000'));
    expect(str_contains(institution_sections(),'&lt;script&gt;'));expect(!str_contains(institution_sections(),'<script>'));
    $rejected=false;try{save_institution_profile($payload);}catch(RuntimeException $error){$rejected=str_contains($error->getMessage(),'ailleurs');}expect($rejected);
    $payload['version']='1';$payload['telephone']='javascript:alert(1)';$rejected=false;try{save_institution_profile($payload);}catch(RuntimeException $error){$rejected=true;}expect($rejected);expect(institution_profile()['version']===1);
    $payload['telephone']='';$payload['whatsapp']='0';$payload['confirmed']='0';$rejected=false;try{save_institution_profile($payload);}catch(RuntimeException $error){$rejected=true;}expect($rejected);
    $payload['confirmed']='1';$payload['history']='';save_institution_profile($payload);expect(institution_phone()==='');expect(!str_contains(institution_sections(),'institution-history'));
    echo "Institution : validation, publication conditionnelle, échappement, téléphone, WhatsApp, confirmation et conflits validés.\n";
}finally{
    // This temporary fixture is private; leave it for normal temp retention because PDO stays open until process exit.
    putenv('FECGD_PRIVATE_DIR');
}
