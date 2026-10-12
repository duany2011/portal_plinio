<?php
require_once __DIR__.'/db.php';
$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $pdo=db();
  $sql=file_get_contents(__DIR__.'/schema.sql');
  foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $stmt){$pdo->exec($stmt);}
  $count=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
  if($count===0){$username=trim($_POST['username']??'admin');$name=trim($_POST['full_name']??'Administrador');$pass=$_POST['password']??'';if(strlen($pass)<8)throw new Exception('La contraseña debe tener al menos 8 caracteres.');$st=$pdo->prepare("INSERT INTO users(username,password_hash,full_name,role,active) VALUES(?,?,?,'admin',1)");$st->execute([$username,password_hash($pass,PASSWORD_DEFAULT),$name]);}
  $message='Instalación completada. Ya puede iniciar sesión.';
 }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar sistema</title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="sistema.css"></head><body><main class="sys-container"><div class="panel" style="max-width:700px;margin:3rem auto"><div style="text-align:center"><img src="../assets/logo.jpg" style="width:90px;margin:auto;border-radius:50%"><h1>Instalación del sistema académico</h1></div><?php if($message):?><div class="alert success"><?=htmlspecialchars($message)?></div><p><a class="button" href="../docentes-login.php">Ir al inicio de sesión</a></p><?php else:?><?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?><p>Antes de continuar, edite <code>sistema/config.php</code> con los datos de la base MySQL creada en Hostinger.</p><form method="post"><label>Usuario administrador<input name="username" value="admin" required></label><label>Nombre completo<input name="full_name" value="Administrador" required></label><label>Contraseña inicial<input type="password" name="password" minlength="8" required></label><button class="button">Crear tablas e instalar</button></form><?php endif;?></div></main></body></html>
