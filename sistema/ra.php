<?php
// Compatibilidad con enlaces antiguos: mantiene la edición individual de RA.
require_once __DIR__.'/layout.php';
$u=require_login();$pdo=db();
$studentId=(int)($_GET['student_id']??$_POST['student_id']??0);$subjectId=(int)($_GET['subject_id']??$_POST['subject_id']??0);
$st=$pdo->prepare("SELECT st.id,st.names,st.last_names,st.section_id,su.name subject_name,su.type FROM students st JOIN subjects su ON su.id=? WHERE st.id=?");$st->execute([$subjectId,$studentId]);$info=$st->fetch();
if(!$info||$info['type']!=='technical'){http_response_code(404);exit('Módulo técnico no encontrado');}
if($u['role']==='teacher'){$chk=$pdo->prepare('SELECT COUNT(*) FROM teacher_assignments WHERE user_id=? AND section_id=? AND subject_id=?');$chk->execute([$u['id'],$info['section_id'],$subjectId]);if(!$chk->fetchColumn()){http_response_code(403);exit('No autorizado');}}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$pdo->beginTransaction();
 try{$up=$pdo->prepare("INSERT INTO ra_scores(student_id,learning_result_id,score,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP");foreach($_POST['score']??[] as $rid=>$raw){$score=$raw===''?null:(float)$raw;if($score!==null&&($score<0||$score>100))throw new Exception('RA fuera de rango');$up->execute([$studentId,(int)$rid,$score,$u['id']]);}$pdo->commit();flash('success','Resultados de aprendizaje guardados.');}catch(Throwable $e){$pdo->rollBack();flash('error','No se pudieron guardar los RA.');}
 header('Location: ra.php?student_id='.$studentId.'&subject_id='.$subjectId);exit;
}
$st=$pdo->prepare("SELECT lr.*,rs.score FROM learning_results lr LEFT JOIN ra_scores rs ON rs.learning_result_id=lr.id AND rs.student_id=? WHERE lr.subject_id=? ORDER BY lr.sort_order,lr.ra_code");$st->execute([$studentId,$subjectId]);$ras=$st->fetchAll();app_header('Resultados de aprendizaje'); ?>
<div class="grade-mode-switch no-print"><a href="grades.php">📘 Áreas académicas <small>P1–P4</small></a><a class="active" href="modules.php?section_id=<?=$info['section_id']?>&subject_id=<?=$subjectId?>">🧩 Módulos técnicos <small>RA</small></a></div>
<div class="panel"><div class="section-heading"><div><span class="mini-kicker">EDICIÓN INDIVIDUAL</span><h2><?=h($info['subject_name'])?></h2><p><b>Estudiante:</b> <?=h($info['last_names'].', '.$info['names'])?></p></div><span class="evaluation-badge technical">RA</span></div><?php if(!$ras):?><div class="empty">Este módulo todavía no tiene RA configurados.</div><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="student_id" value="<?=$studentId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><div class="table-wrap"><table class="sys-table"><thead><tr><th>RA</th><th>Descripción</th><th>Calificación</th></tr></thead><tbody><?php foreach($ras as $r):?><tr><td><b><?=h($r['ra_code'])?></b></td><td><?=h($r['title'])?></td><td><input type="number" min="0" max="100" step="0.01" name="score[<?=$r['id']?>]" value="<?=h($r['score'])?>"></td></tr><?php endforeach;?></tbody></table></div><div class="actions"><button class="button">Guardar RA</button><a class="button gray" href="modules.php?section_id=<?=$info['section_id']?>&subject_id=<?=$subjectId?>">Volver al módulo</a></div></form><?php endif;?></div>
<?php app_footer(); ?>
