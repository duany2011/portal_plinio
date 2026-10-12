<?php
require_once __DIR__.'/sistema/auth.php';
if(current_user()){ header('Location: sistema/dashboard.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $username=trim($_POST['username']??''); $password=$_POST['password']??'';
    try{
        $st=db()->prepare('SELECT * FROM users WHERE username=? AND active=1 LIMIT 1');
        $st->execute([$username]); $u=$st->fetch();
        if($u && password_verify($password,$u['password_hash'])){ login_user($u); header('Location: sistema/dashboard.php'); exit; }
        $error='Usuario o contraseña incorrectos.';
    }catch(Throwable $e){ $error='La base de datos todavía no está configurada. Revise sistema/config.php y ejecute sistema/setup.php.'; }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso docente | Politécnico Plinio Martínez</title><link rel="stylesheet" href="styles.css"></head><body>
<div class="topbar"><span>📍 Calle 53A, Barrio Alegría, Cienfuegos, Santiago</span><span>📞 (849) 997-5995 · ✉ registroplinio13@gmail.com</span></div>
<header class="header"><a class="brand" href="index.html"><img src="assets/logo.jpg" alt="Logotipo"><span>Politécnico Prof. Plinio Martínez<small>Educando, formando y trabajando</small></span></a><button class="menu" aria-label="Abrir menú">☰</button><nav class="nav"><a href="index.html">Inicio</a><a href="centro.html">Nuestro centro</a><a href="oferta.html">Oferta académica</a><a href="noticias.html">Noticias</a><a href="galeria.html">Galería</a><a href="graduandos-2026.html">Graduandos 2026</a><a href="estudiantes.html">Estudiantes</a><a href="docentes-login.php" class="active">Docentes</a><a href="contacto.html">Contacto</a></nav></header>
<main><section class="login-section"><div class="login-card"><div class="login-logo"><img src="assets/logo.jpg" alt="Logo"></div><div class="eyebrow">ÁREA RESERVADA</div><h1>Portal de docentes</h1><p>Ingresa tus credenciales para acceder al sistema académico.</p><?php if($error):?><div class="security-note" style="border-left-color:#d7262e"><?=h($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><label>Usuario<input name="username" type="text" autocomplete="username" required placeholder="Usuario docente"></label><label>Contraseña<input name="password" type="password" autocomplete="current-password" required placeholder="Contraseña"></label><button class="btn login-btn" type="submit">Iniciar sesión</button></form>
<p class="demo-help">La autenticación ahora utiliza usuarios almacenados de forma segura en MySQL.</p><a class="back-link" href="index.html">← Volver al inicio</a></div></section></main>
<footer class="footer"><div class="footer-grid"><div><img src="assets/logo.jpg" alt="Logo"><h3>Politécnico Prof. Plinio Martínez</h3><p>Educación técnica, innovación y valores para un mundo mejor.</p></div><div><h3>Enlaces rápidos</h3><p><a href="estudiantes.html">Portal de estudiantes</a></p><p><a href="docentes-login.php">Portal de docentes</a></p><p><a href="oferta.html">Oferta académica</a></p></div><div><h3>Contacto</h3><p>(849) 997-5995</p><p>registroplinio13@gmail.com</p></div></div><div class="copyright">© <?=date('Y')?> Politécnico Prof. Plinio Martínez</div></footer><script src="script.js"></script></body></html>
