<?php
declare(strict_types=1);
date_default_timezone_set('Africa/Kinshasa');
define('ROOT', dirname(__DIR__));
require_once __DIR__.'/backend/AppConfig.php';
$appConfig = \Fecgd\Backend\AppConfig::fromEnvironment(ROOT);
define('PRIVATE_DIR', $appConfig->privateDirectory);
define('BASE_URL', $appConfig->baseUrl);
define('FECGD_ENV', $appConfig->environment);
require_once __DIR__.'/maintenance.php';
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors','0');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    ini_set('session.use_trans_sid','0');
    session_name('fecgd_admin_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>BASE_URL.'/','httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
    session_start();
}
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        if (!is_file(PRIVATE_DIR.'/fecgd.sqlite') && !defined('FECGD_INSTALLING')) throw new RuntimeException('Base absente : vérifiez la configuration privée avant de continuer.');
        $pdo = new PDO('sqlite:'.PRIVATE_DIR.'/fecgd.sqlite', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $pdo->exec('PRAGMA busy_timeout=5000; PRAGMA foreign_keys=ON;');
    }
    return $pdo;
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function query(string $sql, array $values=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($values); return $s; }
function url(string $slug=''): string { return BASE_URL.'/'.($slug ? trim($slug,'/').'/' : ''); }
// A new URL after each asset edit prevents browsers from reusing outdated styles.
function asset_url(string $path): string {
    return BASE_URL.'/'.$path.'?v='.substr(hash_file('sha256',ROOT.'/'.$path),0,12);
}
function go(string $path): never { header('Location: '.$path, true, 303); exit; }
function user(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $account=query('SELECT id,login,role,password FROM users WHERE id=?',[(int)$_SESSION['user_id']])->fetch();
    $issued=(int)($_SESSION['issued_at']??time());
    $expired=time()-(int)($_SESSION['last_active']??0)>3600||time()-$issued>43200;
    $signature=$account?hash('sha256',$account['password']):'';
    if (!$account||!in_array($account['role'],['admin','editor'],true)||$expired||(isset($_SESSION['auth_signature'])&&!hash_equals($_SESSION['auth_signature'],$signature))) {
        security_event('session_expired',(int)$_SESSION['user_id']);
        unset($_SESSION['user_id'],$_SESSION['last_active'],$_SESSION['issued_at'],$_SESSION['auth_signature']);
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        if(session_status()===PHP_SESSION_ACTIVE)session_regenerate_id(true);
        return null;
    }
    $_SESSION['issued_at']=$issued;$_SESSION['last_active']=time();$_SESSION['auth_signature']=$signature;
    unset($account['password']);return $account;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function token_input(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(csrf(),$_POST['csrf'])) { http_response_code(403); exit('Session expirée. Rechargez la page avant de réessayer.'); }
}
function setting(string $name, $fallback='') { $v=query('SELECT value FROM settings WHERE name=?',[$name])->fetchColumn(); return $v===false?$fallback:json_decode($v,true); }
function set_setting(string $name,$value): void { query('INSERT INTO settings(name,value) VALUES(?,?) ON CONFLICT(name) DO UPDATE SET value=excluded.value',[$name,json_encode($value,JSON_UNESCAPED_UNICODE)]); }
function slugify(string $title): string { $s=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title); return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$s?:'publication')),'-') ?: 'publication'; }
function safe_uri(string $uri): bool {
    if(preg_match('/[\x00-\x20\\\\]/',$uri)||str_starts_with($uri,'//'))return false;
    if(str_starts_with($uri,'mailto:'))return filter_var(substr($uri,7),FILTER_VALIDATE_EMAIL)!==false;
    return (bool)(preg_match('#^https?://#i',$uri)||preg_match('#^(?:/|\#)#',$uri));
}
function clean_html(string $html): string {
    $doc=new DOMDocument(); libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="editor-root">'.$html.'</div>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
    $root=$doc->getElementById('editor-root');
    if (!$root) return '';
    $allowed=['p','br','h2','h3','h4','strong','b','em','i','u','ul','ol','li','a','blockquote','div','span','section','article','aside','figure','figcaption','img','video','source','dl','dt','dd','details','summary','time','table','thead','tbody','tr','th','td'];
    $walk=function($node) use (&$walk,$allowed) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag=strtolower($child->tagName);
                if (in_array($tag,['script','style','iframe','object','embed','form','input','button','svg','math','link','meta'],true)) { $node->removeChild($child); continue; }
                $walk($child);
                if (!in_array($tag,$allowed,true)) { while ($child->firstChild) $node->insertBefore($child->firstChild,$child); $node->removeChild($child); continue; }
                foreach(iterator_to_array($child->attributes) as $attr) {
                    if (!in_array($attr->name,['href','src','alt','title','class','width','height','datetime','id','loading','controls','playsinline','preload','poster','type'],true)) $child->removeAttribute($attr->name);
                    elseif (in_array($attr->name,['href','src','poster'],true) && !safe_uri($attr->value)) $child->removeAttribute($attr->name);
                }
                if ($tag==='a') $child->setAttribute('rel','noopener noreferrer');
            }
        }
    };
    $walk($root); $result=''; foreach($root->childNodes as $node) $result.=$doc->saveHTML($node);
    libxml_clear_errors(); return $result;
}
function images(array $post): array {
    $list=json_decode($post['images']??'[]',true)?:[];
    if (!$list) {
        $doc=new DOMDocument(); libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$post['content'],LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
        foreach($doc->getElementsByTagName('img') as $img) if (safe_uri($img->getAttribute('src'))) $list[]=['src'=>$img->getAttribute('src'),'alt'=>$img->getAttribute('alt')];
    }
    $unique=[]; foreach($list as $im) if (safe_uri($im['src']??'')) $unique[$im['src']]=$im;
    return array_values($unique);
}
function upload_images(): array {
    $result=[]; $files=$_FILES['photos']??null; if (!$files) return [];
    if (count($files['name'])>12) throw new RuntimeException('Ajoutez au maximum 12 photos à la fois.');
    foreach($files['error'] as $i=>$error) {
        if ($error===UPLOAD_ERR_NO_FILE) continue;
        if ($error!==UPLOAD_ERR_OK) throw new RuntimeException('Une photo n’a pas pu être envoyée. Vérifiez sa taille.');
        if ($files['size'][$i]>8*1024*1024) throw new RuntimeException('Chaque photo doit peser au maximum 8 Mo.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$i]);
        $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
        $info=@getimagesize($files['tmp_name'][$i]);
        if (!$ext||!$info||$info[0]*$info[1]>40000000) throw new RuntimeException('Utilisez une photo JPG, PNG ou WebP valide de moins de 40 millions de pixels.');
        $relative='/site/media/'.bin2hex(random_bytes(16)).'.'.$ext;
        if (!move_uploaded_file($files['tmp_name'][$i],ROOT.$relative)) throw new RuntimeException('Impossible de conserver la photo.');
        $result[]=['src'=>BASE_URL.$relative,'alt'=>trim((string)($_POST['photo_alt']??''))?:'Photographie de la fédération'];
    }
    return $result;
}
function revision(array $post): void { query('INSERT INTO revisions(content_id,snapshot,created_at) VALUES(?,?,?)',[$post['id'],json_encode($post,JSON_UNESCAPED_UNICODE),date('c')]); }
function export_stats(): array {
    $visits=[['Date','Pages vues','Visites (sessions de 30 min)','Inscriptions','Messages de contact']];
    $pages=[['Date','Page','Vues']]; $sources=[['Date','Provenance','Visites']];
    foreach(query('SELECT * FROM stats ORDER BY day') as $row) {
        $v=json_decode($row['data'],true); $visits[]=[$row['day'],$v['vues']??0,$v['visites']??0,$v['inscriptions']??0,$v['contacts']??0];
        foreach($v['pages']??[] as $slug=>$n) $pages[]=[$row['day'],$slug,$n];
        foreach($v['sources']??[] as $source=>$n) $sources[]=[$row['day'],$source,$n];
    }
    $members=[['ID','Date','Organisation','Activité','Ville','Statut']];
    foreach(query("SELECT * FROM requests WHERE kind='adhesion' ORDER BY id") as $r) { $v=json_decode($r['data'],true); $members[]=[$r['id'],$r['created_at'],$v['organisation']??'',$v['categorie']??'',$v['ville']??'',$r['status']]; }
    return ['mode'=>'site-fecgd','visites'=>$visits,'inscriptions'=>$members,'pages'=>$pages,'sources'=>$sources];
}
function cms_stat(string $field,string $page=''): void {
    $day=date('Y-m-d'); db()->exec('BEGIN IMMEDIATE');
    try { $data=json_decode(query('SELECT data FROM stats WHERE day=?',[$day])->fetchColumn()?:'{}',true);
        $data[$field]=($data[$field]??0)+1; if ($page) $data['pages'][$page]=($data['pages'][$page]??0)+1;
        query('INSERT INTO stats(day,data) VALUES(?,?) ON CONFLICT(day) DO UPDATE SET data=excluded.data',[$day,json_encode($data)]); db()->exec('COMMIT');
    } catch(Throwable $ex) { db()->exec('ROLLBACK'); throw $ex; }
}
