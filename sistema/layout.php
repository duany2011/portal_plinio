<?php
require_once __DIR__.'/auth.php';
function role_label(string $r): string {
    return ['admin'=>'Administrador','director'=>'Director','coordinator'=>'Coordinador','teacher'=>'Docente'][$r] ?? $r;
}
function app_header(string $title): void {
    $u=require_login(); global $config; $flash=take_flash();
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?> | <?=h($config['app_name'])?></title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="sistema.css"></head><body class="system-page">
    <div class="topbar"><span>📍 Calle 53A, Barrio Alegría, Cienfuegos, Santiago</span><span>📞 (849) 997-5995 · ✉ registroplinio13@gmail.com</span></div>
    <header class="header"><a class="brand" href="../index.html"><img src="../assets/logo.jpg" alt="Logotipo"><span>Politécnico Prof. Plinio Martínez<small>Educando, formando y trabajando</small></span></a><button class="menu" aria-label="Abrir menú">☰</button><nav class="nav"><a href="dashboard.php">Panel</a><a href="students.php">Estudiantes</a><a href="grades.php">Académicas P1–P4</a><a href="modules.php">Módulos RA</a><a href="attendance.php">Asistencia</a><a href="reports.php">Boletines</a><?php if(role_in(['admin'])):?><a href="catalogs.php">Configuración</a><a href="users.php">Usuarios</a><?php endif;?><a href="logout.php">Salir</a></nav></header>
    <main class="sys-container">
      <div class="sys-head"><div><div class="eyebrow">ÁREA DOCENTE</div><h1><?=h($title)?></h1></div><div class="user-pill"><b><?=h($u['full_name'])?></b><span><?=h(role_label($u['role']))?></span></div></div>
      <?php if($flash): ?><div class="alert <?=h($flash[0])?>"><?=h($flash[1])?></div><?php endif; ?>
    <?php
}
function app_footer(): void { ?>
    </main><footer class="sys-footer">© <?=date('Y')?> Politécnico Prof. Plinio Martínez · Sistema académico interno</footer><script src="../script.js"></script></body></html><?php }
