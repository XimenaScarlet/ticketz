<?php
function redirect(string $url) {
    header("Location: $url"); exit;
}
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
            http_response_code(400); echo "CSRF inválido"; exit;
        }
    }
}
function form_csrf(): string {
    return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">';
}

function flash(string $key, ?string $msg = null) {
    if ($msg === null) {
        $m = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $m;
    }
    $_SESSION['_flash'][$key] = $msg;
}
function get_theme(): string {
    $u = $_SESSION['user'] ?? null;
    return $u['theme'] ?? 'light';
}

function render_header(string $title, ?array $user) {
$theme = get_theme();
echo '<!DOCTYPE html><html lang="es" data-theme="'.e($theme).'"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>'.e($title).'</title>
<link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css">
<link rel="stylesheet" href="assets/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head><body>
<header class="container">
  <nav>
    <ul><li><strong>TicketZ</strong></li></ul>
    <ul>';
    if ($user) {
        echo '<li><a href="?page=dashboard">Dashboard</a></li>
              <li><a href="?page=tickets">Mis Tickets</a></li>
              <li><a href="?page=agents">Agentes</a></li>
              <li><a href="?page=settings">Configuración</a></li>
              <li><a href="?page=profile">Perfil</a></li>
              <li><a href="?page=phone" target="_blank">Phone</a></li>
              <li><a class="contrast" href="?action=logout">Salir</a></li>';
    } else {
        echo '<li><a href="?page=home">Inicio</a></li>
              <li><a href="?page=login">Entrar</a></li>
              <li><a href="?page=register">Registro</a></li>';
    }
echo '</ul></nav></header><main class="container content">';
$m = flash('msg'); if ($m) { echo '<article class="msg">'.$m.'</article>'; }
}

function render_footer() {
    echo '</main><footer class="container"></footer>
<script>
document.querySelectorAll("[data-theme-toggle]").forEach(b=>{
  b.addEventListener("click",()=>{
    const cur=document.documentElement.getAttribute("data-theme")==="dark"?"light":"dark";
    document.documentElement.setAttribute("data-theme",cur);
    fetch("?action=theme&v="+cur,{method:"POST",headers:{ "Content-Type":"application/x-www-form-urlencoded" },body:"csrf=<?php echo e(csrf_token()); ?>&theme="+cur});
  });
});
</script>
</body></html>';
}
