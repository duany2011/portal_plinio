<?php
require_once __DIR__.'/layout.php';
$u=require_login();
$pdo=db();
$isTeacher=$u['role']==='teacher';

if($isTeacher){
  $st=$pdo->prepare("SELECT DISTINCT se.id,CONCAT(se.grade_name,' ',se.section_name,' · ',ay.name) label
    FROM teacher_assignments ta
    JOIN sections se ON se.id=ta.section_id
    JOIN academic_years ay ON ay.id=se.academic_year_id
    JOIN subjects su ON su.id=ta.subject_id
    WHERE ta.user_id=? AND su.type='technical'
    ORDER BY ay.active DESC,se.grade_name,se.section_name");
  $st->execute([$u['id']]);$sections=$st->fetchAll();
}else{
  $sections=$pdo->query("SELECT DISTINCT se.id,CONCAT(se.grade_name,' ',se.section_name,' · ',ay.name) label FROM sections se JOIN academic_years ay ON ay.id=se.academic_year_id ORDER BY ay.active DESC,se.grade_name,se.section_name")->fetchAll();
}

$sectionId=(int)($_GET['section_id']??$_POST['section_id']??0);
$subjectId=(int)($_GET['subject_id']??$_POST['subject_id']??0);
$modules=[];
if($sectionId){
  if($isTeacher){
    $st=$pdo->prepare("SELECT DISTINCT su.id,su.name FROM teacher_assignments ta JOIN subjects su ON su.id=ta.subject_id WHERE ta.user_id=? AND ta.section_id=? AND su.active=1 AND su.type='technical' ORDER BY su.name");
    $st->execute([$u['id'],$sectionId]);$modules=$st->fetchAll();
  }else{
    $modules=$pdo->query("SELECT id,name FROM subjects WHERE active=1 AND type='technical' ORDER BY name")->fetchAll();
  }
}

$allowed=true;
if($sectionId&&$subjectId){
  $st=$pdo->prepare("SELECT COUNT(*) FROM subjects WHERE id=? AND type='technical' AND active=1");$st->execute([$subjectId]);$allowed=(bool)$st->fetchColumn();
  if($allowed&&$isTeacher){$st=$pdo->prepare('SELECT COUNT(*) FROM teacher_assignments WHERE user_id=? AND section_id=? AND subject_id=?');$st->execute([$u['id'],$sectionId,$subjectId]);$allowed=(bool)$st->fetchColumn();}
}

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='save'){
  verify_csrf();if(!$allowed){http_response_code(403);exit('No autorizado');}
  $pdo->beginTransaction();
  try{
    $up=$pdo->prepare("INSERT INTO ra_scores(student_id,learning_result_id,score,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP");
    foreach($_POST['student_ids']??[] as $sid){
      $sid=(int)$sid;
      foreach($_POST['score'][$sid]??[] as $rid=>$raw){
        $score=$raw===''?null:(float)$raw;
        if($score!==null&&($score<0||$score>100)) throw new Exception('RA fuera de rango');
        $up->execute([$sid,(int)$rid,$score,$u['id']]);
      }
    }
    $pdo->commit();flash('success','Resultados de aprendizaje guardados.');
  }catch(Throwable $e){$pdo->rollBack();flash('error','No se pudieron guardar los RA. Verifique valores entre 0 y 100.');}
  header('Location: modules.php?section_id='.$sectionId.'&subject_id='.$subjectId);exit;
}

$students=[];$ras=[];$scores=[];$module=null;$sectionInfo=null;
if($sectionId){$st=$pdo->prepare("SELECT se.*,ay.name year_name FROM sections se JOIN academic_years ay ON ay.id=se.academic_year_id WHERE se.id=?");$st->execute([$sectionId]);$sectionInfo=$st->fetch();}
if($sectionId&&$subjectId&&$allowed){
  $st=$pdo->prepare("SELECT * FROM subjects WHERE id=? AND type='technical'");$st->execute([$subjectId]);$module=$st->fetch();
  $st=$pdo->prepare('SELECT id,order_no,names,last_names,sigerd_id FROM students WHERE section_id=? AND active=1 ORDER BY order_no,last_names,names');$st->execute([$sectionId]);$students=$st->fetchAll();
  $st=$pdo->prepare('SELECT * FROM learning_results WHERE subject_id=? ORDER BY sort_order,ra_code');$st->execute([$subjectId]);$ras=$st->fetchAll();
  if($ras){$st=$pdo->prepare('SELECT rs.student_id,rs.learning_result_id,rs.score FROM ra_scores rs JOIN learning_results lr ON lr.id=rs.learning_result_id JOIN students st ON st.id=rs.student_id WHERE st.section_id=? AND lr.subject_id=?');$st->execute([$sectionId,$subjectId]);foreach($st as $r)$scores[$r['student_id']][$r['learning_result_id']]=$r['score'];}
}

app_header('Módulos técnicos · RA'); ?>
<div class="grade-mode-switch no-print">
  <a href="grades.php">📘 Áreas académicas <small>P1 · P2 · P3 · P4</small></a>
  <a class="active" href="modules.php">🧩 Módulos técnicos <small>RA1 · RA2 · RA3…</small></a>
</div>
<div class="panel no-print">
  <div class="section-heading"><div><span class="mini-kicker">FORMACIÓN TÉCNICO PROFESIONAL</span><h2>Resultados de Aprendizaje</h2><p>Los módulos técnicos se califican por RA. Cada módulo muestra únicamente los resultados que tiene configurados.</p></div><span class="evaluation-badge technical">RA</span></div>
  <form method="get" class="filters">
    <label>Grado / sección<select name="section_id" onchange="this.form.submit()"><option value="">Seleccione</option><?php foreach($sections as $s):?><option value="<?=$s['id']?>" <?=$sectionId==$s['id']?'selected':''?>><?=h($s['label'])?></option><?php endforeach;?></select></label>
    <label>Módulo técnico<select name="subject_id" onchange="this.form.submit()"><option value="">Seleccione</option><?php foreach($modules as $s):?><option value="<?=$s['id']?>" <?=$subjectId==$s['id']?'selected':''?>><?=h($s['name'])?></option><?php endforeach;?></select></label>
  </form>
</div>
<?php if($sectionId&&$subjectId&&!$allowed):?><div class="alert error">No tiene permiso para registrar este módulo en esta sección.</div><?php endif;?>
<?php if($module&&$allowed&&!$ras):?><div class="panel empty"><b><?=h($module['name'])?></b> todavía no tiene Resultados de Aprendizaje configurados.<br><?php if(role_in(['admin'])):?><a href="catalogs.php">Agregar RA desde Configuración →</a><?php else:?>Solicite al administrador que configure sus RA.<?php endif;?></div>
<?php elseif($students&&$ras&&$allowed):?>
<form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="section_id" value="<?=$sectionId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>">
<div class="panel">
  <div class="record-header"><div><span class="mini-kicker"><?=h(($sectionInfo['grade_name']??'').' '.($sectionInfo['section_name']??''))?> · <?=count($ras)?> RA</span><h2><?=h($module['name'])?></h2><p>Registre cada Resultado de Aprendizaje de 0 a 100. Desplace la tabla horizontalmente cuando el módulo tenga muchos RA.</p></div><div class="record-summary technical"><b><?=count($ras)?></b><span>resultados</span></div></div>
  <div class="ra-legend no-print"><?php foreach($ras as $r):?><span><b><?=h($r['ra_code'])?></b><?=h($r['title'])?></span><?php endforeach;?></div>
  <div class="table-wrap ra-table-wrap"><table class="sys-table ra-register"><thead><tr><th class="sticky-col no-col">No.</th><th class="sticky-col student-col">Estudiante</th><?php foreach($ras as $r):?><th class="center-cell" title="<?=h($r['title'])?>"><?=h($r['ra_code'])?></th><?php endforeach;?><th class="center-cell">Promedio RA</th></tr></thead><tbody>
  <?php foreach($students as $s):?><tr><td class="sticky-col no-col"><?=h($s['order_no'])?><input type="hidden" name="student_ids[]" value="<?=$s['id']?>"></td><td class="sticky-col student-col"><b><?=h($s['last_names'].', '.$s['names'])?></b><br><small><?=h($s['sigerd_id'])?></small></td><?php $vals=[];foreach($ras as $r):$v=$scores[$s['id']][$r['id']]??'';if($v!==''&&$v!==null)$vals[]=(float)$v;?><td><input class="ra-score-input" type="number" min="0" max="100" step="0.01" name="score[<?=$s['id']?>][<?=$r['id']?>]" value="<?=h($v)?>" aria-label="<?=h($r['ra_code'].' '.$s['names'].' '.$s['last_names'])?>"></td><?php endforeach;?><td class="center-cell ra-avg-cell"><?=$vals?number_format(array_sum($vals)/count($vals),1):'—'?></td></tr><?php endforeach;?>
  </tbody></table></div>
  <div class="actions sticky-actions"><button class="button">Guardar resultados RA</button><a class="button gray" href="reports.php">Ver boletines</a></div>
</div></form>
<script>document.querySelectorAll('.ra-score-input').forEach(i=>i.addEventListener('input',()=>{const r=i.closest('tr'),v=[...r.querySelectorAll('.ra-score-input')].map(x=>parseFloat(x.value)).filter(Number.isFinite);r.querySelector('.ra-avg-cell').textContent=v.length?(v.reduce((a,b)=>a+b,0)/v.length).toFixed(1):'—'}));</script>
<?php elseif($sectionId&&$subjectId&&$allowed):?><div class="panel empty">No hay estudiantes activos en esta sección.</div><?php else:?><div class="panel empty"><b>Seleccione una sección y un módulo técnico.</b><br>Los módulos no utilizan P1–P4; se registran mediante RA.</div><?php endif;?>
<?php app_footer(); ?>
