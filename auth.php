<?php
/** Shared authentication. No registration; only locally provisioned accounts can sign in. */
function fc_auth_file() { return __DIR__.'/auth-private/users.json'; }
function fc_auth_config() {
    $path=fc_auth_file();
    if(!is_file($path))return ['domain'=>'forgedcoreengineering.com','users'=>[]];
    $data=json_decode(file_get_contents($path),true);
    return is_array($data) && isset($data['domain'],$data['users'])?$data:['domain'=>'forgedcoreengineering.com','users'=>[]];
}
function fc_auth_base() {
    $path=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php'));
    return rtrim($path,'/.').'/';
}
function fc_auth_start() {
    static $headersSet=false;
    if(session_status()!==PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
        session_name('forgedcore_session');
        session_set_cookie_params(['lifetime'=>0,'path'=>fc_auth_base(),'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
    if(!$headersSet && !headers_sent()) {
        header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');
        header('X-Frame-Options: SAMEORIGIN');$headersSet=true;
    }
}
function fc_auth_csrf() {
    fc_auth_start();return $_SESSION['fc_csrf']=$_SESSION['fc_csrf']??bin2hex(random_bytes(32));
}
function fc_auth_user() {
    fc_auth_start();$user=$_SESSION['fc_user']??null;
    if(!$user)return null;
    $cfg=fc_auth_config();$account=$cfg['users'][$user['email']]??null;$now=time();
    if(!$account || !hash_equals(hash('sha256',$account['password_hash']),$user['fingerprint']??'') || $now-($user['last_seen']??0)>1800 || $now-($user['signed_in']??0)>28800) {
        unset($_SESSION['fc_user']);return null;
    }
    $_SESSION['fc_user']['last_seen']=$now;return $user;
}
function fc_auth_json_request() {
    $script=basename($_SERVER['SCRIPT_NAME']??'');
    return in_array($script,['save-receipt.php','save-payslip.php','update-receipt.php','update-payslip.php','delete-receipt.php','delete-payslip.php','record-payment.php','search-clients.php','search-outstanding.php','get-invoice-info.php','get-payslip-info.php'],true)
        || stripos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest';
}
function fc_auth_failure($status,$message) {
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>$message]);exit;
}
function fc_require_login() {
    if(PHP_SAPI==='cli')return;
    fc_auth_start();
    if(!fc_auth_user()) {
        if(fc_auth_json_request() || !in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true))fc_auth_failure(401,'Please sign in to continue.');
        $next=basename($_SERVER['SCRIPT_NAME']??'index.php');
        if(!empty($_SERVER['QUERY_STRING']))$next.='?'.$_SERVER['QUERY_STRING'];
        header('Location: '.fc_auth_base().'login.php?next='.rawurlencode($next),true,302);exit;
    }
    if(!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)) {
        $origin=$_SERVER['HTTP_ORIGIN']??($_SERVER['HTTP_REFERER']??'');
        $expectedHost=strtolower($_SERVER['HTTP_HOST']??'');
        if($origin!=='') {
            $host=strtolower((string)parse_url($origin,PHP_URL_HOST));$port=parse_url($origin,PHP_URL_PORT);
            $actual=$host.($port?':'.$port:'');
            $scheme=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'?'https':'http';
            if($actual!==$expectedHost || strtolower((string)parse_url($origin,PHP_URL_SCHEME))!==$scheme)fc_auth_failure(403,'Cross-site requests are not allowed.');
        }
    }
}
function fc_auth_next($value) {
    // Only known application PHP pages; never accept hosts, paths or control characters.
    if(!is_string($value) || preg_match('/[\x00-\x20\x7f\\\\]/',$value))return 'index.php';
    $parts=explode('?',$value,2);$page=$parts[0];
    if(!preg_match('/^[a-z][a-z0-9-]*\.php$/D',$page) || !is_file(__DIR__.'/'.$page) || in_array($page,['auth.php','login.php','logout.php','db_config.php','config.php','functions.php'],true))return 'index.php';
    return $value;
}
function fc_auth_attempt($email,$password) {
    fc_auth_start();$cfg=fc_auth_config();$email=strtolower(trim($email));
    $dir=__DIR__.'/auth-private';
    if(!is_dir($dir) || !is_writable($dir))throw new RuntimeException('Sign-in is not configured. Contact your administrator.');
    $file=fopen($dir.'/attempts.json','c+');
    if(!$file || !flock($file,LOCK_EX))throw new RuntimeException('Sign-in is temporarily unavailable.');
    try {
        $buckets=json_decode(stream_get_contents($file),true)?:[];$now=time();
        foreach($buckets as $key=>$bucket)if($now-$bucket['start']>=900)unset($buckets[$key]);
        // Both IP and account counters survive cookie/session resets.
        $keys=[hash('sha256','ip:'.($_SERVER['REMOTE_ADDR']??'local')),hash('sha256','email:'.$email)];
        foreach($keys as $key)if(($buckets[$key]['count']??0)>=10)return 'Too many attempts. Try again in 15 minutes.';
        $account=$cfg['users'][$email]??null;
        $allowed=filter_var($email,FILTER_VALIDATE_EMAIL) && substr(strrchr($email,'@')?:'',1)===$cfg['domain'];
        $hash=$account['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid=password_verify($password,$hash);
        if(!$allowed || !$account || !$valid) {
            foreach($keys as $key){if(!isset($buckets[$key]))$buckets[$key]=['start'=>$now,'count'=>0];$buckets[$key]['count']++;}
            $error='Email or password is incorrect.';
        } else {
            // Keep the IP counter so one valid account cannot reset attacks on other accounts.
            unset($buckets[$keys[1]]);session_regenerate_id(true);
            $_SESSION=[];$_SESSION['fc_user']=['email'=>$email,'name'=>$account['name']??$email,'fingerprint'=>hash('sha256',$hash),'signed_in'=>$now,'last_seen'=>$now];
            $error=null;
        }
        rewind($file);ftruncate($file,0);fwrite($file,json_encode($buckets));fflush($file);return $error;
    } finally {flock($file,LOCK_UN);fclose($file);}
}
