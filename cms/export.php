<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/bootstrap.php';
$files=[];$rendered=[];
foreach(query("SELECT * FROM contents WHERE status='published' AND posted_at<=? ORDER BY id",[date('Y-m-d H:i:s')])->fetchAll() as $post) {
    $front=$post['slug']==='accueil';$prefix=$front?'./':'../';
    $curl=curl_init('http://localhost'.url($front?'':$post['slug']));curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20]);
    $html=curl_exec($curl);$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
    if($code!==200||!is_string($html))throw new RuntimeException('Page non exportée : '.$post['slug']);
    $html=preg_replace('#<a\b[^>]*href="'.preg_quote(url('admin'),'#').'"[^>]*>.*?</a>#s','',$html);
    $html=preg_replace_callback('#<form\b.*?</form>#s',function($m){$f=preg_replace('#<form\b[^>]*>#','<form class="fecgd-form" onsubmit="return false">',$m[0],1);$f=preg_replace('#<input\b[^>]*type="hidden"[^>]*>#','',$f);$f=preg_replace('#<(input|select|textarea|button)\b#','<$1 disabled',$f);return $f.'<p class="form-note">Le formulaire fonctionne sur le site complet. Cet aperçu HTML ne permet pas l’envoi.</p>';},$html);
    $html=str_replace(BASE_URL.'/cms/assets/',$prefix.'assets/cms/',$html);
    $html=str_replace(BASE_URL.'/site/dist/',$prefix,$html);
    $html=str_replace(BASE_URL.'/site/media/',$prefix.'assets/uploads/',$html);
    $html=str_replace(BASE_URL.'/',$prefix,$html);
    $file=$front?'index.html':$post['slug'].'/index.html';$files[]=$file;$rendered[$file]=$html;
}
foreach(['cms/assets'=>'assets/cms','site/media'=>'assets/uploads'] as $from=>$to) {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT.'/'.$from,FilesystemIterator::SKIP_DOTS)) as $file) {
        if(!in_array(strtolower($file->getExtension()),['css','js','jpg','jpeg','png','webp'],true))continue;
        $relative=substr($file->getPathname(),strlen(ROOT.'/'.$from)+1);$dest=ROOT.'/site/dist/'.$to.'/'.$relative;
        if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);
        if(is_file($dest)&&hash_file('sha256',$file->getPathname())===hash_file('sha256',$dest))continue;
        if(!copy($file->getPathname(),$dest))throw new RuntimeException('Impossible de copier le média public : '.$relative);
    }
}
foreach($rendered as $file=>$html) {$dest=ROOT.'/site/dist/'.$file;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);file_put_contents($dest,$html);}
$manifest=ROOT.'/site/dist/generated-pages.json';$old=json_decode(@file_get_contents($manifest)?:'[]',true)?:[];
foreach(array_diff($old,$files) as $stale)if(preg_match('#^(?:[a-z0-9-]+/)?index\.html$#',$stale)&&is_file(ROOT.'/site/dist/'.$stale))unlink(ROOT.'/site/dist/'.$stale);
file_put_contents($manifest,json_encode($files,JSON_PRETTY_PRINT));
echo count($files)." pages publiques exportées, sans administration ni demandes privées.\n";
