<?php
// Presentation only: the editable article remains unchanged in the database.
function article_has_video(string $html): bool {
    return str_contains($html,'<video')||(bool)preg_match('/\[fecgd_youtube:[A-Za-z0-9_-]{11}\]/',$html);
}
function article_is_press(string $html): bool {return str_contains($html,'class="press-source"');}
function article_youtube(string &$html): string {
    if(!preg_match('/\[fecgd_youtube:([A-Za-z0-9_-]{11})\]/',$html,$match))return '';
    $html=str_replace($match[0],'',$html);$id=$match[1];
    return '<section class="youtube-media" aria-label="Lecteur YouTube"><div class="youtube-player" data-youtube-id="'.e($id).'"><div class="youtube-consent"><svg aria-hidden="true" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="29" stroke="currentColor" stroke-width="2"/><path d="m26 20 19 12-19 12V20Z" fill="currentColor"/></svg><p>Le journal écologique de Green Impact TV</p><button type="button" data-load-youtube hidden>Charger la vidéo YouTube</button><p class="youtube-privacy">Le chargement de la vidéo vous connecte à YouTube.</p></div></div><p class="youtube-fallback"><a href="https://www.youtube.com/watch?v='.e($id).'" target="_blank" rel="noopener noreferrer">Ouvrir la vidéo sur YouTube <span aria-hidden="true">↗</span></a></p><noscript><p>Utilisez le lien YouTube pour regarder cette vidéo.</p></noscript></section>';
}
function article_video(string &$html): string {
    if(!str_contains($html,'<video'))return '';
    $doc=new DOMDocument();libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="article-source">'.$html.'</div>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
    $root=$doc->getElementById('article-source');$video=$doc->getElementsByTagName('video')->item(0);
    if(!$root||!$video)return '';
    $video->setAttribute('controls','');$video->setAttribute('playsinline','');$video->setAttribute('preload','none');
    $video->setAttribute('aria-label','Lecteur vidéo de la publication');$video->removeAttribute('autoplay');
    $figure=$video->parentNode;
    if(!$figure instanceof DOMElement||$figure->tagName!=='figure')$figure=$video;
    $media=$doc->saveHTML($figure);$figure->parentNode->removeChild($figure);
    $html='';foreach($root->childNodes as $node)$html.=$doc->saveHTML($node);
    libxml_clear_errors();return $media;
}
function article_related(array $post): string {
    $rows=query("SELECT * FROM contents WHERE kind=? AND status='published' AND posted_at<=? AND id<>? ORDER BY posted_at DESC,id DESC LIMIT 3",[$post['kind'],date('Y-m-d H:i:s'),$post['id']??0])->fetchAll();
    if(!$rows)return '';
    $out='<section class="article-related" aria-labelledby="related-title"><div class="related-heading"><h2 id="related-title">À découvrir aussi</h2><a href="'.e(url($post['kind']==='news'?'actualites':'activites')).'">Toutes les publications</a></div><div class="related-grid">';
    foreach($rows as $item){$ims=images($item);$out.='<article><a class="related-story" href="'.e(url($item['slug'])).'">';if($ims)$out.='<img class="'.((($ims[0]['fit']??'')==='contain'||is_poster($ims[0]))?'media-contained':'').'" src="'.e($ims[0]['src']).'" alt="" loading="lazy" width="480" height="300">';$out.='<span>'.(article_has_video($item['content'])?'Vidéo':(article_is_press($item['content'])?'Revue de presse':'Publication')).' · '.date('d/m/Y',strtotime($item['posted_at'])).'</span><h3>'.e($item['title']).'</h3><span class="related-open">Découvrir <span aria-hidden="true">↗</span></span></a></article>';}
    return $out.'</div></section>';
}
