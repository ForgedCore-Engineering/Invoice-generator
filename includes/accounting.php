<?php
require_once __DIR__.'/activity.php';
/** Accounting domain rules. Monetary calculations use integer pesewas. */
function acct_entities() {
    return [
        'engagements'=>['title'=>'Client accounts','fields'=>['name'=>['Client / work item','text',true], 'expected'=>['Expected amount (GH₵)','money'], 'legacy_expected'=>['Include in original workbook expected total','check'], 'notes'=>['Notes','textarea']]],
        'payments'=>['title'=>'Income','fields'=>['engagement_id'=>['Client account','engagement',true], 'amount'=>['Received (GH₵)','money',true], 'payment_date'=>['Payment date','date'], 'date_original'=>['Original date / unresolved date','text'], 'notes'=>['Comments','textarea'], 'legacy_received'=>['Include in original workbook received total','check']]],
        'expenses'=>['title'=>'Expenses','fields'=>['amount'=>['Amount (GH₵)','money',true], 'expense_date'=>['Expense date','date'], 'date_original'=>['Original date / unresolved date','text'], 'description'=>['Remarks / purpose','textarea',true], 'payment_details'=>['Payment details / payee','textarea'], 'notes'=>['Notes','textarea']]],
        'allocations'=>['title'=>'Income allocations','fields'=>['name'=>['Project','text',true], 'department'=>['Department','text',true], 'amount'=>['Project amount (GH₵)','money',true], 'sourcer_rate'=>['Client sourcer (%)','rate',true], 'dividend_rate'=>['Dividends (%)','rate',true], 'department_rate'=>['Working department (%)','rate',true], 'holdings_rate'=>['Company holdings (%)','rate',true], 'sourcer_override'=>['Fixed sourcer amount (blank = calculate)','money'], 'dividend_override'=>['Fixed dividends (blank = calculate)','money'], 'department_override'=>['Fixed department amount (blank = calculate)','money'], 'holdings_override'=>['Fixed holdings (blank = calculate)','money'], 'status'=>['Status','text',true], 'notes'=>['Notes','textarea']]],
        'projects'=>['title'=>'Projects & domains','fields'=>['name'=>['Project / domain','text',true], 'kind'=>['Type','select:Project,Domain',true], 'start_date'=>['Initiation date','date'], 'expiry_date'=>['Expiry date','date'], 'handler'=>['Handlers','text'], 'department'=>['Department','text'], 'priority'=>['Priority','select:Low,Medium,High',true], 'status'=>['Status','text',true], 'notes'=>['Remarks','textarea']]],
        'assets'=>['title'=>'Asset register','fields'=>['name'=>['Company asset','text',true], 'quantity'=>['Quantity','integer',true], 'specification'=>['Specification','textarea']]],
        'sims'=>['title'=>'SIM register','fields'=>['sim_number'=>['SIM number','identifier',true], 'service_id'=>['Service ID / MSISDN','identifier',true], 'notes'=>['Notes','textarea']]],
        'reserves'=>['title'=>'Savings & reserves','fields'=>['name'=>['Reserve','text',true], 'amount'=>['Cash deduction (GH₵)','money',true], 'notes'=>['Notes','textarea']]],
    ];
}
function acct_h($x) { return htmlspecialchars((string)($x ?? ''), ENT_QUOTES, 'UTF-8'); }
function acct_cents($value) {
    if (!preg_match('/^\d{1,14}(?:\.\d{1,2})?$/D', (string)$value)) throw new InvalidArgumentException('Enter a non-negative amount with at most two decimal places.');
    $p = explode('.',(string)$value);
    return (int)$p[0]*100+(int)str_pad($p[1]??'',2,'0');
}
function acct_money($cents,$grouped=true) {
    $sign=$cents<0?'-':'';$cents=abs($cents);$whole=intdiv($cents,100);
    return $sign.($grouped?number_format($whole,0):$whole).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT);
}
function acct_rate_units($fraction) {
    $parts=explode('.',(string)$fraction);
    return (int)$parts[0]*1000000+(int)str_pad(substr($parts[1]??'',0,6),6,'0');
}
function acct_allocation($row) {
    $result=[]; $amount=acct_cents($row['amount']);
    foreach (['sourcer','dividend','department','holdings'] as $key) {
        $result[$key] = $row[$key.'_override'] !== null && $row[$key.'_override'] !== ''
            ? acct_cents($row[$key.'_override'])
            : intdiv($amount,1000000)*acct_rate_units($row[$key.'_rate'])
                + intdiv(($amount%1000000)*acct_rate_units($row[$key.'_rate'])+500000,1000000);
    }
    $result['variance']=$amount-array_sum($result);
    return $result;
}
function acct_validate($entity,$input) {
    $defs=acct_entities();
    if (!isset($defs[$entity])) throw new InvalidArgumentException('Unknown record type.');
    $out=[];
    foreach($defs[$entity]['fields'] as $key=>$field) {
        $type=$field[1]; $v=trim((string)($input[$key]??''));
        if ($type==='check') { $out[$key]=isset($input[$key]) && $input[$key]==='1'?1:0; continue; }
        if ($v==='' && !empty($field[2])) throw new InvalidArgumentException($field[0].' is required.');
        if ($type==='money') { if($v!=='') acct_cents($v); $out[$key]=$v===''?null:$v; }
        elseif($type==='rate') {
            if(!preg_match('/^\d{1,3}(?:\.\d{1,4})?$/D',$v) || (float)$v>100) throw new InvalidArgumentException('Rates must be between 0 and 100%.');
            $parts=explode('.',$v);$units=(int)$parts[0]*10000+(int)str_pad($parts[1]??'',4,'0');
            $out[$key]=intdiv($units,1000000).'.'.str_pad((string)($units%1000000),6,'0',STR_PAD_LEFT);
        } elseif($type==='date') {
            $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
            if($v!=='' && (!$d || $d->format('Y-m-d')!==$v)) throw new InvalidArgumentException('Enter a valid date.');
            $out[$key]=$v===''?null:$v;
        } elseif($type==='integer' || $type==='engagement') {
            if(!preg_match('/^\d{1,9}$/D',$v) || ($type==='engagement' && (int)$v<1)) throw new InvalidArgumentException('Enter a valid whole number.');
            $out[$key]=(int)$v;
        } else {
            if(strpos($type,'select:')===0 && !in_array($v,explode(',',substr($type,7)),true)) throw new InvalidArgumentException('Select a valid '.$field[0].'.');
            if(strlen($v)>($type==='textarea'?60000:($key==='service_id'?80:($key==='sim_number'?40:255)))) throw new InvalidArgumentException($field[0].' is too long.');
            if($type==='identifier' && !preg_match('/^[0-9+ -]+$/D',$v)) throw new InvalidArgumentException('Identifiers must contain digits, spaces, + or -.');
            $out[$key]=$v;
        }
    }
    if($entity==='allocations') {
        $sum=0;foreach(['sourcer','dividend','department','holdings'] as $k)$sum+=acct_rate_units($out[$k.'_rate']);
        if($sum!==1000000) throw new InvalidArgumentException('The four allocation percentages must total 100%. Fixed overrides may create a variance.');
    }
    if($entity==='projects' && $out['start_date'] && $out['expiry_date'] && $out['expiry_date']<$out['start_date']) throw new InvalidArgumentException('Expiry cannot precede initiation.');
    return $out;
}
function acct_save(PDO $pdo,$entity,$input,$delete=false) {
    $defs=acct_entities(); if(!isset($defs[$entity])) throw new InvalidArgumentException('Unknown record type.');
    $id=filter_var($input['id']??0,FILTER_VALIDATE_INT);$version=filter_var($input['version']??0,FILTER_VALIDATE_INT);
    if($id===false || $id<0) throw new InvalidArgumentException('Invalid record.');
    $table='acct_'.$entity; $pdo->beginTransaction();
    try {
        $before=null;
        if($id) {
            $stmt=$pdo->prepare("SELECT * FROM $table WHERE id=? FOR UPDATE");$stmt->execute([$id]);$before=$stmt->fetch();
            if(!$before || (int)$before['version']!==$version) throw new RuntimeException('This record changed in another window. Reload before saving.');
        }
        if($delete) {
            if(!$id)throw new InvalidArgumentException('Choose a record.');
            $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);$after=null;$action='delete';
        } else {
            $data=acct_validate($entity,$input);
            if($entity==='payments') {
                $s=$pdo->prepare('SELECT id FROM acct_engagements WHERE id=?');$s->execute([$data['engagement_id']]);
                if(!$s->fetch()) throw new InvalidArgumentException('Choose an existing client account.');
            }
            if($id) {
                $sets=implode(',',array_map(function($k){return "$k=?";},array_keys($data)));
                $pdo->prepare("UPDATE $table SET $sets,version=version+1 WHERE id=?")->execute(array_merge(array_values($data),[$id]));
                $action='update';
            } else {
                $cols=implode(',',array_keys($data));$marks=implode(',',array_fill(0,count($data),'?'));
                $pdo->prepare("INSERT INTO $table ($cols) VALUES ($marks)")->execute(array_values($data));$id=(int)$pdo->lastInsertId();$action='create';
            }
            $s=$pdo->prepare("SELECT * FROM $table WHERE id=?");$s->execute([$id]);$after=$s->fetch();
        }
        $pdo->prepare('INSERT INTO acct_audit(entity,record_id,action,before_json,after_json) VALUES (?,?,?,?,?)')->execute([$entity,$id,$action,$before?json_encode($before):null,$after?json_encode($after):null]);
        fc_activity_log($pdo,'accounting.'.$entity,$id,$action,$before,$after);
        $pdo->commit();return $id;
    } catch(Throwable $e) {$pdo->rollBack();throw $e;}
}
function acct_totals(PDO $pdo,$legacy=false) {
    $e=$pdo->query('SELECT COALESCE(SUM(expected),0) FROM acct_engagements'.($legacy?' WHERE legacy_expected=1':''))->fetchColumn();
    $p=$pdo->query('SELECT COALESCE(SUM(amount),0) FROM acct_payments'.($legacy?' WHERE legacy_received=1':''))->fetchColumn();
    $x=$pdo->query('SELECT COALESCE(SUM(amount),0) FROM acct_expenses')->fetchColumn();
    $r=$pdo->query('SELECT COALESCE(SUM(amount),0) FROM acct_reserves')->fetchColumn();
    return ['expected'=>acct_cents($e),'received'=>acct_cents($p),'expenses'=>acct_cents($x),'reserves'=>acct_cents($r),'outstanding'=>acct_cents($e)-acct_cents($p),'cash'=>acct_cents($p)-acct_cents($x),'available'=>acct_cents($p)-acct_cents($x)-acct_cents($r)];
}
