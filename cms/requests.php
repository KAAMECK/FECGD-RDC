<?php
// Private request tools; the controller enforces admin access and CSRF first.
function requests_schema(): void {db()->exec(file_get_contents(__DIR__.'/requests-schema.sql'));}
function request_filters(array $input): array {
    $f=[];
    foreach(['q','kind','status','flag','archive','from','to','sort','page'] as $key)$f[$key]=is_string($input[$key]??null)?mb_substr(trim($input[$key]),0,180):'';
    if(!in_array($f['kind'],['contact','adhesion'],true))$f['kind']='';
    if(!in_array($f['status'],['Reçue','En cours','Acceptée','Refusée'],true))$f['status']='';
    if(!in_array($f['flag'],['unread','priority','pending'],true))$f['flag']='';
    $f['archive']=$f['archive']==='1'?'1':'';
    foreach(['from','to'] as $key) { $dt=DateTime::createFromFormat('!Y-m-d',$f[$key]);if(!$dt||$dt->format('Y-m-d')!==$f[$key])$f[$key]=''; }
    if(!in_array($f['sort'],['oldest','priority'],true))$f['sort']='newest';
    $f['page']=(string)max(1,min(100000,(int)$f['page']));
    return $f;
}
function request_url(array $f=[],array $override=[]): string {return url('admin').'?'.http_build_query(array_merge(['view'=>'requests'],$f,$override),'','&',PHP_QUERY_RFC3986);}
function request_row(int $id): array|false {return query("SELECT r.*,COALESCE(m.is_read,0) is_read,COALESCE(m.priority,'normal') priority,COALESCE(m.archived,0) archived,COALESCE(m.note,'') note,COALESCE(m.version,0) version FROM requests r LEFT JOIN request_meta m ON m.request_id=r.id WHERE r.id=?",[$id])->fetch();}
function request_label(string $status,string $kind=''): string {
    if($kind==='contact')return ['Reçue'=>'À traiter','En cours'=>'En cours','Acceptée'=>'Traité','Refusée'=>'Sans suite'][$status]??$status;
    return ['Reçue'=>'Reçue','En cours'=>'En cours','Acceptée'=>'Acceptée','Refusée'=>'Refusée'][$status]??$status;
}
function request_rows(array $f): array {
    $where=['COALESCE(m.archived,0)=CAST(? AS INTEGER)'];$args=[$f['archive']==='1'?1:0];
    foreach(['kind','status'] as $key)if($f[$key]!==''){$where[]='r.'.$key.'=?';$args[]=$f[$key];}
    if($f['from']){$where[]='r.created_at>=?';$args[]=$f['from'].' 00:00:00';}
    if($f['to']){$where[]='r.created_at<=?';$args[]=$f['to'].' 23:59:59';}
    if($f['flag']==='unread')$where[]='COALESCE(m.is_read,0)=0';
    if($f['flag']==='priority')$where[]="m.priority='high'";
    if($f['flag']==='pending')$where[]="r.status IN ('Reçue','En cours')";
    $order=['newest'=>'r.created_at DESC,r.id DESC','oldest'=>'r.created_at,r.id','priority'=>"CASE WHEN m.priority='high' THEN 0 ELSE 1 END,r.created_at DESC,r.id DESC"][$f['sort']];
    $rows=query("SELECT r.*,COALESCE(m.is_read,0) is_read,COALESCE(m.priority,'normal') priority,COALESCE(m.archived,0) archived,COALESCE(m.note,'') note,COALESCE(m.version,0) version FROM requests r LEFT JOIN request_meta m ON m.request_id=r.id WHERE ".implode(' AND ',$where).' ORDER BY '.$order,$args)->fetchAll();
    foreach($rows as &$r)$r['fields']=json_decode($r['data'],true)?:[];unset($r);
    if($f['q']!=='')$rows=array_values(array_filter($rows,fn($r)=>mb_stripos(implode(' ',array_map(fn($v)=>is_scalar($v)?(string)$v:'',$r['fields'])).' '.$r['note'].' #'.$r['id'],$f['q'])!==false));
    return $rows;
}
function request_update(array $r,string $actor,string $description): void {
    query('UPDATE requests SET status=? WHERE id=?',[$r['status'],$r['id']]);
    query('INSERT INTO request_meta(request_id,is_read,priority,archived,note,version,updated_at) VALUES(?,?,?,?,?,1,?) ON CONFLICT(request_id) DO UPDATE SET is_read=excluded.is_read,priority=excluded.priority,archived=excluded.archived,note=excluded.note,version=request_meta.version+1,updated_at=excluded.updated_at',[$r['id'],$r['is_read'],$r['priority'],$r['archived'],$r['note'],date('Y-m-d H:i:s')]);
    query('INSERT INTO request_events(request_id,actor,description,created_at) VALUES(?,?,?,?)',[$r['id'],$actor,$description,date('Y-m-d H:i:s')]);
}
function request_action(string $action,array $account): void {
    $f=request_filters($_POST);$id=(int)($_POST['id']??0);
    db()->exec('BEGIN IMMEDIATE');
    try {
        if($action==='request_save') {
            $r=request_row($id);if(!$r)throw new RuntimeException('Cette demande n’existe plus.');
            if((string)($r['version'])!==($_POST['request_version']??null))throw new RuntimeException('Ce dossier a été modifié ailleurs. Rechargez-le avant de reprendre vos changements.');
            $status=$_POST['request_status']??'';$priority=$_POST['request_priority']??'';$note=$_POST['request_note']??'';
            if(!in_array($status,['Reçue','En cours','Acceptée','Refusée'],true)||!in_array($priority,['normal','high'],true)||!is_string($note)||mb_strlen($note)>4000)throw new RuntimeException('Vérifiez le statut, la priorité et la note (4 000 caractères maximum).');
            $changes=[];if($r['status']!==$status)$changes[]='Statut : '.request_label($status,$r['kind']);if($r['priority']!==$priority)$changes[]=$priority==='high'?'Priorité haute':'Priorité normale';if($r['note']!==trim($note))$changes[]='Note interne modifiée';
            if($changes){$r['status']=$status;$r['priority']=$priority;$r['note']=trim($note);request_update($r,$account['login'],implode(' ; ',$changes));}
        } elseif($action==='request_batch') {
            $ids=$_POST['request_ids']??[];$op=$_POST['operation']??'';
            if(!is_array($ids)||!count($ids)||count($ids)>25)throw new RuntimeException('Sélectionnez entre un et vingt-cinq dossiers.');
            $operations=['read'=>['is_read',1,'Marquée comme lue'],'unread'=>['is_read',0,'Marquée comme non lue'],'priority'=>['priority','high','Priorité haute'],'normal'=>['priority','normal','Priorité normale'],'archive'=>['archived',1,'Archivée'],'restore'=>['archived',0,'Sortie des archives']];
            if(!isset($operations[$op]))throw new RuntimeException('Choisissez une action pour la sélection.');
            foreach(array_unique($ids) as $value){if(!is_scalar($value)||!ctype_digit((string)$value))throw new RuntimeException('Sélection invalide.');$r=request_row((int)$value);if(!$r)throw new RuntimeException('Un dossier sélectionné n’existe plus.');[$key,$new,$label]=$operations[$op];if((string)$r[$key]!==(string)$new){$r[$key]=$new;request_update($r,$account['login'],$label);}}
            $id=(int)($_POST['detail_id']??0);
        } else throw new RuntimeException('Action inconnue.');
        db()->exec('COMMIT');
    }catch(Throwable $e){db()->exec('ROLLBACK');throw $e;}
    $_SESSION['notice']='Suivi enregistré. Les demandes et leurs messages sont conservés.';go(request_url($f,$id?['id'=>$id]:[]));
}
function request_csv(array $f): never {
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="fecgd-demandes-'.date('Ymd').'.csv"');echo "\xEF\xBB\xBF";$out=fopen('php://output','w');
    fputcsv($out,['Référence','Type','Date','Statut','Priorité','Nom','Organisation','E-mail','Téléphone','Ville'],';');
    foreach(request_rows($f) as $r){$v=$r['fields'];$line=[$r['id'],$r['kind']==='contact'?'Message':'Adhésion',$r['created_at'],request_label($r['status'],$r['kind']),$r['priority']==='high'?'Haute':'Normale',$v['nom']??'',$v['organisation']??'',$v['email']??'',$v['telephone']??'',$v['ville']??''];fputcsv($out,array_map(fn($x)=>is_string($x)&&preg_match('/^[\s]*[=+@\-]/u',$x)?"'".$x:$x,$line),';');}
    fclose($out);exit;
}
function request_hidden(array $f): string {$html='';foreach($f as $key=>$v)$html.='<input type="hidden" name="'.e($key).'" value="'.e($v).'">';return $html;}
