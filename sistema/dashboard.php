<?php
require_once __DIR__.'/layout.php'; $u=require_login();
$pdo=db();
$studentCount=(int)$pdo->query('SELECT COUNT(*) FROM students WHERE active=1')->fetchColumn();
$sectionCount=(int)$pdo->query('SELECT COUNT(*) FROM sections')->fetchColumn();
$subjectCount=(int)$pdo->query('SELECT COUNT(*) FROM subjects WHERE active=1')->fetchColumn();
$gradeCount=(int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM (SELECT student_id FROM grades WHERE score IS NOT NULL UNION SELECT student_id FROM ra_scores WHERE score IS NOT NULL) x")->fetchColumn();
app_header('Panel docente'); ?>
<div class="sys-grid">
 <div class="sys-card"><span>👥</span><b class="metric"><?=$studentCount?></b><h3>Estudiantes</h3><p>Registrados y activos.</p><a href="students.php">Ver estudiantes →</a></div>
 <div class="sys-card"><span>🏫</span><b class="metric"><?=$sectionCount?></b><h3>Secciones</h3><p>Grados y secciones configurados.</p><?php if(role_in(['admin'])):?><a href="catalogs.php">Configurar →</a><?php endif;?></div>
 <div class="sys-card"><span>📚</span><b class="metric"><?=$subjectCount?></b><h3>Asignaturas y módulos</h3><p>Áreas P1–P4 y módulos por RA.</p><a href="grades.php">Registrar evaluación →</a></div>
 <div class="sys-card"><span>📊</span><b class="metric"><?=$gradeCount?></b><h3>Con calificaciones</h3><p>Estudiantes con al menos una nota.</p><a href="reports.php">Generar boletín →</a></div>
</div>
<div class="panel"><h2>Accesos rápidos</h2><div class="sys-grid">
 <div class="sys-card"><h3>📘 Áreas académicas</h3><p>Registrar P1, P2, P3 y P4 por sección y asignatura.</p><a href="grades.php">Abrir P1–P4</a></div><div class="sys-card"><h3>🧩 Módulos técnicos</h3><p>Registrar RA1, RA2, RA3… según cada módulo.</p><a href="modules.php">Abrir módulos RA</a></div>
 <div class="sys-card"><h3>🗓️ Asistencia</h3><p>Registrar asistencia y ausencia por período.</p><a href="attendance.php">Abrir asistencia</a></div>
 <div class="sys-card"><h3>🧾 Boletines</h3><p>Consultar e imprimir el resumen individual.</p><a href="reports.php">Generar boletín</a></div>
 <?php if(role_in(['admin'])):?><div class="sys-card"><h3>⚙️ Administración</h3><p>Usuarios, años, secciones, asignaturas y asignaciones.</p><a href="catalogs.php">Configurar</a></div><?php endif;?>
</div></div>
<?php app_footer(); ?>
