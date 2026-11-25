<?php
declare(strict_types=1);

/** Texts already present on the federation page; new official facts remain empty. */
function institution_defaults(): array {
    return ['history'=>'','organisation'=>'','objectives'=>'Favoriser la concertation entre les opérateurs de la filière et les pouvoirs publics, la professionnalisation du secteur et l’amélioration de la qualité des services.','vision'=>'','mission'=>'Représenter, défendre et promouvoir les intérêts professionnels des membres. Rapprocher les entreprises de la filière pour favoriser les échanges et une meilleure articulation de leurs activités.','values'=>'','commitments'=>'','telephone'=>'','whatsapp'=>false,'version'=>0];
}
function institution_profile(): array {return array_replace(institution_defaults(),setting('institution',[]));}
function institution_fields(): array {
    return [
        'history'=>['Notre histoire','Date de création, circonstances, étapes marquantes et réalisations datées.'],
        'organisation'=>['Notre organisation','Organes officiels, responsabilités et, si autorisés à la publication, noms et fonctions des responsables.'],
        'objectives'=>['Nos objectifs','Objectifs officiels et priorités de la fédération.'],
        'vision'=>['Notre vision','Ambition à long terme pour la fédération et la filière en RDC.'],
        'mission'=>['Notre mission','Rôle concret de la fédération et acteurs auxquels elle s’adresse.'],
        'values'=>['Nos valeurs','Valeurs approuvées et leur traduction dans les actions.'],
        'commitments'=>['Nos engagements','Engagements précis envers les membres, les partenaires et la communauté.'],
    ];
}
function institution_sections(): string {
    $profile=institution_profile();$navigation='';$sections='';
    foreach(institution_fields() as $key=>[$title,$hint]) {
        $text=trim((string)$profile[$key]);if($text==='')continue;
        $navigation.='<a href="#institution-'.e($key).'">'.e($title).'</a>';
        $paragraphs='';foreach(preg_split('/\R\s*\R/u',$text)?:[] as $paragraph)$paragraphs.='<p>'.nl2br(e($paragraph),false).'</p>';
        $sections.='<section class="institution-section" id="institution-'.e($key).'" aria-labelledby="heading-'.e($key).'"><h2 id="heading-'.e($key).'">'.e($title).'</h2>'.$paragraphs.'</section>';
    }
    if($sections==='')return '';
    return '<div class="institution-reading"><nav class="institution-index" aria-label="Dans cette présentation"><p class="eyebrow">Mieux nous connaître</p>'.$navigation.'</nav><div class="institution-sections">'.$sections.'</div></div>';
}
function institution_phone(bool $compact=false): string {
    $profile=institution_profile();$phone=trim((string)$profile['telephone']);if($phone==='')return '';
    $dial=preg_replace('/[^+0-9]/','',$phone);
    if(!preg_match('/^\+[1-9][0-9]{7,14}$/',$dial))return '';
    if($compact)return '<br><a href="tel:'.e($dial).'">'.e($phone).'</a>';
    $html='<div class="contact-channel"><p class="eyebrow">Par téléphone</p><a class="contact-direct" href="tel:'.e($dial).'">'.e($phone).'</a>';
    if($profile['whatsapp'])$html.='<a class="inline-link" href="https://wa.me/'.e(substr($dial,1)).'" rel="noopener noreferrer">Écrire sur WhatsApp <span aria-hidden="true">↗</span></a>';
    return $html.'</div>';
}
function save_institution_profile(array $input): void {
    $next=[];
    foreach(institution_fields() as $key=>$definition) {
        if(!is_string($input[$key]??null)||mb_strlen($input[$key])>12000)throw new RuntimeException('Chaque rubrique doit contenir au maximum 12 000 caractères.');
        $next[$key]=trim($input[$key]);
    }
    $phone=$input['telephone']??'';
    if(!is_string($phone)||mb_strlen($phone)>40)throw new RuntimeException('Le numéro de téléphone est invalide.');
    $phone=trim($phone);$dial=preg_replace('/[ ().-]/','',$phone);
    if($phone!==''&&!preg_match('/^\+[1-9][0-9]{7,14}$/',$dial))throw new RuntimeException('Indiquez le numéro officiel au format international, avec + et l’indicatif du pays.');
    if(($input['confirmed']??'')!=='1')throw new RuntimeException('Confirmez que ces informations sont validées pour publication.');
    $next['telephone']=$phone;$next['whatsapp']=$phone!==''&&($input['whatsapp']??'')==='1';
    db()->exec('BEGIN IMMEDIATE');
    try {
        $current=institution_profile();
        if((string)($input['version']??'')!==(string)$current['version'])throw new RuntimeException('Ces informations ont été modifiées ailleurs. Rechargez la page avant de poursuivre.');
        $next['version']=(int)$current['version']+1;set_setting('institution',$next);db()->exec('COMMIT');
    }catch(Throwable $error){db()->exec('ROLLBACK');throw $error;}
}
