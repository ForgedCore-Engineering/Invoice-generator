<?php
require_once __DIR__.'/auth.php';fc_auth_start();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit;}
if(!hash_equals(fc_auth_csrf(),(string)($_POST['csrf']??'')))fc_auth_failure(403,'Session expired.');
$_SESSION=[];
$cookie=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>$cookie['path'],'secure'=>$cookie['secure'],'httponly'=>true,'samesite'=>'Lax']);
session_destroy();header('Location: '.fc_auth_base().'login.php',true,303);exit;
