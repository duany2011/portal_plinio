<?php
require_once __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}
function login_user(array $u): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$u['id'],
        'username' => $u['username'],
        'full_name' => $u['full_name'],
        'role' => $u['role'],
    ];
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function require_login(): array {
    $u=current_user();
    if(!$u){ header('Location: ../docentes-login.php'); exit; }
    return $u;
}
function role_in(array $roles): bool {
    $u=current_user(); return $u && in_array($u['role'],$roles,true);
}
function require_role(array $roles): array {
    $u=require_login();
    if(!in_array($u['role'],$roles,true)){ http_response_code(403); exit('No autorizado.'); }
    return $u;
}
function csrf_token(): string {
    if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if(!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])){
        http_response_code(419); exit('Solicitud vencida. Recargue la página.');
    }
}
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function flash(string $type,string $msg): void { $_SESSION['flash']=[$type,$msg]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
