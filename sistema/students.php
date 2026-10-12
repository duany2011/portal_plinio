<?php
require_once __DIR__.'/layout.php'; $u=require_login(); $pdo=db();
$canEdit=role_in(['admin','director','coordinator']);
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!$canEdit){http_response_code(403);exit('No autorizado');} verify_csrf(); $action=$_POST['action']??'';
  if($action==='save'){
    $id=(int)($_POST['id']??0); $order=($_POST['order_no']??'')!==''?(int)$_POST['order_no']:null;
    $section=(int)($_POST['section_id']??0); $names=trim($_POST['names']??''); $last=trim($_POST['last_names']??'');
    $sig=trim($_POST['sigerd_id']??''); $phone=trim($_POST['phone']??''); $obs=trim($_POST['observations']??'');
    if(!$section||!$names||!$last){flash('error','Complete sección, nombres y apellidos.'); header('Location: students.php');exit;}
    try{
      if($id){$st=$pdo->prepare('UPDATE students SET section_id=?,order_no=?,sigerd_id=?,names=?,last_names=?,phone=?,observations=? WHERE id=?');$st->execute([$section,$order,$sig?:null,$names,$last,$phone?:null,$obs?:null,$id]);}
      else{$st=$pdo->prepare('INSERT INTO students(section_id,order_no,sigerd_id,names,last_names,phone,observations) VALUES(?,?,?,?,?,?,?)');$st->execute([$section,$order,$sig?:null,$names,$last,$phone?:null,$obs?:null]);}
      flash('success','Estudiante guardado.');
    }catch(Throwable $e){flash('error','No se pudo guardar. Verifique que el ID SIGERD no esté repetido.');}
    header('Location: students.php');exit;
  }
  if($action==='delete'){$id=(int)$_POST['id'];$pdo->prepare('UPDATE students SET active=0 WHERE id=?')->execute([$id]);flash('success','Estudiante desactivado.');header('Location: students.php');exit;}
}
$edit=null;if(isset($_GET['edit'])&&$canEdit){$st=$pdo->prepare('SELECT * FROM students WHERE id=?');$st->execute([(int)$_GET['edit']]);$edit=$st->fetch();}
$sections=$pdo->query("SELECT s.id, CONCAT(s.grade_name,' ',s.section_name,' · ',ay.name) label FROM sections s JOIN academic_years ay ON ay.id=s.academic_year_id ORDER BY ay.active DESC,s.grade_name,s.section_name")->fetchAll();
$q=trim($_GET['q']??'');$sql="SELECT st.*,CONCAT(se.grade_name,' ',se.section_name) section_label,ay.name year_name FROM students st JOIN sections se ON se.id=st.section_id JOIN academic_years ay ON ay.id=se.academic_year_id WHERE st.active=1";$args=[];
if($u['role']==='teacher'){$sql.=" AND st.section_id IN (SELECT section_id FROM teacher_assignments WHERE user_id=?)";$args[]=$u['id'];}
if($q!==''){$sql.=" AND (st.names LIKE ? OR st.last_names LIKE ? OR st.sigerd_id LIKE ? OR se.grade_name LIKE ? OR se.section_name LIKE ?)";$like="%$q%";for($i=0;$i<5;$i++)$args[]=$like;} $sql.=' ORDER BY ay.active DESC,se.grade_name,se.section_name,st.order_no,st.last_names';$st=$pdo->prepare($sql);$st->execute($args);$students=$st->fetchAll();
app_header('Estudiantes'); ?>
<div class="panel no-print"><form method="get" class="filters"><label>Buscar<input name="q" value="<?=h($q)?>" placeholder="Nombre, ID, grado o sección"></label><button class="button blue">Buscar</button><?php if($q):?><a class="button gray" href="students.php">Limpiar</a><?php endif;?></form></div>
<?php if($canEdit):?><div class="panel"><h2><?=$edit?'Editar estudiante':'Nuevo estudiante'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=h($edit['id']??'')?>"><div class="form-grid"><label class="c3">Número de orden<input type="number" name="order_no" min="1" value="<?=h($edit['order_no']??'')?>"></label><label class="c3">Sección<select name="section_id" required><option value="">Seleccione</option><?php foreach($sections as $s):?><option value="<?=$s['id']?>" <?=($edit&&$edit['section_id']==$s['id'])?'selected':''?>><?=h($s['label'])?></option><?php endforeach;?></select></label><label class="c3">ID SIGERD<input name="sigerd_id" value="<?=h($edit['sigerd_id']??'')?>"></label><label class="c3">Teléfono<input name="phone" value="<?=h($edit['phone']??'')?>"></label><label class="c6">Nombre(s)<input name="names" required value="<?=h($edit['names']??'')?>"></label><label class="c6">Apellido(s)<input name="last_names" required value="<?=h($edit['last_names']??'')?>"></label><label class="c12">Observaciones<textarea name="observations"><?=h($edit['observations']??'')?></textarea></label></div><div class="actions"><button class="button">Guardar</button><?php if($edit):?><a class="button gray" href="students.php">Cancelar</a><?php endif;?></div></form></div><?php endif;?>
<div class="panel"><h2>Listado de estudiantes</h2><div class="table-wrap"><table class="sys-table"><thead><tr><th>No.</th><th>Estudiante</th><th>Año escolar</th><th>Grado/Sección</th><th>ID SIGERD</th><th>Acciones</th></tr></thead><tbody><?php foreach($students as $s):?><tr><td><?=h($s['order_no'])?></td><td><b><?=h($s['last_names'].', '.$s['names'])?></b></td><td><?=h($s['year_name'])?></td><td><span class="badge"><?=h($s['section_label'])?></span></td><td><?=h($s['sigerd_id'])?></td><td><a class="button gray" href="reports.php?student_id=<?=$s['id']?>">Boletín</a><?php if($canEdit):?> <a class="button blue" href="students.php?edit=<?=$s['id']?>">Editar</a> <form method="post" style="display:inline" onsubmit="return confirm('¿Desactivar este estudiante?')"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="button danger">Desactivar</button></form><?php endif;?></td></tr><?php endforeach;?><?php if(!$students):?><tr><td colspan="6" class="empty">No hay estudiantes.</td></tr><?php endif;?></tbody></table></div></div>
<?php app_footer(); ?>
