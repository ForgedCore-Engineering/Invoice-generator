<?php
require_once __DIR__.'/auth.php';
fc_auth_start();
$next=fc_auth_next($_POST['next']??($_GET['next']??'index.php'));
if(fc_auth_user()){header('Location: '.fc_auth_base().$next);exit;}
$error=null;$email='';$cfg=fc_auth_config();
if($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');
    if(!hash_equals(fc_auth_csrf(),(string)($_POST['csrf']??''))){http_response_code(403);$error='Session expired. Reload and try again.';}
    elseif(strlen($email)>254 || strlen($password)>72){$error='Email or password is incorrect.';}
    else try {
        $error=fc_auth_attempt($email,$password);
        if($error===null){header('Location: '.fc_auth_base().$next,true,303);exit;}
        if(strpos($error,'Too many')===0){http_response_code(429);header('Retry-After: 900');}
    }catch(Throwable $e){error_log('Login unavailable: '.$e->getMessage());$error='Sign-in is unavailable. Contact your administrator.';http_response_code(503);}
}
function login_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Sign in · ForgedCore</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:24px 16px;background:#070c18;color:#f1f5f9;font:15px/1.6 system-ui,sans-serif}.login{width:100%;max-width:430px;background:#121c32;border:1px solid #243d65;border-radius:16px;padding:32px;box-shadow:0 12px 45px #0005}.brand{display:flex;align-items:center;gap:12px;font-weight:700}.mark{display:grid;place-items:center;width:46px;height:46px;border-radius:10px;background:#009999;color:#fff}h1{font-size:26px;margin:28px 0 4px}.muted{color:#94a3b8;font-size:13px;margin:0 0 24px}label{display:block;font-size:13px;margin:17px 0 6px}input{width:100%;min-height:48px;padding:12px;border:1px solid #243d65;border-radius:8px;background:#0a1020;color:#f1f5f9;font:16px system-ui,sans-serif}input:focus-visible,button:focus-visible{outline:2px solid #00b2b2;outline-offset:3px}.password{position:relative}.password input{padding-right:70px}.reveal{position:absolute;right:4px;top:4px;min-height:40px;min-width:56px;border:0;border-radius:6px;background:transparent;color:#94a3b8;cursor:pointer}.submit{width:100%;min-height:48px;border:0;border-radius:8px;background:#009999;color:white;font:600 15px system-ui,sans-serif;margin-top:24px;cursor:pointer}.submit:hover{background:#00b2b2}.error{padding:12px;background:#ef444419;border:1px solid #ef44444d;border-radius:8px;color:#fca5a5;font-size:13px;overflow-wrap:anywhere}.foot{text-align:center;font-size:12px;color:#64748b;margin:24px 0 0}@media(max-width:380px){.login{padding:24px 20px}h1{font-size:24px}}
</style></head><body><main class="login"><div class="brand"><span class="mark">FC</span><div>ForgedCore<br><small style="font-weight:400;color:#94a3b8">Engineering Ltd</small></div></div><h1>Welcome back</h1><p class="muted">Sign in with your approved company email.</p>
<?php if($error):?><p class="error" role="alert"><?=login_h($error)?></p><?php endif?>
<?php if(empty($cfg['users'])):?><p class="error">Access is locked until an administrator provisions the approved accounts.</p><?php endif?>
<form method="post"><input type="hidden" name="csrf" value="<?=login_h(fc_auth_csrf())?>"><input type="hidden" name="next" value="<?=login_h($next)?>"><label for="email">Company email</label><input id="email" type="email" name="email" value="<?=login_h($email)?>" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="254" required><label for="password">Password</label><div class="password"><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" class="reveal" id="reveal" aria-label="Show password">Show</button></div><button class="submit">Sign in</button></form><p class="foot">Authorized team members only</p></main>
<script>document.getElementById('reveal').addEventListener('click',function(){var p=document.getElementById('password'),show=p.type==='password';p.type=show?'text':'password';this.textContent=show?'Hide':'Show';this.setAttribute('aria-label',show?'Hide password':'Show password');});</script></body></html>
