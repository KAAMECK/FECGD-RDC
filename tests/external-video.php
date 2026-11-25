<?php
declare(strict_types=1);
require dirname(__DIR__).'/cms/bootstrap.php';
require dirname(__DIR__).'/cms/article-layout.php';
function verify_video(bool $value): void {if(!$value)throw new RuntimeException('Vérification vidéo externe échouée.');}
$html='<p>Présentation.</p>[fecgd_youtube:yZ-IUsaO3HE]<p>Source.</p>';
verify_video(article_has_video($html));
$player=article_youtube($html);
verify_video(!str_contains($html,'[fecgd_youtube:'));
verify_video(str_contains($html,'Présentation.')&&str_contains($html,'Source.'));
verify_video(str_contains($player,'data-youtube-id="yZ-IUsaO3HE"'));
verify_video(str_contains($player,'https://www.youtube.com/watch?v=yZ-IUsaO3HE'));
verify_video(!str_contains($player,'<iframe')&&!str_contains($player,'<script'));
foreach(['[fecgd_youtube:javascript:alert(1)]','[fecgd_youtube:yZ-IUsaO3HEz]','[fecgd_youtube:"><script>]'] as $invalid){$original=$invalid;verify_video(article_youtube($invalid)==='');verify_video($invalid===$original);}
verify_video(article_has_video('<video controls></video>'));
verify_video(article_is_press('<p class="press-source">Source</p>'));
echo "Vidéo externe : identifiant strict, contenu préservé, lien de secours et absence de lecteur tiers avant action validés.\n";
