<?php
require __DIR__.'/bootstrap.php';
require __DIR__.'/institution.php';
require __DIR__.'/article-layout.php';
function is_generated_illustration(array $image): bool {
    return (bool)preg_match('#/illustration-(?:metiers|caniveaux|plastiques)-20261026\.png$#',$image['src']??'');
}
function is_poster(array $image): bool { return ($image['format']??'')==='poster'; }
function gallery(array $post): string {
    $ims=images($post); $count=count($ims); if (!$count) return '';
    $out='<div class="news-carousel" role="group" aria-label="Photos : '.e($post['title']).'"><div class="news-slides'.($count>1?' news-marquee':'').'" style="--news-duration: '.max(24,$count*8).'s"><div class="news-track">';
    for($copy=0;$copy<($count>1?2:1);$copy++) { $out.='<div class="news-image-group"'.($copy?' aria-hidden="true"':'').'>'; foreach($ims as $im) $out.='<figure class="news-image'.(is_poster($im)?' news-poster':'').(($im['fit']??'')==='contain'?' news-contain':'').'"><img class="news-slide" src="'.e($im['src']).'" alt="'.($copy?'':e($im['alt']?:$post['title'])).'" loading="lazy" decoding="async">'.(is_generated_illustration($im)?'<figcaption class="image-credit">Illustration générée par IA</figcaption>':(!empty($im['credit'])?'<figcaption class="image-credit">Photo : '.e($im['credit']).'</figcaption>':'')).'</figure>'; $out.='</div>'; }
    return $out.'</div></div>'.($count>1?'<button type="button" class="news-pause" data-news-pause aria-pressed="false">Pause</button>':'').'</div>';
}
function cards(string $kind): string {
    $html='<div class="news-list">';
    foreach(query("SELECT * FROM contents WHERE kind=? AND status='published' AND posted_at<=? ORDER BY posted_at DESC,id DESC LIMIT 100",[$kind,date('Y-m-d H:i:s')]) as $post) {
        $excerpt=$post['excerpt']?:strip_tags($post['content']);
        $words=preg_split('/\s+/u',trim($excerpt)); $excerpt=implode(' ',array_slice($words,0,38)).(count($words)>38?'…':'');
        $html.='<article class="news-card'.(images($post)?' has-media':'').'">'.gallery($post).'<div class="news-card-content"><span class="news-category">'.($kind==='news'?(article_has_video($post['content'])?'Vidéo':(article_is_press($post['content'])?'Revue de presse':'Actualité')):'Domaine d’action').'</span><time datetime="'.e(date('c',strtotime($post['posted_at']))).'">Publié le '.date('d/m/Y à H:i',strtotime($post['posted_at'])).'</time><h2><a href="'.e(url($post['slug'])).'">'.e($post['title']).'</a></h2><p>'.e($excerpt).'</p><a class="inline-link" href="'.e(url($post['slug'])).'">'.($kind==='news'?'Lire l’article':'Découvrir l’activité').' <span aria-hidden="true">↗</span></a></div></article>';
    }
    return $html.'</div>';
}
function public_form(string $kind): string {
    $ad=$kind==='adhesion'; $html='';
    $messages=['recu'=>'Votre demande a été enregistrée. La fédération pourra la consulter.','invalide'=>'Vérifiez les champs obligatoires et votre accord.','limite'=>'Patientez une minute avant de recommencer.'];
    if(isset($messages[$_GET['resultat']??''])) $html='<p class="form-notice" role="status">'.e($messages[$_GET['resultat']]).'</p>';
    $html.='<form class="fecgd-form" method="post" action="'.e(url('envoyer')).'">'.token_input().'<input type="hidden" name="kind" value="'.e($kind).'"><div class="trap" aria-hidden="true"><label>Site web<input name="website" tabindex="-1" autocomplete="off"></label></div>';
    foreach(['nom'=>'Nom et prénom','email'=>'Adresse email','telephone'=>'Téléphone'] as $name=>$label) $html.='<label>'.$label.($name!=='telephone'||$ad?' *':'').'<input name="'.$name.'" type="'.($name==='email'?'email':($name==='telephone'?'tel':'text')).'" maxlength="160" '.($name!=='telephone'||$ad?'required':'').'></label>';
    if($ad) { foreach(['organisation'=>'Entreprise ou organisation','ville'=>'Ville ou province'] as $name=>$label) $html.='<label>'.$label.' *<input name="'.$name.'" required maxlength="160"></label>'; $html.='<label>Activité principale *<select name="categorie" required><option value="">Choisir</option>'; foreach(['Collecte','Transport','Tri','Traitement','Recyclage','Valorisation','Autre activité de la filière'] as $value) $html.='<option>'.e($value).'</option>'; $html.='</select></label>'; }
    else $html.='<label>Objet *<input name="objet" required maxlength="180"></label>';
    return $html.'<label>'.($ad?'Présentation de votre activité':'Message').' *<textarea name="message" required maxlength="4000"></textarea></label><label class="consent"><input type="checkbox" name="consent" value="1" required><span>J’accepte le traitement de ma demande selon les mentions de confidentialité.</span></label><button class="button lime" type="submit">Envoyer ma demande ↗</button><p class="form-note">Les champs marqués d’un astérisque sont obligatoires.'.($ad?' Une demande ne vaut pas admission automatique.':'').'</p></form>';
}
$slug=trim(substr(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),strlen(BASE_URL)),'/');
if($slug==='index.php') $slug='';
if($slug==='envoyer') {
    if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
    check_csrf(); $kind=$_POST['kind']??''; if(!in_array($kind,['contact','adhesion'],true)) {http_response_code(400); exit;}
    $return=url($kind); $data=[];
    foreach(['nom'=>120,'email'=>160,'telephone'=>40,'organisation'=>160,'ville'=>100,'categorie'=>80,'objet'=>180,'message'=>4000] as $key=>$max) { $v=$_POST[$key]??''; if(!is_string($v)||mb_strlen($v)>$max) go($return.'?resultat=invalide'); $data[$key]=trim(strip_tags($v)); }
    if(!empty($_POST['website'])||($_POST['consent']??'')!=='1'||!$data['nom']||!filter_var($data['email'],FILTER_VALIDATE_EMAIL)||!$data['message']||($kind==='contact'&&!$data['objet'])||($kind==='adhesion'&&(!$data['organisation']||!$data['telephone']||!$data['ville']||!in_array($data['categorie'],['Collecte','Transport','Tri','Traitement','Recyclage','Valorisation','Autre activité de la filière'],true)))) go($return.'?resultat=invalide');
    $key='form:'.hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'').':'.$kind,setting('salt'));
    db()->exec('BEGIN IMMEDIATE');
    try { if ((int)query('SELECT expires FROM limits WHERE key=?',[$key])->fetchColumn()>time()) {db()->exec('ROLLBACK');go($return.'?resultat=limite');}
        query('INSERT INTO requests(kind,data,status,created_at) VALUES(?,?,?,?)',[$kind,json_encode($data,JSON_UNESCAPED_UNICODE),'Reçue',date('Y-m-d H:i:s')]);
        query('INSERT INTO limits(key,attempts,expires) VALUES(?,1,?) ON CONFLICT(key) DO UPDATE SET expires=excluded.expires',[$key,time()+60]); db()->exec('COMMIT');
    } catch(Throwable $ex) {db()->exec('ROLLBACK');throw $ex;}
    cms_stat($kind==='contact'?'contacts':'inscriptions'); go($return.'?resultat=recu');
}
if ($slug==='sitemap.xml') {header('Content-Type: application/xml; charset=utf-8'); echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'; foreach(query("SELECT slug FROM contents WHERE status='published' AND posted_at<=?",[date('Y-m-d H:i:s')]) as $p) echo '<url><loc>'.e((!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').url($p['slug']==='accueil'?'':$p['slug'])).'</loc></url>'; echo '</urlset>'; exit;}
$post=query('SELECT * FROM contents WHERE slug=?',[$slug?:'accueil'])->fetch();
$preview=isset($_GET['apercu'])&&user();
if(!$post||($post['status']!=='published'&&!$preview)||($post['posted_at']>date('Y-m-d H:i:s')&&!$preview)) {http_response_code(404);$post=['title'=>'Page introuvable','content'=>'<p>Cette page n’est pas disponible.</p>','excerpt'=>'','kind'=>'page','slug'=>'404'];}
if(!user()&&!$preview&&http_response_code()!==404&&!isset($_GET['resultat'])) {cms_stat('vues',$post['slug']);if(time()-($_SESSION['visit_time']??0)>1800) cms_stat('visites');$_SESSION['visit_time']=time();}
$body=$post['content'];
$body=str_replace('aria-label="Découvrir la suite"','title="Découvrir la suite"',$body);
$body=str_replace('[fecgd_institution_sections]',institution_sections(),$body);
$body=str_replace('[fecgd_telephone]',institution_phone(),$body);
$body=str_replace('[fecgd_actualites]',cards('news'),$body);
$body=str_replace('[fecgd_contact]',public_form('contact'),$body);
$body=str_replace('[fecgd_adhesion]',public_form('adhesion'),$body);
$phone=setting('contact',[])['whatsapp']??'';
$body=str_replace('[fecgd_whatsapp]',$phone?'<a class="button lime" href="https://wa.me/'.e(preg_replace('/\D/','',$phone)).'">Contacter sur WhatsApp</a>':'',$body);
$body=str_replace('[fecgd_domaines]',cards('activity'),$body);
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($post['title'])?> — FECGD-RDC</title><meta name="description" content="<?=e(mb_substr($post['excerpt'],0,160))?>"><meta name="robots" content="<?=setting('indexing',false)&&!$preview?'index, follow':'noindex, nofollow'?>"><link rel="icon" href="<?=BASE_URL?>/site/dist/assets/favicon.png"><link rel="stylesheet" href="<?=e(asset_url('site/dist/styles.css'))?>"><link rel="stylesheet" href="<?=e(asset_url('cms/assets/site.css'))?>"><link rel="stylesheet" href="<?=e(asset_url('cms/assets/ui.css'))?>"><link rel="stylesheet" href="<?=e(asset_url('cms/assets/articles.css'))?>"><link rel="stylesheet" href="<?=e(asset_url('cms/assets/identity.css'))?>"><meta property="og:title" content="<?=e($post['title'])?>"><meta property="og:description" content="<?=e($post['excerpt'])?>"></head><body class="site-ui">
<a class="skip-link" href="#contenu">Aller au contenu</a><div class="topbar"><div class="wrap"><span>Fédération des entreprises de la filière des déchets</span><span class="country">République démocratique du Congo</span></div></div>
<header class="header"><div class="wrap nav-wrap"><a class="brand" href="<?=url()?>" title="Accueil"><img src="<?=BASE_URL?>/site/dist/assets/logo.webp" alt="" width="82" height="82"><span class="brand-label"><strong>FECGD-RDC</strong><span>Collecte · Gestion · Valorisation</span></span></a><button class="menu-toggle" aria-expanded="false" aria-controls="navigation" aria-label="Ouvrir le menu"><span></span><span></span></button><nav id="navigation" aria-label="Navigation principale"><?php foreach(['accueil'=>'Accueil','federation'=>'Qui sommes-nous ?','activites'=>'Activités','actualites'=>'Actualités','faq'=>'FAQ','contact'=>'Contact','adhesion'=>'Adhérer'] as $key=>$label):?><a href="<?=url($key==='accueil'?'':$key)?>" class="<?=$key==='adhesion'?'nav-cta ':''?><?=$post['slug']===$key||($post['kind']==='news'&&$key==='actualites')||($post['kind']==='activity'&&$key==='activites')?'active':''?>" <?=$post['slug']===$key?'aria-current="page"':''?>><?=e($label)?></a><?php endforeach;?></nav></div></header>
<main id="contenu"><?php if($preview):?><div class="form-notice wrap">Aperçu privé — le contenu n’est pas nécessairement publié. <a href="<?=url('admin')?>?view=edit&amp;id=<?=(int)$post['id']?>">Revenir à l’éditeur</a></div><?php endif;?>
<?php if($post['slug']==='accueil'):?>
  <div class="home-content"><?php
  $shortcuts='<nav class="domain-shortcuts wrap" aria-label="Les métiers de la filière"><span class="domain-intro">Nos domaines d’action</span>';
  foreach(query("SELECT slug,title FROM contents WHERE kind='activity' AND status='published' AND posted_at<=? ORDER BY id LIMIT 3",[date('Y-m-d H:i:s')]) as $domain) $shortcuts.='<a href="'.e(url($domain['slug'])).'">'.e($domain['title']).'<span aria-hidden="true">↗</span></a>';
  $shortcuts.='</nav>';
  $position=strpos($body,'</section>');
  echo $position===false?$body:substr_replace($body,'</section>'.$shortcuts,$position,10);
  ?></div>
<?php elseif($post['kind']!=='page'): $collection=$post['kind']==='news'?'actualites':'activites'; $photos=images($post); $illustrations=count(array_filter($photos,'is_generated_illustration')); $poster=count($photos)===1&&is_poster($photos[0]); $primaryVideo=article_video($body);if(!$primaryVideo)$primaryVideo=article_youtube($body); $readingMinutes=max(1,(int)ceil(str_word_count(strip_tags($body))/200)); ?>
  <div class="article-shell wrap">
    <nav class="breadcrumbs" aria-label="Fil d’Ariane"><a href="<?=url()?>">Accueil</a><span aria-hidden="true">/</span><a href="<?=url($collection)?>"><?=$collection==='actualites'?'Actualités':'Activités'?></a><span aria-hidden="true">/</span><span>La publication</span></nav>
    <header class="article-intro <?=$photos?'has-photos':''?> <?=$primaryVideo?'is-video':''?>">
      <div class="article-intro-copy"><p class="eyebrow"><?=$primaryVideo?'En vidéo':($post['kind']==='news'?(article_is_press($post['content'])?'Revue de presse':'La vie de la fédération'):'Nos domaines d’action')?></p><h1><?=e($post['title'])?></h1><p class="article-summary"><?=e($post['excerpt'])?></p><div class="article-meta"><time datetime="<?=e(date('c',strtotime($post['posted_at'])))?>">Publié le <?=date('d/m/Y à H:i',strtotime($post['posted_at']))?></time><span><?=$primaryVideo?'Vidéo à la demande':($readingMinutes.' min de lecture')?></span></div><a class="article-read" href="<?=$primaryVideo?'#article-video':'#article-texte'?>"><?=$primaryVideo?'Regarder la vidéo':'Lire la publication'?> <span aria-hidden="true">↓</span></a></div>
      <?php if($primaryVideo):?><div class="article-primary-video" id="article-video"><div class="video-heading"><span><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m10 8 6 4-6 4z"/></svg>La vidéo</span><span><?=str_contains($primaryVideo,'data-youtube-id')?'YouTube':'REGEDEK'?></span></div><?=$primaryVideo?><p class="video-state" data-video-state role="status">Lancez la lecture à votre rythme.</p></div>
      <?php elseif($photos):?><div class="article-cover"><?=gallery($post)?><p><?=$poster?'<a href="'.e($photos[0]['src']).'" target="_blank" rel="noopener noreferrer">Voir l’affiche en grand ↗</a>':($illustrations===count($photos)?'Illustration générée par IA':(!empty($photos[0]['credit'])?'Crédit : '.e($photos[0]['credit']):'Photographies de la fédération'))?> <span><?=$illustrations===count($photos)?'ILLUSTRATION':'EN IMAGES'?></span></p></div><?php endif;?>
    </header>
    <div class="article-reading-layout"><aside class="article-tools" aria-label="Outils de l’article"><p>La publication</p><a href="<?=url($collection)?>">← Retour aux <?=$collection==='actualites'?'actualités':'activités'?></a><button type="button" data-copy-article hidden>Copier le lien</button><p class="share-status" data-share-status role="status"></p></aside><article class="page-body article-body" id="article-texte"><?=$body?></article></div>
    <div class="article-return"><a class="button lime" href="<?=url($collection)?>">Retour aux <?=$collection==='actualites'?'actualités':'activités'?> <span aria-hidden="true">↗</span></a><p>Collecte, gestion et valorisation des déchets en RDC.</p></div>
    <?=article_related($post)?>
  </div>
<?php else:?>
  <div class="page-heading wrap"><p class="eyebrow">FECGD-RDC</p><h1><?=e($post['title'])?></h1><?php if($post['excerpt']):?><p class="page-lead"><?=e($post['excerpt'])?></p><?php endif;?></div><article class="page-body wrap"><?=$body?></article>
<?php endif;?></main>
<footer class="footer wrap"><div class="footer-main"><a class="brand" href="<?=url()?>"><img src="<?=BASE_URL?>/site/dist/assets/logo.webp" alt="FECGD-RDC" width="100" height="100" loading="lazy"></a><p>Fédération des entreprises de collecte, de gestion et de valorisation des déchets de la RDC.<br>N° 5897, 2e rue, Limete résidentiel / Petit boulevard.<br>Commune de Limete, Kinshasa, RDC.<br><a href="mailto:contact@fecgd-drc.org">contact@fecgd-drc.org</a><?=institution_phone(true)?></p></div><nav class="footer-links" aria-label="Liens de pied de page"><a href="<?=url('federation')?>">Qui sommes-nous ?</a><a href="<?=url('activites')?>">Nos activités</a><a href="<?=url('actualites')?>">Actualités</a><a href="<?=url('contact')?>">Nous contacter</a><a href="<?=url('adhesion')?>">Demander une adhésion</a></nav><div class="footer-bottom"><span>© <?=date('Y')?> FECGD-RDC</span><a href="<?=url('mentions-legales')?>">Mentions et confidentialité</a><a href="<?=url('admin')?>">Administration FECGD</a></div></footer><script src="<?=e(asset_url('cms/assets/menu.js'))?>"></script><script src="<?=e(asset_url('cms/assets/news.js'))?>"></script><script src="<?=e(asset_url('cms/assets/ui.js'))?>"></script><script src="<?=e(asset_url('cms/assets/articles.js'))?>"></script></body></html>
