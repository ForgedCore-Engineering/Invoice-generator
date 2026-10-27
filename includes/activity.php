<?php
/** Server-side data footprints; business write and footprint commit together. */
function fc_activity_schema(PDO $pdo) {
    $pdo->exec("SET time_zone = '+00:00'");
    $pdo->exec(file_get_contents(dirname(__DIR__).'/activity_schema.sql'));
}
function fc_activity_email() {
    $user=function_exists('fc_auth_user')?fc_auth_user():null;
    if(!$user && PHP_SAPI!=='cli')throw new RuntimeException('Your session expired. Sign in before saving.');
    return $user['email']??null;
}
function fc_activity_log(PDO $pdo,$entity,$id,$action,$before,$after) {
    if(!$pdo->inTransaction())throw new LogicException('A footprint must be written in the data transaction.');
    $email=fc_activity_email();
    $pdo->prepare('INSERT INTO app_activity(entity,record_id,action,actor_email,before_json,after_json) VALUES (?,?,?,?,?,?)')->execute([
        $entity,$id,$action,$email,$before===null?null:json_encode($before,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$after===null?null:json_encode($after,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)
    ]);
}
function fc_activity_change(PDO $pdo,$entity,$id,$action,callable $write) {
    $tables=['receipt'=>'clients','payslip'=>'payslips'];
    if(!isset($tables[$entity]) || !in_array($action,['create','update','payment','delete'],true))throw new InvalidArgumentException('Invalid footprint operation.');
    $table=$tables[$entity];$pdo->beginTransaction();
    try {
        $before=null;
        if($action!=='create') {
            $s=$pdo->prepare("SELECT * FROM $table WHERE id=? FOR UPDATE");$s->execute([$id]);$before=$s->fetch();
            if(!$before)throw new OutOfBoundsException('Record not found.');
        }
        $result=$write($before);
        if($action==='create')$id=(int)$result;
        $s=$pdo->prepare("SELECT * FROM $table WHERE id=?");$s->execute([$id]);$after=$s->fetch()?:null;
        if($action==='create' && !$after)throw new RuntimeException('Created record could not be verified.');
        if($before!==$after)fc_activity_log($pdo,$entity,$id,$action,$before,$after);
        $pdo->commit();return $result;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function fc_activity_origin(PDO $pdo,$entity,$id) {
    $s=$pdo->prepare("SELECT actor_email,created_at FROM app_activity WHERE entity=? AND record_id=? AND action='create' ORDER BY id LIMIT 1");$s->execute([$entity,$id]);return $s->fetch()?:null;
}
