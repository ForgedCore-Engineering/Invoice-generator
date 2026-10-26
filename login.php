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
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Sign in · ForgedCore</title><link rel="stylesheet" href="assets/auth.css?v=2"></head><body><main class="auth-shell">
<?php require __DIR__.'/includes/auth-hero.php'; ?>
<section class="auth-content"><div class="mobile-brand"><?php require __DIR__.'/includes/auth-brand.php'; ?></div><p class="form-kicker">Team access</p><h1>Welcome back.</h1><p class="muted">Sign in to your ForgedCore workspace with your approved company email.</p>
<?php if($error):?><p class="error" role="alert"><?=login_h($error)?></p><?php endif?>
<?php if(empty($cfg['users'])):?><p class="error">Access is locked until an administrator provisions the approved accounts.</p><?php endif?>
<form method="post"><input type="hidden" name="csrf" value="<?=login_h(fc_auth_csrf())?>"><input type="hidden" name="next" value="<?=login_h($next)?>"><label for="email">Company email</label><input id="email" type="email" name="email" value="<?=login_h($email)?>" placeholder="you@forgedcoreengineering.com" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="254" required><label for="password">Password</label><div class="password"><input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button type="button" class="reveal" id="reveal" aria-label="Show password" aria-pressed="false">Show</button></div><button class="submit">Sign in <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button></form><p class="auth-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Secure access for authorized team members</p><p class="auth-bottom">ForgedCore Engineering Ltd · Team workspace</p></section></main>
<script>document.getElementById('reveal').addEventListener('click',function(){var p=document.getElementById('password'),show=p.type==='password';p.type=show?'text':'password';this.textContent=show?'Hide':'Show';this.setAttribute('aria-label',show?'Hide password':'Show password');this.setAttribute('aria-pressed',show?'true':'false');});</script></body></html>
