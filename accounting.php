<?php
session_start();
define('SKIP_DB_INIT',true);
require __DIR__.'/config.php';
require __DIR__.'/includes/accounting.php';
$_SESSION['acct_csrf']=$_SESSION['acct_csrf']??bin2hex(random_bytes(32));
$entities=acct_entities();$view=(string)($_GET['view']??'overview');
if(!isset($entities[$view]) && !in_array($view,['overview','source','audit'],true))$view='overview';
$error=null;$dbError=false;$pdo=null;$rows=[];$edit=null;$accounts=[];
try {
    $pdo=getDB();$pdo->query('SELECT id FROM acct_engagements LIMIT 1');
    if($_SERVER['REQUEST_METHOD']==='POST') {
        if(!hash_equals($_SESSION['acct_csrf'],(string)($_POST['csrf']??''))) {http_response_code(403);throw new RuntimeException('Session expired. Reload and try again.');}
        if(!isset($entities[$view])) throw new InvalidArgumentException('Choose a record type.');
        try {
            acct_save($pdo,$view,$_POST,($_POST['action']??'')==='delete');
            $_SESSION['acct_flash']='Record saved. Reports have been updated.';
            header('Location: accounting.php?view='.urlencode($view));exit;
        } catch(PDOException $e) {error_log($e->getMessage());$error='Could not save. A client account with payments cannot be deleted. Check the record and try again.';$edit=$_POST;}
          catch(InvalidArgumentException | RuntimeException $e) {$error=$e->getMessage();$edit=$_POST;}
    }
    $accounts=$pdo->query('SELECT id,name FROM acct_engagements ORDER BY name')->fetchAll();
    if(isset($entities[$view])) {
        $rows=$pdo->query('SELECT * FROM acct_'.$view.' ORDER BY id')->fetchAll();
        $id=(int)($_GET['edit']??0);
        foreach($rows as $row)if((int)$row['id']===$id && !$error)$edit=$row;
        if($id && !$edit){http_response_code(404);$error='Record no longer exists.';}
    }
} catch(Throwable $e) {
    if($e instanceof PDOException || !$pdo) {$dbError=true;$error='Accounting is not available. Start MySQL and run accounting setup.';error_log($e->getMessage());}
    else {$error=$e->getMessage();if($_SERVER['REQUEST_METHOD']==='POST')$edit=$_POST;}
}
// CSV exports contain the same records as the current register; protect spreadsheet readers from formulas.
if(isset($_GET['export']) && isset($entities[$view]) && !$dbError && !$error) {
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="accounting-'.$view.'.csv"');
    $f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");
    $fields=array_merge(['id'],array_keys($entities[$view]['fields']),['source_ref']);
    if($view==='allocations')$fields=array_merge($fields,['sourcer','dividend','department','holdings','variance']);
    fputcsv($f,$fields);
    foreach($rows as $r) {
        if($view==='allocations')foreach(acct_allocation($r) as $k=>$v)$r[$k]=acct_money($v,false);
        $line=[];foreach($fields as $key){$v=(string)($r[$key]??'');if(preg_match('/^[\s]*[=+@-]/u',$v))$v="'".$v;$line[]=$v;}fputcsv($f,$line);
    } fclose($f);exit;
}
$page_title='Accounting';$page_subtitle='Finances, projects and company registers';
require __DIR__.'/includes/header.php';
?>
<style>
.main,.pg{min-width:0}.acct-table{max-width:100%}
.acct-tabs{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:22px}.acct-tabs a{padding:8px 12px;border:1px solid var(--br);border-radius:8px;font-size:12px;color:var(--txt2)}.acct-tabs a.active{background:var(--acc-bg);color:var(--acc);border-color:var(--acc)}
.acct-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:22px}.acct-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.acct-field{display:flex;flex-direction:column;gap:6px;font-size:12px;color:var(--txt2)}
.acct-field input:not([type=checkbox]),.acct-field select,.acct-field textarea,.acct-search{width:100%;background:var(--inp);border:1px solid var(--br);border-radius:7px;color:var(--txt);padding:10px;font:inherit}.acct-field textarea{min-height:78px}.acct-wide{grid-column:1/-1}.acct-table{overflow:auto;max-height:68vh}.acct-table table{width:100%;border-collapse:collapse;font-size:12px}.acct-table th{position:sticky;top:0;background:var(--bg2);z-index:1;text-align:left;white-space:nowrap}.acct-table th,.acct-table td{padding:12px 14px;border-bottom:1px solid var(--br);vertical-align:top}.acct-table td{min-width:100px;max-width:330px;overflow-wrap:anywhere}.acct-table tr:hover td{background:var(--card-h)}.acct-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.acct-note{font-size:12px;color:var(--txt2);margin:12px 0}.acct-pill{background:var(--acc-bg);color:var(--acc);padding:3px 8px;border-radius:12px;white-space:nowrap}.acct-warning{color:var(--ylw)}.acct-bar{display:flex;height:22px;border-radius:5px;overflow:hidden;background:var(--inp)}.acct-bar span{display:block;min-width:1px}.acct-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.acct-empty{padding:32px;color:var(--txt2)}
@media(max-width:1024px){.acct-grid,.acct-form{grid-template-columns:1fr}.acct-wide{grid-column:auto}.acct-tabs{gap:5px}.acct-tabs a{padding:7px 9px}}
.acct-summary-row,.acct-renewal-row{display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px 16px}.acct-summary-row strong,.acct-renewal-row span{float:none!important}.acct-field{min-width:0}.acct-field input,.acct-field select,.acct-field textarea{max-width:100%}.acct-note{overflow-wrap:anywhere}
@media(max-width:640px){
 .hdr-acts{gap:4px}.hdr-acts .btn{padding:10px}#acct-register td:empty{display:none!important}
 .acct-tabs{flex-wrap:nowrap;overflow-x:auto;overscroll-behavior-x:contain;padding-bottom:8px;scrollbar-width:thin}.acct-tabs a{flex:0 0 auto;display:flex;align-items:center;min-height:44px;padding:10px 12px}
 .acct-actions .btn,.acct-form .btn{min-height:44px;justify-content:center}.acct-actions h2{flex-basis:100%}.acct-actions>.btn{flex:1 1 auto}
 .acct-field input:not([type=checkbox]),.acct-field select,.acct-field textarea,.acct-search{font-size:16px;min-height:46px}.acct-field input[type=checkbox]{width:24px;height:24px}.acct-form .acct-actions .btn{flex:1}
 #acct-register,#acct-register tbody,#acct-register tr,#acct-register td{display:block;width:100%;max-width:none;min-width:0}#acct-register thead{display:none}#acct-register tr{padding:10px 14px;border-bottom:1px solid var(--br)}#acct-register tr[hidden]{display:none}#acct-register td{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:12px;padding:9px 0;border:0;text-align:left;white-space:normal;overflow-wrap:anywhere}#acct-register td::before{content:attr(data-label);font-size:11px;color:var(--txt3);font-weight:500}#acct-register td:first-child{display:flex;justify-content:flex-end;padding:0 0 4px}#acct-register td:first-child::before{display:none}#acct-register .btn{min-height:44px;min-width:64px;justify-content:center}
 .acct-register-wrap{max-height:none}.acct-summary-row strong{margin-left:auto}.acct-renewal-row span{margin-left:auto}.acct-renewal-row{padding:6px 0}.acct-table{touch-action:pan-x pan-y}
}
@media print{.sb,.hdr,.bottom-nav,.acct-tabs,.acct-actions,form,.btn{display:none!important}.main{margin:0}.acct-table{max-height:none;overflow:visible}.card{break-inside:avoid}.acct-table th{position:static}}
</style>
<nav class="acct-tabs" aria-label="Accounting sections">
<?php foreach(array_merge(['overview'=>['title'=>'Overview']],$entities,['source'=>['title'=>'Workbook review'],'audit'=>['title'=>'Change history']]) as $k=>$d):?>
<a class="<?=$view===$k?'active':''?>" href="accounting.php?view=<?=$k?>"><?=acct_h($d['title'])?></a>
<?php endforeach?></nav>
<?php if($error):?><div class="err-box" role="alert" style="margin-bottom:18px"><?=acct_h($error)?> <?php if($dbError):?><a class="btn btn-s" href="accounting-setup.php">Accounting setup</a><?php endif?></div><?php endif?>
<?php if(isset($_SESSION['acct_flash'])):?><p class="acct-note" role="status"><?=acct_h($_SESSION['acct_flash'])?></p><?php unset($_SESSION['acct_flash']);endif?>
<?php if(!$dbError && $pdo): ?>
<?php if($view==='overview'):
$legacy=($_GET['basis']??'legacy')!=='all';$t=acct_totals($pdo,$legacy);
?>
<div class="acct-actions" style="margin-bottom:14px"><h2 style="font-size:20px;margin-right:auto">Financial overview</h2><a class="btn <?=$legacy?'btn-s':'btn-p'?>" href="accounting.php?basis=all">All entries</a><a class="btn <?=$legacy?'btn-p':'btn-s'?>" href="accounting.php?basis=legacy">Original workbook totals</a><button class="btn btn-s" onclick="window.print()">Print</button></div>
<p class="acct-note"><?=$legacy?'Uses the inclusion flags matching Excel’s manually selected total cells. New entries are included by default.':'Includes every recorded payment and expense. Gross construction receipts and profit deposits may overlap; review their meaning before using this as a cash balance. Expected amounts also include unresolved lighting entries.'?> Receipt and payslip records are available in the sidebar. They are not automatically added to these totals to avoid counting existing Excel entries twice.</p>
<div class="sg">
<?php foreach(['received'=>['Received','am'],'expenses'=>['Expenses','bl'],'cash'=>['Cash remaining','gn'],'available'=>['After savings & reserves','am']] as $key=>$label):?>
<div class="sc <?=$label[1]?>"><div class="sc-bar"></div><div class="sc-val" style="font-size:22px">GH₵ <?=acct_money($t[$key])?></div><div class="sc-lbl"><?=$label[0]?></div></div>
<?php endforeach?></div>
<div class="acct-grid"><div class="card"><div class="card-hdr"><h3 class="card-title">Expected amounts & receipts</h3></div><div class="card-body">
<p class="acct-summary-row">Expected <strong>GH₵ <?=acct_money($t['expected'])?></strong></p><p class="acct-summary-row">Expected less received <strong>GH₵ <?=acct_money($t['outstanding'])?></strong></p><p class="acct-summary-row">Reserved <strong>GH₵ <?=acct_money($t['reserves'])?></strong></p>
<p class="acct-note">Cash remaining = received − expenses. Available cash = cash remaining − reserves. Unknown expected amounts remain blank.</p><a class="btn btn-s" href="accounting.php?view=engagements">Review client accounts</a></div></div>
<div class="card"><div class="card-hdr"><h3 class="card-title">Project activity</h3></div><div class="card-body">
<?php foreach($pdo->query('SELECT status,COUNT(*) n FROM acct_projects GROUP BY status ORDER BY n DESC')->fetchAll() as $r):?><p style="margin:7px 0"><span class="acct-pill"><?=acct_h($r['status'])?></span> <strong style="float:right"><?=$r['n']?></strong></p><?php endforeach?>
<a class="btn btn-s" href="accounting.php?view=projects" style="margin-top:12px">Manage projects & domains</a></div></div></div>
<div class="card" style="margin-bottom:20px"><div class="card-hdr"><h3 class="card-title">Monthly cash activity</h3><span class="card-sub"><?=$legacy?'Original workbook inclusion flags':'All entries'?></span></div><div class="acct-table"><table><thead><tr><th>Month</th><th>Received (GH₵)</th><th>Expenses (GH₵)</th><th>Net (GH₵)</th><th>Activity</th></tr></thead><tbody>
<?php
$months=[];$undated=['received'=>0,'expenses'=>0];
foreach(['received'=>['payments','payment_date'],'expenses'=>['expenses','expense_date']] as $key=>$spec){
 foreach($pdo->query('SELECT '.$spec[1].' d,amount FROM acct_'.$spec[0].($legacy && $key==='received'?' WHERE legacy_received=1':''))->fetchAll() as $r){
  if(!$r['d']){$undated[$key]+=acct_cents($r['amount']);continue;}$m=substr($r['d'],0,7);if(!isset($months[$m]))$months[$m]=['received'=>0,'expenses'=>0];$months[$m][$key]+=acct_cents($r['amount']);
 }}ksort($months);$max=1;foreach($months as $r)$max=max($max,$r['received']+$r['expenses']);
foreach($months as $m=>$r):?>
<tr><td><?=acct_h($m)?></td><td class="acct-num"><?=acct_money($r['received'])?></td><td class="acct-num"><?=acct_money($r['expenses'])?></td><td class="acct-num"><?=acct_money($r['received']-$r['expenses'])?></td><td><div class="acct-bar" title="Received and expenses"><span style="background:var(--acc);width:<?=$r['received']/$max*100?>%"></span><span style="background:var(--blue);width:<?=$r['expenses']/$max*100?>%"></span></div></td></tr>
<?php endforeach?><tr><td>Undated</td><td class="acct-num"><?=acct_money($undated['received'])?></td><td class="acct-num"><?=acct_money($undated['expenses'])?></td><td class="acct-num"><?=acct_money($undated['received']-$undated['expenses'])?></td><td>Original date text retained</td></tr></tbody></table></div></div>
<?php $expiring=$pdo->query("SELECT name,expiry_date FROM acct_projects WHERE kind='Domain' AND expiry_date IS NOT NULL ORDER BY expiry_date")->fetchAll(); ?>
<div class="card"><div class="card-hdr"><h3 class="card-title">Domain renewals</h3></div><div class="card-body"><?php if(!$expiring):?><p>No domains registered.</p><?php endif?><?php foreach($expiring as $r):$days=(int)(new DateTimeImmutable('today',new DateTimeZone('Africa/Accra')))->diff(new DateTimeImmutable($r['expiry_date'],new DateTimeZone('Africa/Accra')))->format('%r%a');?><p class="acct-renewal-row" style="margin:8px 0"><?=acct_h($r['name'])?> <span style="float:right" class="<?=$days<=30?'acct-warning':''?>"><?=acct_h($r['expiry_date'])?> (<?=$days<0?abs($days).' days overdue':$days.' days'?>)</span></p><?php endforeach?></div></div>
<?php elseif(isset($entities[$view])):
$definition=$entities[$view];$showForm=isset($_GET['new']) || $edit!==null;
$displayFields=$definition['fields'];
if($view==='allocations')$displayFields=array_intersect_key($displayFields,array_flip(['name','department','amount','status']));
$accountNames=array_column($accounts,'name','id');
?>
<div class="acct-actions" style="margin-bottom:16px"><h2 style="font-size:20px;margin-right:auto"><?=acct_h($definition['title'])?> <span class="card-sub">(<?=count($rows)?>)</span></h2><a class="btn btn-p" href="accounting.php?view=<?=$view?>&new=1">Add record</a><a class="btn btn-s" href="accounting.php?view=<?=$view?>&export=1">Export CSV</a><button class="btn btn-s" onclick="window.print()">Print</button></div>
<?php if($view==='allocations'):?><p class="acct-note">Allocation = project amount × percentage, rounded to pesewas. A fixed amount takes precedence, including zero. Variance = project amount − all four allocations.</p><?php endif?>
<?php if($showForm):$record=$edit??[]; ?>
<div class="card" style="margin-bottom:20px"><div class="card-hdr"><h3 class="card-title"><?=$edit?'Edit record':'Add record'?></h3><a href="accounting.php?view=<?=$view?>" class="btn btn-s">Close</a></div><div class="card-body">
<form method="post" class="acct-form"><input type="hidden" name="csrf" value="<?=acct_h($_SESSION['acct_csrf'])?>"><input type="hidden" name="id" value="<?=(int)($record['id']??0)?>"><input type="hidden" name="version" value="<?=(int)($record['version']??0)?>">
<?php foreach($definition['fields'] as $key=>$field):$type=$field[1];$value=$record[$key]??'';
if(!$edit){$defaults=['sourcer_rate'=>'5','dividend_rate'=>'10','department_rate'=>'35','holdings_rate'=>'50','quantity'=>1,'priority'=>'Medium','kind'=>'Project','status'=>'Pending','legacy_expected'=>1,'legacy_received'=>1];$value=$defaults[$key]??'';}
elseif($type==='rate' && $_SERVER['REQUEST_METHOD']!=='POST')$value=(float)$value*100;
?>
<label class="acct-field <?=$type==='textarea'?'acct-wide':''?>"><span><?=acct_h($field[0])?><?=!empty($field[2])?' *':''?></span>
<?php if($type==='textarea'):?><textarea name="<?=$key?>" <?=!empty($field[2])?'required':''?>><?=acct_h($value)?></textarea>
<?php elseif($type==='engagement' || strpos($type,'select:')===0):?><select name="<?=$key?>" required>
<?php $options=$type==='engagement'?$accountNames:array_combine(explode(',',substr($type,7)),explode(',',substr($type,7))); if($type==='engagement'):?><option value="">Select client account</option><?php endif?>
<?php foreach($options as $option=>$label):?><option value="<?=acct_h($option)?>" <?=(string)$value===(string)$option?'selected':''?>><?=acct_h($label)?></option><?php endforeach?></select>
<?php elseif($type==='check'):?><input type="checkbox" name="<?=$key?>" value="1" <?=$value?'checked':''?> style="align-self:flex-start">
<?php else:?><input name="<?=$key?>" value="<?=acct_h($value)?>" type="<?=$type==='date'?'date':(in_array($type,['money','rate','integer'])?'number':'text')?>" <?=in_array($type,['money','rate','integer'])?'min="0" step="'.($type==='integer'?'1':($type==='rate'?'0.0001':'0.01')).'"':''?> <?=!empty($field[2])?'required':''?>><?php endif?></label>
<?php endforeach?>
<div class="acct-wide acct-actions"><button class="btn btn-p" name="action" value="save">Save record</button><a class="btn btn-s" href="accounting.php?view=<?=$view?>">Cancel</a></div></form>
<?php if(!empty($record['id'])):?><form method="post" style="margin-top:18px" onsubmit="return confirm('Delete this record? Its previous values will remain in change history.');"><input type="hidden" name="csrf" value="<?=acct_h($_SESSION['acct_csrf'])?>"><input type="hidden" name="id" value="<?=(int)$record['id']?>"><input type="hidden" name="version" value="<?=(int)($record['version']??0)?>"><button class="btn btn-d" name="action" value="delete">Delete record</button></form><?php endif?>
</div></div><?php endif?>
<input class="acct-search" id="acct-search" aria-label="Search records" placeholder="Search this register…" style="margin-bottom:14px;max-width:420px">
<div class="card"><div class="acct-table acct-register-wrap"><table id="acct-register"><thead><tr><th>Action</th>
<?php foreach($displayFields as $key=>$field):?><th><?=acct_h($field[0])?></th><?php endforeach?>
<?php if($view==='engagements'):?><th>Received (GH₵)</th><th>Balance (GH₵)</th><?php endif?>
<?php if($view==='allocations'):foreach(['Sourcer','Dividends','Department','Holdings','Variance'] as $label):?><th><?=$label?> (GH₵)</th><?php endforeach;endif?><th>Source</th></tr></thead><tbody>
<?php $received=[];if($view==='engagements')foreach($pdo->query('SELECT engagement_id,SUM(amount) amount FROM acct_payments GROUP BY engagement_id')->fetchAll() as $p)$received[$p['engagement_id']]=acct_cents($p['amount']);
foreach($rows as $row):?><tr><td><a class="btn btn-s btn-sm" href="accounting.php?view=<?=$view?>&edit=<?=$row['id']?>">Edit</a></td>
<?php foreach($displayFields as $key=>$field):$val=$row[$key];$type=$field[1]; ?>
<td data-label="<?=acct_h($field[0])?>" class="<?=in_array($type,['money','rate','integer'])?'acct-num':''?>">
<?php if($type==='money'):?><?=$val===null?'—':acct_money(acct_cents($val))?>
<?php elseif($type==='rate'):?><?=acct_h(rtrim(rtrim(number_format((float)$val*100,4,'.',''),'0'),'.'))?>%
<?php elseif($type==='check'):?><?=$val?'Yes':'No'?>
<?php elseif($type==='engagement'):?><?=acct_h($accountNames[$val]??'Missing account')?>
<?php else:?><?=nl2br(acct_h($val))?><?php endif?></td><?php endforeach?>
<?php if($view==='engagements'):$paid=$received[$row['id']]??0;?><td data-label="Received (GH₵)" class="acct-num"><?=acct_money($paid)?></td><td data-label="Balance (GH₵)" class="acct-num"><?=$row['expected']===null?'Unknown':acct_money(acct_cents($row['expected'])-$paid)?></td><?php endif?>
<?php if($view==='allocations'):foreach(acct_allocation($row) as $k=>$val):?><td data-label="<?=acct_h(ucfirst($k))?> (GH₵)" class="acct-num <?=$k==='variance' && $val!==0?'acct-warning':''?>"><?=acct_money($val)?></td><?php endforeach;endif?>
<td data-label="Source"><?=acct_h($row['source_ref']??'Entered in app')?></td></tr><?php endforeach?>
</tbody></table><?php if(!$rows):?><p class="acct-empty">No records yet. Add a record to get started.</p><?php endif?></div></div><p id="acct-search-count" class="acct-note" aria-live="polite"></p>
<?php if($view==='allocations'):
$groups=[];foreach($rows as $r){$d=$r['department'];if(!isset($groups[$d]))$groups[$d]=['amount'=>0,'sourcer'=>0,'dividend'=>0,'department'=>0,'holdings'=>0,'variance'=>0];$groups[$d]['amount']+=acct_cents($r['amount']);foreach(acct_allocation($r) as $k=>$val)$groups[$d][$k]+=$val;}
?>
<div class="card" style="margin-top:20px"><div class="card-hdr"><h3 class="card-title">Department allocation totals</h3><span class="card-sub">All projects in this register</span></div><div class="acct-table"><table><thead><tr><th>Department</th><th>Project amounts</th><th>Sourcer</th><th>Dividends</th><th>Working department</th><th>Holdings</th><th>Variance</th></tr></thead><tbody><?php foreach($groups as $dept=>$g):?><tr><td><?=acct_h($dept)?></td><?php foreach($g as $key=>$val):?><td class="acct-num <?=$key==='variance' && $val!==0?'acct-warning':''?>"><?=acct_money($val)?></td><?php endforeach?></tr><?php endforeach?></tbody></table></div></div>
<?php endif?>
<script>document.getElementById('acct-search').addEventListener('input',function(){var q=this.value.toLowerCase(),n=0;document.querySelectorAll('#acct-register tbody tr').forEach(function(r){r.hidden=!r.textContent.toLowerCase().includes(q);if(!r.hidden)n++;});document.getElementById('acct-search-count').textContent=n+' matching records';});</script>
<?php elseif($view==='source'):
$imports=$pdo->query('SELECT * FROM acct_imports ORDER BY id DESC')->fetchAll();
?>
<div class="card" style="margin-bottom:20px"><div class="card-hdr"><h2 class="card-title">Workbook reconciliation & review</h2></div><div class="card-body">
<?php if(!$imports):?><p>No workbook imported. <a class="btn btn-s" href="accounting-setup.php">Import workbook</a></p><?php endif?>
<?php foreach($imports as $i):$m=json_decode($i['manifest'],true);?><p><strong><?=acct_h($i['filename'])?></strong> · Imported <?=acct_h($i['imported_at'])?></p><p class="acct-note"><?=$m['source_cells']?> original cells · <?=$m['formula_count']?> formulas · <?=count($m['sheets'])?> worksheets. This archive preserves the import; app edits update live registers only.</p>
<?php foreach($m['issues'] as $issue):?><p class="acct-note"><strong><?=acct_h($issue['reference'])?>:</strong> <?=acct_h($issue['issue'])?> <?=acct_h($issue['value']??'')?></p><?php endforeach?>
<?php $live=acct_totals($pdo,true);?><div class="acct-table"><table><thead><tr><th>Original total</th><th>Workbook (GH₵)</th><th>Current flagged entries (GH₵)</th><th>Difference (GH₵)</th></tr></thead><tbody><?php foreach(['expected_legacy'=>'expected','received_legacy'=>'received','expenses'=>'expenses'] as $k=>$lk):$c=acct_cents($m['totals'][$k]);?><tr><td><?=acct_h(str_replace('_',' ',$k))?></td><td class="acct-num"><?=acct_money($c)?></td><td class="acct-num"><?=acct_money($live[$lk])?></td><td class="acct-num"><?=acct_money($live[$lk]-$c)?></td></tr><?php endforeach?></tbody></table></div>
<?php endforeach?></div></div>
<div class="card"><div class="card-hdr"><h3 class="card-title">Original cells & formulas</h3></div><div class="card-body"><form method="get" class="acct-actions"><input type="hidden" name="view" value="source"><label>Worksheet <select name="sheet" class="acct-search" style="width:auto"><?php $sheets=$pdo->query('SELECT DISTINCT sheet FROM acct_source_cells ORDER BY sheet')->fetchAll(PDO::FETCH_COLUMN);$sheet=(string)($_GET['sheet']??($sheets[0]??''));foreach($sheets as $s):?><option <?=$sheet===$s?'selected':''?>><?=acct_h($s)?></option><?php endforeach?></select></label><button class="btn btn-s">View worksheet</button></form></div>
<div class="acct-table"><table><thead><tr><th>Cell</th><th>Original value</th><th>Formula</th></tr></thead><tbody>
<?php $s=$pdo->prepare('SELECT address,value_text,formula FROM acct_source_cells WHERE sheet=? ORDER BY id');$s->execute([$sheet]);foreach($s->fetchAll() as $r):?><tr><td><?=acct_h($r['address'])?></td><td><?=acct_h(json_decode($r['value_text'],true))?></td><td><?=acct_h($r['formula'])?></td></tr><?php endforeach?></tbody></table></div></div>
<?php elseif($view==='audit'):$history=$pdo->query('SELECT * FROM acct_audit ORDER BY id DESC LIMIT 200')->fetchAll(); ?>
<div class="card"><div class="card-hdr"><h2 class="card-title">Change history</h2><span class="card-sub">Latest 200 changes</span></div><div class="acct-table"><table><thead><tr><th>Date</th><th>Register / record</th><th>Action</th><th>Values</th></tr></thead><tbody><?php foreach($history as $r):?><tr><td><?=acct_h($r['created_at'])?></td><td><?=acct_h($r['entity'])?> #<?=(int)$r['record_id']?></td><td><?=acct_h($r['action'])?></td><td><details><summary>Before & after</summary><pre style="white-space:pre-wrap"><?=acct_h(json_encode(['before'=>json_decode($r['before_json']??'null',true),'after'=>json_decode($r['after_json']??'null',true)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))?></pre></details></td></tr><?php endforeach?></tbody></table><?php if(!$history):?><p class="acct-empty">Changes made in the app will appear here.</p><?php endif?></div></div>
<?php endif;endif;require __DIR__.'/includes/footer.php'; ?>
