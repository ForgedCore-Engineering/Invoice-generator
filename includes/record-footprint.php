<?php
$origin=fc_activity_origin($pdo,$footprintEntity,$footprintRecordId);
?>
<div style="display:flex;gap:8px 18px;flex-wrap:wrap;align-items:center;padding:12px 16px;margin-bottom:16px;border:1px solid var(--br);border-radius:8px;font-size:12px;color:var(--txt2);overflow-wrap:anywhere">
<span>Entered by: <strong><?=htmlspecialchars($origin?($origin['actor_email']??'System / import'):'Not recorded (historical/imported)',ENT_QUOTES,'UTF-8')?></strong><?php if($origin):?> · <?=htmlspecialchars($origin['created_at'],ENT_QUOTES,'UTF-8')?><?php endif?></span>
<a href="footprints.php?<?=htmlspecialchars(http_build_query(['entity'=>$footprintEntity,'record'=>$footprintRecordId]),ENT_QUOTES,'UTF-8')?>" style="color:var(--acc)">View footprints</a></div>
