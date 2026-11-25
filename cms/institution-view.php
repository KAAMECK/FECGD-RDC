<?php
$profile=institution_profile();
if($error&&($_POST['action']??'')==='institution') {
    foreach(array_keys(institution_fields()) as $field)if(is_string($_POST[$field]??null))$profile[$field]=$_POST[$field];
    if(is_string($_POST['telephone']??null))$profile['telephone']=$_POST['telephone'];
    $profile['whatsapp']=($_POST['whatsapp']??'')==='1';
    // Keep the submitted version: a conflict requires reloading, not silent overwrite.
    $profile['version']=is_scalar($_POST['version']??null)?(string)$_POST['version']:'';
}
$missing=[];foreach(institution_fields() as $key=>[$title,$hint])if(trim((string)$profile[$key])==='')$missing[]=$title;
?>
<h1>Identité et coordonnées</h1>
<p>Complétez « Qui sommes-nous ? » avec les réponses validées de la fédération. Les rubriques vides et le téléphone non renseigné ne s’affichent pas sur le site.</p>
<section class="panel"><h2>Informations à recueillir</h2><?php if($missing):?><ul><?php foreach($missing as $label):?><li><?=e($label)?></li><?php endforeach;?></ul><?php else:?><p>Les rubriques de présentation sont renseignées.</p><?php endif;?><?php if(!$profile['telephone']):?><p>Le numéro de téléphone officiel reste à communiquer.</p><?php endif;?><p class="hint">Les textes initiaux sur les objectifs et la mission reprennent la présentation existante. Faites valider toute nouvelle formulation. Conservez les documents confidentiels hors des champs publics.</p></section>
<form method="post" class="panel"><?=token_input()?><input type="hidden" name="action" value="institution"><input type="hidden" name="version" value="<?=e($profile['version'])?>">
<h2>Présentation institutionnelle</h2>
<?php foreach(institution_fields() as $key=>[$title,$hint]):?><label for="institution-<?=e($key)?>"><?=e($title)?></label><p class="hint" id="hint-<?=e($key)?>"><?=e($hint)?> Laissez vide tant que le texte n’est pas validé.</p><textarea id="institution-<?=e($key)?>" name="<?=e($key)?>" rows="5" maxlength="12000" aria-describedby="hint-<?=e($key)?>"><?=e($profile[$key])?></textarea><?php endforeach;?>
<h2>Téléphone officiel</h2><label for="institution-phone">Numéro à afficher au public</label><input id="institution-phone" type="tel" name="telephone" maxlength="40" value="<?=e($profile['telephone'])?>" aria-describedby="phone-hint"><p id="phone-hint" class="hint">Format international : + suivi de l’indicatif et du numéro. Aucun numéro personnel ne doit être publié sans autorisation.</p>
<label><input type="checkbox" name="whatsapp" value="1" <?=$profile['whatsapp']?'checked':''?>> Ce même numéro accepte les messages WhatsApp.</label>
<p>Adresse actuelle : N° 5897, 2e rue, Limete résidentiel / Petit boulevard, commune de Limete, Kinshasa.<br>E-mail : contact@fecgd-drc.org.</p>
<label><input type="checkbox" name="confirmed" value="1" required> Je confirme que ces informations sont validées et autorisées à être publiées.</label>
<p class="hint">L’enregistrement met à jour ce site immédiatement. La copie en ligne demande une publication séparée.</p><div class="actions"><button class="primary">Enregistrer les informations validées</button><a href="<?=url('federation')?>">Voir « Qui sommes-nous ? »</a><a href="<?=url('contact')?>">Voir les contacts</a></div></form>
