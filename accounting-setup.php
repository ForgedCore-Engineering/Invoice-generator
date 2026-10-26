<?php
session_start();
define('SKIP_DB_INIT',true);
require __DIR__.'/config.php';
require __DIR__.'/includes/accounting.php';
$_SESSION['acct_csrf']=$_SESSION['acct_csrf']??bin2hex(random_bytes(32));
$error=null;$done=false;
$hasImport=is_file(__DIR__.'/accounting-private/manifest.json') && is_file(__DIR__.'/accounting-private/accounting_data.sql');
if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!hash_equals($_SESSION['acct_csrf'],(string)($_POST['csrf']??''))) {http_response_code(403);$error='Session expired. Reload and try again.';}
    else try {
        if(isset($_POST['import']) && !$hasImport) throw new RuntimeException('Workbook import package is missing.');
        $pdo=getDB();
        $pdo->exec(file_get_contents(__DIR__.'/accounting_schema.sql'));
        if(isset($_POST['import'])) {
            $pdo->exec(file_get_contents(__DIR__.'/accounting-private/accounting_data.sql'));
        }
        $done=true;
    } catch(Throwable $e) {
        if(isset($pdo) && $pdo->inTransaction())$pdo->rollBack();
        error_log('Accounting setup: '.$e->getMessage());
        $error='Setup could not finish. Check your MySQL connection and the PHP error log, then retry. Existing data is preserved.';
    }
}
$page_title='Accounting setup';$page_subtitle='Add the workbook records to your invoice database';
require __DIR__.'/includes/header.php';
$manifest=$hasImport?json_decode(file_get_contents(__DIR__.'/accounting-private/manifest.json'),true):null;
?>
<div class="card"><div class="card-hdr"><h2 class="card-title">Set up accounting</h2></div><div class="card-body">
<?php if($error):?><p class="err-box"><?=acct_h($error)?></p><?php endif?>
<?php if($done):?><p>Accounting is ready.</p><a class="btn btn-p" href="accounting.php">Open accounting</a><?php else:?>
<p>This adds accounting tables to the configured invoice database. Importing again skips existing source records and keeps your edits.</p>
<?php if($manifest):?><p style="margin:16px 0">Workbook: <?=acct_h($manifest['filename'])?>. <?=array_sum($manifest['counts'])?> working records, <?=$manifest['source_cells']?> original cells and <?=$manifest['formula_count']?> formulas.</p>
<?php else:?><p style="margin:16px 0">No local workbook package is present. You can create empty accounting tables now, or generate the private import package using the setup instructions.</p><?php endif?>
<form method="post"><input type="hidden" name="csrf" value="<?=acct_h($_SESSION['acct_csrf'])?>">
<label style="display:block;margin:18px 0"><input type="checkbox" name="import" value="1" <?=$hasImport?'checked':'disabled'?>> Import the supplied workbook data and original cell archive</label>
<button class="btn btn-p">Create tables and import</button></form>
<p style="margin-top:18px;color:var(--txt2)">For manual installation, run accounting_schema.sql followed by accounting-private/accounting_data.sql in your selected database.</p>
<?php endif?></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
