<?php
// Integration test: uses ONLY the explicitly supplied isolated database port.
if(PHP_SAPI!=='cli' || empty($argv[1]))exit("Usage: php tools/test-accounting.php ISOLATED_PORT\n");
require __DIR__.'/../includes/accounting.php';
$pdo=new PDO('mysql:host=127.0.0.1;port='.(int)$argv[1].';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS accounting_test CHARACTER SET utf8mb4');$pdo->exec('USE accounting_test');
$checks=0;
function check($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
$pdo->exec(file_get_contents(__DIR__.'/../accounting_schema.sql'));
$seed=file_get_contents(__DIR__.'/../accounting-private/accounting_data.sql');$pdo->exec($seed);
$manifest=json_decode(file_get_contents(__DIR__.'/../accounting-private/manifest.json'),true);
foreach($manifest['counts'] as $table=>$n)check((int)$pdo->query('SELECT COUNT(*) FROM acct_'.$table)->fetchColumn()===$n,'Count '.$table);
check((int)$pdo->query('SELECT COUNT(*) FROM acct_source_cells')->fetchColumn()===$manifest['source_cells'],'All source cells');
check((int)$pdo->query('SELECT COUNT(*) FROM acct_source_cells WHERE formula IS NOT NULL')->fetchColumn()===$manifest['formula_count'],'All formulas, including shared formulas');
$pdo->exec($seed);
foreach($manifest['counts'] as $table=>$n)check((int)$pdo->query('SELECT COUNT(*) FROM acct_'.$table)->fetchColumn()===$n,'Repeat import '.$table);
$t=acct_totals($pdo,true);
check($t['expected']===acct_cents($manifest['totals']['expected_legacy']),'Original expected total');check($t['received']===acct_cents($manifest['totals']['received_legacy']),'Original received');check($t['expenses']===acct_cents($manifest['totals']['expenses']),'Original expenses');check($t['cash']===$t['received']-$t['expenses'],'Original cash');check($t['available']===$t['cash']-$t['reserves'],'Original available');
$all=acct_totals($pdo);
check($all['received']===acct_cents($manifest['totals']['received_all']),'All payments including excluded construction payments');
$sourceSim=$pdo->query("SELECT value_text FROM acct_source_cells WHERE sheet='SIM cards and Serials' AND address='B3' LIMIT 1")->fetchColumn();
check($pdo->query('SELECT service_id FROM acct_sims ORDER BY id LIMIT 1')->fetchColumn()===(string)json_decode($sourceSim,true),'SIM ID remains exact');
check((int)$pdo->query("SELECT COUNT(*) FROM acct_expenses WHERE expense_date IS NULL AND date_original='last month'")->fetchColumn()===1,'Ambiguous dates preserved');
$allocation=['amount'=>'100.00','sourcer_rate'=>'0.050000','dividend_rate'=>'0.100000','department_rate'=>'0.350000','holdings_rate'=>'0.500000','sourcer_override'=>'20.00','dividend_override'=>null,'department_override'=>null,'holdings_override'=>null];
$calc=acct_allocation($allocation);check($calc['sourcer']===2000,'Manual allocation override');check($calc['variance']===-1500,'Allocation exception exposed');
$allocation['sourcer_override']='0';check(acct_allocation($allocation)['sourcer']===0,'Zero override preserved');
$allocation['sourcer_override']=null;$allocation['amount']='100.10';check(acct_allocation($allocation)['sourcer']===501,'Half-up rounding');
check(acct_money(9999999999999999)==='99,999,999,999,999.99','Exact large monetary display');
$allocation['amount']='99999999999999.99';$allocation['sourcer_rate']='1.000000';check(acct_allocation($allocation)['sourcer']===9999999999999999,'Exact large allocation without multiplication overflow');
$bad=[['expenses',['amount'=>'-1','description'=>'test']],['expenses',['amount'=>'1.001','description'=>'test']],['expenses',['amount'=>'1','description'=>'test','expense_date'=>'2026-02-30']],['sims',['sim_number'=>'=1+1','service_id'=>'123']]];
foreach($bad as $case){$caught=false;try{acct_validate($case[0],$case[1]);}catch(InvalidArgumentException $e){$caught=true;}check($caught,'Reject invalid input');}
$examples=[
 'engagements'=>['name'=>'Test account','expected'=>'100.00','legacy_expected'=>'1'],
 'payments'=>['engagement_id'=>'1','amount'=>'10.05','payment_date'=>'2026-10-26','legacy_received'=>'1'],
 'expenses'=>['amount'=>'2.25','expense_date'=>'2026-10-26','description'=>'Test expense'],
 'projects'=>['name'=>'Test domain','kind'=>'Domain','priority'=>'High','status'=>'Pending','start_date'=>'2026-10-26','expiry_date'=>'2027-10-26'],
 'assets'=>['name'=>'Test asset','quantity'=>'2'],
 'sims'=>['sim_number'=>'0590000000','service_id'=>'8912345678901234567'],
 'reserves'=>['name'=>'Test reserve','amount'=>'50.00'],
 'allocations'=>['name'=>'Test allocation','department'=>'Software','amount'=>'100.00','sourcer_rate'=>'5','dividend_rate'=>'10','department_rate'=>'35','holdings_rate'=>'50','status'=>'Completed'],
];
foreach($examples as $entity=>$data){
 $id=acct_save($pdo,$entity,$data);$s=$pdo->query('SELECT * FROM acct_'.$entity.' WHERE id='.$id);$before=$s->fetch();check((int)$before['version']===1,'Create '.$entity);
 $data['id']=$id;$data['version']=1;acct_save($pdo,$entity,$data);
 $caught=false;try{acct_save($pdo,$entity,$data);}catch(RuntimeException $e){$caught=true;}check($caught,'Concurrent edit protection '.$entity);
 $data['version']=2;acct_save($pdo,$entity,$data,true);check(!(bool)$pdo->query('SELECT id FROM acct_'.$entity.' WHERE id='.$id)->fetch(),'Delete '.$entity);
}
check((int)$pdo->query('SELECT COUNT(*) FROM acct_audit')->fetchColumn()===24,'Audit create/edit/delete for each register');
check((int)$pdo->query('SELECT COUNT(*) FROM app_activity')->fetchColumn()===24,'Every accounting change also has a footprint');
check((int)$pdo->query('SELECT COUNT(*) FROM app_activity WHERE actor_email IS NOT NULL')->fetchColumn()===0,'CLI operations are not falsely attributed to a team member');
$before=(int)$pdo->query('SELECT COUNT(*) FROM acct_engagements')->fetchColumn();$caught=false;
try{acct_save($pdo,'engagements',['id'=>1,'version'=>1],true);}catch(PDOException $e){$caught=true;}
check($caught,'Do not delete account with payments');check((int)$pdo->query('SELECT COUNT(*) FROM acct_engagements')->fetchColumn()===$before,'Failed delete rolled back');
$pdo->exec("UPDATE acct_assets SET specification='Preserved edit' WHERE source_ref='Company Assets Register!A2'");$pdo->exec($seed);
check($pdo->query("SELECT specification FROM acct_assets WHERE source_ref='Company Assets Register!A2'")->fetchColumn()==='Preserved edit','Repeat import preserves edits');
echo "$checks integration checks passed.\n";
