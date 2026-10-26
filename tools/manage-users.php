<?php
/** CLI only. Input JSON on stdin; plaintext passwords are never saved or printed. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$input=json_decode(stream_get_contents(STDIN),true);
if(!is_array($input) || empty($input['users']))exit("Supply JSON with domain and users on stdin. See AUTH_SETUP.md.\n");
$domain=strtolower(trim($input['domain']??''));
if($domain!=='forgedcoreengineering.com')exit("Only the approved company domain is allowed.\n");
$users=[];
foreach($input['users'] as $u){
 $email=strtolower(trim($u['email']??''));$password=(string)($u['password']??'');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL) || substr(strrchr($email,'@')?:'',1)!==$domain || strlen($password)<8 || strlen($password)>72 || isset($users[$email]))exit("Invalid/duplicate account or password length (8–72 bytes). No changes saved.\n");
 $users[$email]=['name'=>(string)($u['name']??$email),'password_hash'=>password_hash($password,PASSWORD_DEFAULT)];
}
$dir=dirname(__DIR__).'/auth-private';if(!is_dir($dir))mkdir($dir,0700,true);
$target=$dir.'/users.json';$temp=tempnam($dir,'users-');
if(file_put_contents($temp,json_encode(['domain'=>$domain,'users'=>$users],JSON_PRETTY_PRINT),LOCK_EX)===false)exit("Could not save accounts.\n");
chmod($temp,0600);
if(!rename($temp,$target)){unlink($temp);exit("Could not replace accounts.\n");}
echo count($users)." approved accounts provisioned. Passwords were hashed.\n";
