<?php
require_once __DIR__.'/layout.php';
$u=require_login();
$pdo=db();
$isTeacher=$u['role']==='teacher';

// Solo áreas curriculares: P1, P2, P3 y P4.
if($isTeacher){
  $st=$pdo->prepare("SELECT DISTINCT se.id,CONCAT(se.grade_name,' ',se.section_name,' · ',ay.name) label
    FROM teacher_assignments ta
    JOIN sections se ON se.id=ta.section_id
    JOIN academic_years ay ON ay.id=se.academic_year_id
    JOIN subjects su ON su.id=ta.subject_id
    WHERE ta.user_id=? AND su.type='curricular'
    ORDER BY ay.active DESC,se.grade_name,se.section_name");
  $st->execute([$u['id']]);
  $sections=$st->fetchAll();
}else{
  $sections=$pdo->query("SELECT DISTINCT se.id,CONCAT(se.grade_name,' ',se.section_name,' · ',ay.name) label
    FROM sections se JOIN academic_years ay ON ay.id=se.academic_year_id
    ORDER BY ay.active DESC,se.grade_name,se.section_name")->fetchAll();
}

$sectionId=(int)($_GET['section_id']??$_POST['section_id']??0);
$subjectId=(int)($_GET['subject_id']??$_POST['subject_id']??0);
$subjects=[];
if($sectionId){
  if($isTeacher){
    $st=$pdo->prepare("SELECT DISTINCT su.id,su.name
      FROM teacher_assignments ta JOIN subjects su ON su.id=ta.subject_id
      WHERE ta.user_id=? AND ta.section_id=? AND su.active=1 AND su.type='curricular'
      ORDER BY su.name");
    $st->execute([$u['id'],$sectionId]);
    $subjects=$st->fetchAll();
  }else{
    $subjects=$pdo->query("SELECT id,name FROM subjects WHERE active=1 AND type='curricular' ORDER BY name")->fetchAll();
  }
}

$allowed=true;
if($sectionId&&$subjectId){
  $st=$pdo->prepare("SELECT COUNT(*) FROM subjects WHERE id=? AND type='curricular' AND active=1");
  $st->execute([$subjectId]);
  $allowed=(bool)$st->fetchColumn();
  if($allowed&&$isTeacher){
    $st=$pdo->prepare('SELECT COUNT(*) FROM teacher_assignments WHERE user_id=? AND section_id=? AND subject_id=?');
    $st->execute([$u['id'],$sectionId,$subjectId]);
    $allowed=(bool)$st->fetchColumn();
  }
}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='save'){
  verify_csrf();
  if(!$allowed){http_response_code(403);exit('No autorizado');}
  $studentIds=$_POST['student_ids']??[];
  $pdo->beginTransaction();
  try{
    $up=$pdo->prepare("INSERT INTO grades(student_id,subject_id,period,score,updated_by) VALUES(?,?,?,?,?)
      ON DUPLICATE KEY UPDATE score=VALUES(score),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP");
    foreach($studentIds as $sid){
      $sid=(int)$sid;
      foreach(['P1','P2','P3','P4'] as $p){
        $raw=$_POST['score'][$sid][$p]??'';
        $score=$raw===''?null:(float)$raw;
        if($score!==null&&($score<0||$score>100)) throw new Exception('Nota fuera de rango');
        $up->execute([$sid,$subjectId,$p,$score,$u['id']]);
      }
    }
    $pdo->commit();
    flash('success','Calificaciones académicas guardadas.');
  }catch(Throwable $e){
    $pdo->rollBack();
    flash('error','No se pudieron guardar las calificaciones. Verifique que estén entre 0 y 100.');
  }
  header('Location: grades.php?section_id='.$sectionId.'&subject_id='.$subjectId);exit;
}

$students=[];$existing=[];$subject=null;$sectionInfo=null;
if($sectionId){
  $st=$pdo->prepare("SELECT se.*,ay.name year_name FROM sections se JOIN academic_years ay ON ay.id=se.academic_year_id WHERE se.id=?");
  $st->execute([$sectionId]);$sectionInfo=$st->fetch();
}
if($sectionId&&$subjectId&&$allowed){
  $st=$pdo->prepare("SELECT * FROM subjects WHERE id=? AND type='curricular'");$st->execute([$subjectId]);$subject=$st->fetch();
  $st=$pdo->prepare('SELECT id,order_no,names,last_names,sigerd_id FROM students WHERE section_id=? AND active=1 ORDER BY order_no,last_names,names');$st->execute([$sectionId]);$students=$st->fetchAll();
  $st=$pdo->prepare('SELECT g.* FROM grades g JOIN students st ON st.id=g.student_id WHERE st.section_id=? AND g.subject_id=?');$st->execute([$sectionId,$subjectId]);
  foreach($st as $g)$existing[$g['student_id']][$g['period']]=$g['score'];
}

app_header('Calificaciones académicas'); ?>
<div class="grade-mode-switch no-print">
  <a class="active" href="grades.php">📘 Áreas académicas <small>P1 · P2 · P3 · P4</small></a>
  <a href="modules.php">🧩 Módulos técnicos <small>RA1 · RA2 · RA3…</small></a>
</div>
<div class="panel no-print">
  <div class="section-heading"><div><span class="mini-kicker">FORMACIÓN GENERAL</span><h2>Registro por períodos</h2><p>Las áreas curriculares se califican mediante P1, P2, P3 y P4.</p></div><span class="evaluation-badge">P1–P4</span></div>
  <form method="get" class="filters">
    <label>Grado / sección<select name="section_id" onchange="this.form.submit()"><option value="">Seleccione</option><?php foreach($sections as $s):?><option value="<?=$s['id']?>" <?=$sectionId==$s['id']?'selected':''?>><?=h($s['label'])?></option><?php endforeach;?></select></label>
    <label>Área curricular<select name="subject_id" onchange="this.form.submit()"><option value="">Seleccione</option><?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=$subjectId==$s['id']?'selected':''?>><?=h($s['name'])?></option><?php endforeach;?></select></label>
  </form>
</div>
<?php if($sectionId&&$subjectId&&!$allowed):?><div class="alert error">No tiene permiso para registrar esta asignatura en esta sección.</div><?php endif;?>
<?php if($students&&$allowed):?>
<form method="post">
  <input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="section_id" value="<?=$sectionId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>">
  <div class="panel">
    <div class="record-header"><div><span class="mini-kicker"><?=h(($sectionInfo['grade_name']??'').' '.($sectionInfo['section_name']??''))?></span><h2><?=h($subject['name']??'')?></h2><p>Introduzca valores de 0 a 100. El promedio mostrado se calcula automáticamente con las notas registradas.</p></div><div class="record-summary"><b><?=count($students)?></b><span>estudiantes</span></div></div>
    <div class="table-wrap"><table class="sys-table grade-register"><thead><tr><th>No.</th><th>Estudiante</th><th class="center-cell">P1</th><th class="center-cell">P2</th><th class="center-cell">P3</th><th class="center-cell">P4</th><th class="center-cell">Promedio</th></tr></thead><tbody>
      <?php foreach($students as $s):?><tr><td><?=h($s['order_no'])?><input type="hidden" name="student_ids[]" value="<?=$s['id']?>"></td><td><b><?=h($s['last_names'].', '.$s['names'])?></b><br><small><?=h($s['sigerd_id'])?></small></td><?php $vals=[];foreach(['P1','P2','P3','P4'] as $p):$v=$existing[$s['id']][$p]??'';if($v!==''&&$v!==null)$vals[]=(float)$v;?><td><input class="grade-input" type="number" min="0" max="100" step="0.01" name="score[<?=$s['id']?>][<?=$p?>]" value="<?=h($v)?>" aria-label="<?=h($p.' '.$s['names'].' '.$s['last_names'])?>"></td><?php endforeach;?><td class="center-cell avg-cell"><?=$vals?number_format(array_sum($vals)/count($vals),1):'—'?></td></tr><?php endforeach;?>
    </tbody></table></div>
    <div class="actions sticky-actions"><button class="button">Guardar P1–P4</button><a class="button gray" href="reports.php">Ver boletines</a></div>
  </div>
</form>
<script>document.querySelectorAll('.grade-input').forEach(i=>i.addEventListener('input',()=>{const r=i.closest('tr'),v=[...r.querySelectorAll('.grade-input')].map(x=>parseFloat(x.value)).filter(Number.isFinite);r.querySelector('.avg-cell').textContent=v.length?(v.reduce((a,b)=>a+b,0)/v.length).toFixed(1):'—'}));</script>
<?php elseif($sectionId&&$subjectId&&$allowed):?><div class="panel empty">No hay estudiantes activos en esta sección.</div><?php else:?><div class="panel empty"><b>Seleccione una sección y un área curricular.</b><br>Para módulos técnicos utilice la pestaña <b>Módulos técnicos · RA</b>.</div><?php endif;?>
<?php app_footer(); ?>
