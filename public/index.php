<?php
require_once __DIR__ . '/../app/bootstrap.php';
csrf_check();

/** Flash helpers within this file **/
function pop_flash(string $key): ?string {
    if (!isset($_SESSION)) session_start();
    if (!isset($_SESSION['flash']) || !array_key_exists($key, $_SESSION['flash'])) return null;
    $v = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return is_string($v) ? $v : null;
}
function render_toast_once(): void {
    $msg = pop_flash('msg');
    if (!$msg) return;
    echo '<div id="toast-root" class="tz-toast" role="status" aria-live="polite">
            <div class="tz-toast__card">
              <div class="tz-toast__content">'.e($msg).'</div>
              <button class="tz-toast__close" aria-label="Cerrar">✕</button>
            </div>
          </div>
          <style>
            .tz-toast{position:fixed; inset:auto 1rem 1rem auto; z-index:9999; display:flex; gap:.5rem; pointer-events:none}
            .tz-toast__card{pointer-events:auto; background:var(--card-background,#fff); color:inherit; border:1px solid rgba(0,0,0,.06); border-radius:12px; padding:.75rem 1rem; box-shadow:0 10px 30px rgba(0,0,0,.12); min-width:260px; max-width:360px; display:flex; align-items:center; gap:.75rem; animation:tz-slide-in .24s ease-out}
            .tz-toast__content{flex:1}
            .tz-toast__close{background:transparent;border:0;font-size:1rem;line-height:1;cursor:pointer;opacity:.6}
            .tz-toast__close:hover{opacity:1}
            @keyframes tz-slide-in{from{transform:translateX(20px);opacity:0}to{transform:translateX(0);opacity:1}}
            @media (max-width:600px){.tz-toast{inset:auto .75rem .75rem auto} .tz-toast__card{min-width:220px; max-width:90vw}}
          </style>
          <script>
            (function(){
              const root = document.getElementById("toast-root");
              if(!root) return;
              const closeBtn = root.querySelector(".tz-toast__close");
              let t = setTimeout(()=> root.remove(), 5000);
              root.addEventListener("mouseenter", ()=> { clearTimeout(t); });
              root.addEventListener("mouseleave", ()=> { t = setTimeout(()=> root.remove(), 2000); });
              closeBtn && closeBtn.addEventListener("click", ()=> root.remove());
            })();
          </script>';
}

$action = $_GET['action'] ?? null;
$page   = $_GET['page'] ?? 'home';

function human_duration(int $seconds): string {
    if ($seconds < 60) return $seconds.'s';
    if ($seconds < 3600) return intval($seconds/60).'m';
    if ($seconds < 86400) return intval($seconds/3600).'h '.intval(($seconds%3600)/60).'m';
    return intval($seconds/86400).'d';
}

// ---- Actions ----
switch ($action) {
    case 'login':
        $ok = login($_POST['email'] ?? '', $_POST['pass'] ?? '');
        if ($ok) { flash('msg','Sesión iniciada'); redirect('?page=dashboard'); }
        flash('msg','Credenciales inválidas'); redirect('?page=login');
        break;
    case 'register':
        $email = $_POST['email'] ?? '';
        $pass  = $_POST['pass'] ?? '';
        [$ok,$msg]=register_user($_POST['name']??'', $email, $pass);
        flash('msg',$msg);
        if ($ok) {
            login($email, $pass);
            redirect('?page=dashboard');
        } else {
            redirect('?page=register');
        }
        break;
    case 'logout':
        logout(); flash('msg','Sesión cerrada'); redirect('?page=home'); break;
    case 'new_ticket':
        require_login();
        $id = create_ticket((int)$_SESSION['user']['id'], trim($_POST['title']??''), $_POST['category']??'General', $_POST['priority']??'media', trim($_POST['description']??''));
        flash('msg','Ticket creado #'.$id); redirect('?page=ticket&id='.$id); break;
    case 'status':
        require_login();
        set_ticket_status((int)$_POST['ticket_id'], $_POST['status']);
        flash('msg','Estado actualizado'); redirect('?page=ticket&id='.(int)$_POST['ticket_id']); break;
    case 'assign':
        require_login();
        $agent = $_POST['agent_id'] !== '' ? (int)$_POST['agent_id'] : null;
        assign_ticket((int)$_POST['ticket_id'], $agent);
        flash('msg','Asignación actualizada'); redirect('?page=ticket&id='.(int)$_POST['ticket_id']); break;
    case 'profile':
        require_login();
        $u=$_SESSION['user'];
        $name = trim($_POST['name']??$u['name']);
        $newpass = strlen($_POST['newpass']??'') ? $_POST['newpass'] : null;
        $ok=update_profile((int)$u['id'], $name, $u['theme'], (int)$u['notify_email'], $newpass);
        if ($ok) {
            $_SESSION['user']=user_by_id((int)$u['id']);
            flash('msg','Perfil actualizado');
        }
        redirect('?page=profile'); break;
    case 'theme':
        require_login();
        $th = $_POST['theme'] ?? 'light';
        update_profile((int)$_SESSION['user']['id'], $_SESSION['user']['name'], $th, (int)$_SESSION['user']['notify_email'], null);
        $_SESSION['user']=user_by_id((int)$_SESSION['user']['id']);
        flash('msg','Tema actualizado');
        redirect('?page=settings'); break;
    case 'set_status':
        require_login();
        $st = $_POST['status'] ?? 'offline';
        update_status((int)$_SESSION['user']['id'], $st);
        flash('msg','Estatus actualizado a: '.$st);
        redirect('?page=phone'); break;
}

// ---- Páginas públicas ----
if ($page === 'home') {
    render_header('TicketZ — Inicio', $user);
    render_toast_once();
    echo '<section class="hero-landing">
      <svg class="hero-blob" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g transform="translate(300,300)">
          <path d="M120.7,-172.4C159.9,-149.5,194.6,-121.6,210.6,-85.1C226.6,-48.6,223.8,-3.6,210.8,36.8C197.8,77.3,174.6,113.3,144.4,146.1C114.3,178.9,77.2,208.4,35.3,227.4C-6.6,246.4,-52.9,254.8,-89.5,238.2C-126.1,221.5,-153.1,179.7,-179.6,140.3C-206.2,100.8,-232.3,63.8,-236.6,23.6C-240.9,-16.6,-223.4,-60.1,-197.3,-95.2C-171.2,-130.3,-136.4,-156.9,-100.7,-179.3C-65,-201.6,-28.5,-219.7,6.5,-229C41.5,-238.3,83,-238.9,120.7,-172.4Z" fill="#f4e6d6"/>
        </g>
      </svg>
      <div class="wrap">
        <div>
          <h1>Soporte ágil para tu equipo</h1>
          <p>Centraliza solicitudes, da seguimiento claro y mantén a tu equipo en sintonía con un <strong>sistema de tickets</strong> rápido y simple.</p>
          <div class="cta">
            <a class="primary" href="?page=login">Entrar</a>
            <a class="secondary" href="?page=register">Crear cuenta</a>
          </div>
          <div class="features">
            <div class="feature"><h3><span class="ico">🎫</span> Tickets claros</h3><p>Prioriza, clasifica y cambia estado en segundos.</p></div>
            <div class="feature"><h3><span class="ico">👥</span> Agentes</h3><p>Ve quién está disponible y su tiempo en estado.</p></div>
            <div class="feature"><h3><span class="ico">⚡</span> Ligero</h3><p>Sin dependencias pesadas: PHP + SQLite.</p></div>
          </div>
        </div>
        <div class="hero-illus">
          <div>
            <h3 style="margin:.2rem 0 1rem">¿Qué es TicketZ?</h3>
            <p style="margin:0; max-width:42ch">
              Una plataforma mínima pero potente para gestionar incidencias y solicitudes internas.
              Crea tickets en segundos, asígnalos a agentes, actualiza estados y consulta el
              historial cuando lo necesites. Ideal para equipos que quieren orden sin complicarse.
            </p>
          </div>
        </div>
      </div>
    </section>';
    render_footer(); exit;
}

if ($page === 'login') {
    render_header('Entrar', $user ?? null);
    render_toast_once();
    echo '<section class="auth-center">
      <style>.auth-center{min-height:calc(100vh - 120px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-center .card{max-width:460px;width:100%}</style>
      <article class="card">
        <h2 style="text-align:center;margin-top:0">Iniciar sesión</h2>
        <form method="post" action="?action=login">'.form_csrf().'
          <label>Email<input type="email" name="email" required value="'.e($_POST['email']??'').'"></label>
          <label>Contraseña<input type="password" name="pass" required></label>
          <button type="submit" class="btn-primary" style="width:100%">Entrar</button>
        </form>
      </article>
    </section>';
    render_footer(); exit;
}

if ($page === 'register') {
    render_header('Registro', $user ?? null);
    render_toast_once();
    echo '<section class="auth-center">
      <style>.auth-center{min-height:calc(100vh - 120px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-center .card{max-width:520px;width:100%}</style>
      <article class="card">
        <h2 style="text-align:center;margin-top:0">Crear cuenta</h2>
        <form method="post" action="?action=register">'.form_csrf().'
          <label>Nombre<input type="text" name="name" required></label>
          <label>Email<input type="email" name="email" required></label>
          <label>Contraseña<input type="password" name="pass" minlength="6" required></label>
          <button type="submit" class="btn-primary" style="width:100%">Registrarme</button>
        </form>
      </article>
    </section>';
    render_footer(); exit;
}

// ---- Privadas ----
require_login();

if ($page === 'dashboard') {
    $mine = list_my_tickets((int)$user['id'], $_GET['f'] ?? null);
    $all  = ($user['role']==='agent' || $user['role']==='admin') ? list_all_tickets($_GET['f'] ?? null) : [];

    render_header('Dashboard', $user);
    render_toast_once();
    echo '<div class="narrow">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem">
          <h2 style="margin:0">Dashboard</h2>
          <button id="btnNewTicket" class="btn-primary">+ Nuevo ticket</button>
        </div>

        '.(($all && count($all)>0) ? ('<article class="card"><h3>Cola global (agentes)</h3><div class="table-scroll"><table><thead><tr><th>#</th><th>Título</th><th>Cliente</th><th>Estado</th><th>Agente</th></tr></thead><tbody>'.
            implode("", array_map(function($t){
                return "<tr><td>".(int)$t["id"]."</td><td><a href=\"?page=ticket&id=".(int)$t["id"]."\">".e($t["title"])."</a></td><td>".e($t["user_name"])."</td><td>".e($t["status"])."</td><td>".e($t["agent_name"]??"—")."</td></tr>";
            }, array_slice($all,0,20)))
            .'</tbody></table></div></article>') : '<article class="card"><h3>Cola global</h3><p class="small">Sin tickets globales o no eres agente.</p></article>').'

        <article class="card">
          <h3>Actividad reciente</h3>';
          if (!$mine) echo '<p class="small">Sin tickets aún.</p>';
          else {
              echo '<div class="table-scroll"><table><thead><tr><th>#</th><th>Título</th><th>Estado</th><th>Agente</th><th>Actualizado</th></tr></thead><tbody>';
              foreach (array_slice($mine,0,8) as $t) {
                  echo '<tr><td>'.(int)$t['id'].'</td>
                  <td><a href="?page=ticket&id='.(int)$t['id'].'">'.e($t['title']).'</a></td>
                  <td><span class="badge status-'.e($t['status']).'">'.e($t['status']).'</span></td>
                  <td>'.e($t['agent_name'] ?? '—').'</td>
                  <td>'.date('Y-m-d H:i', (int)$t['updated_at']).'</td></tr>';
              }
              echo '</tbody></table></div>';
          }
    echo '  </article>
      </div>

      <dialog id="dlgNewTicket" class="modal">
        <article>
          <header style="display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0">Nuevo ticket</h3>
            <button aria-label="Cerrar" id="closeDlg" class="secondary">✕</button>
          </header>
          <form method="post" action="?action=new_ticket">'.form_csrf().'
            <label>Título<input name="title" required></label>
            <div class="grid-2">
              <label>Categoría<select name="category">
                  <option>General</option><option>Acceso</option><option>Hardware</option><option>Software</option>
              </select></label>
              <label>Prioridad<select name="priority">
                  <option>baja</option><option selected>media</option><option>alta</option>
              </select></label>
            </div>
            <label>Descripción<textarea name="description"></textarea></label>
            <footer style="display:flex;gap:.5rem;justify-content:flex-end">
              <button type="button" id="cancelDlg" class="secondary">Cancelar</button>
              <button type="submit" class="btn-primary">Crear</button>
            </footer>
          </form>
        </article>
      </dialog>

      <script>
        (function(){
          const btn = document.getElementById("btnNewTicket");
          const dlg = document.getElementById("dlgNewTicket");
          const close = document.getElementById("closeDlg");
          const cancel = document.getElementById("cancelDlg");
          if(btn && dlg){
            btn.addEventListener("click", ()=> dlg.showModal());
            [close,cancel].forEach(b=> b && b.addEventListener("click", ()=> dlg.close()));
          }
        })();
      </script>
    ';

    render_footer(); exit;
}

if ($page === 'tickets') {
    $state = $_GET['f'] ?? 'todos';
    $list = list_my_tickets((int)$user['id'], $state);
    render_header('Mis Tickets', $user);
    render_toast_once();
    echo '<div class="narrow"><h2>Mis Tickets</h2>
    <details><summary>Filtros</summary>
      <a href="?page=tickets&f=todos">Todos</a> ·
      <a href="?page=tickets&f=abierto">Abiertos</a> ·
      <a href="?page=tickets&f=pendiente">Pendientes</a> ·
      <a href="?page=tickets&f=cerrado">Cerrados</a>
    </details>';
    if (!$list) echo '<p>No hay resultados.</p>';
    else {
        echo '<div class="table-scroll"><table><thead><tr><th>#</th><th>Título</th><th>Estado</th><th>Agente</th><th>Actualizado</th></tr></thead><tbody>';
        foreach ($list as $t) {
            echo '<tr><td>'.(int)$t['id'].'</td><td><a href="?page=ticket&id='.(int)$t['id'].'">'.e($t['title']).'</a></td><td>'.e($t['status']).'</td><td>'.e($t['agent_name']??'—').'</td><td>'.date('Y-m-d H:i',(int)$t['updated_at']).'</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</div>';
    render_footer(); exit;
}

if ($page === 'ticket') {
    $tid = (int)($_GET['id'] ?? 0);
    $t = get_ticket($tid);
    if (!$t) { flash('msg','Ticket no existe'); redirect('?page=tickets'); }
    $agents = list_agents();
    render_header('Ticket #'.$tid, $user);
    render_toast_once();
    echo '<div class="narrow">
      <article class="card">
        <div class="ticket-header">
          <h3>#'.(int)$t['id'].' — '.e($t['title']).'</h3>
        </div>
        <p class="ticket-meta">
          <span><b>Cliente:</b> '.e($t['user_name']).'</span> ·
          <span><b>Categoría:</b> '.e($t['category']).'</span> ·
          <span><b>Prioridad:</b> '.e($t['priority']).'</span>
        </p>
        <p><b>Estado:</b> <span class="badge status-'.e($t['status']).'">'.e($t['status']).'</span> ·
           <b>Agente:</b> '.e($t['agent_name']??'—').'</p>
        <p>'.nl2br(e($t['description'] ?? '')).'</p>
        <div class="grid-2">
          <form class="actions" method="post" action="?action=status">'.form_csrf().'
              <input type="hidden" name="ticket_id" value="'.(int)$t['id'].'">
              <label>Estado<select name="status"><option '.($t['status']=='abierto'?'selected':'').'>abierto</option><option '.($t['status']=='pendiente'?'selected':'').'>pendiente</option><option '.($t['status']=='cerrado'?'selected':'').'>cerrado</option></select></label>
              <button>Actualizar estado</button>
          </form>
          <form class="actions" method="post" action="?action=assign">'.form_csrf().'
              <input type="hidden" name="ticket_id" value="'.(int)$t['id'].'">
              <label>Asignar a<select name="agent_id"><option value="">— sin asignar —</option>';
              foreach ($agents as $a) {
                  $sel = ($t['agent_id']??null)==$a['id'] ? 'selected' : '';
                  $online = $a['last_active'] && (time() - (int)$a['last_active'] < 300) ? '🟢' : '⚪';
                  echo '<option '.$sel.' value="'.(int)$a['id'].'">'.$online.' '.e($a['name']).'</option>';
              }
          echo '</select></label><button>Asignar</button></form>
        </div>
      </article>
    </div>';
    render_footer(); exit;
}

if ($page === 'agents') {
    $agents = list_agents();
    render_header('Agentes', $user);
    render_toast_once();
    echo '<div class="narrow"><h2>Agentes y actividad</h2><div class="table-scroll"><table><thead>
    <tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Tiempo en estado</th><th>Actividad</th></tr></thead><tbody>';
    foreach ($agents as $a) {
        $ago = $a['last_active'] ? (time() - (int)$a['last_active']) : 999999;
        $label = $ago < 120 ? 'En línea' : ($ago < 3600 ? 'Activo hace '.intval($ago/60).' min' : 'Hace '.intval($ago/3600).' h');
        $since = $a['status_since'] ? human_duration(time() - (int)$a['status_since']) : '—';
        $state_label = $a['status'] === 'en_llamada' ? 'En llamada' :
                       ($a['status'] === 'disponible' ? 'Disponible' :
                        ($a['status'] === 'ausente' ? 'Ausente' : 'Offline'));
        echo '<tr><td>'.e($a['name']).'</td><td>'.e($a['email']).'</td><td>'.$state_label.'</td><td>'.$since.'</td><td>'.$label.'</td></tr>';
    }
    echo '</tbody></table></div></div>';
    render_footer(); exit;
}

if ($page === 'phone') {
    $me = user_by_id((int)$user['id']);
    render_header('Phone — Estatus', $user);
    render_toast_once();
    $current = $me['status'] ?? 'offline';
    $since = $me['status_since'] ? human_duration(time() - (int)$me['status_since']) : '—';
    echo '<article class="card narrow">
      <h2 style="margin-top:0">Mi estatus de teléfono</h2>
      <p>Actual: <strong>'.e($current).'</strong> · desde: <strong>'.$since.'</strong></p>
      <form method="post" action="?action=set_status">'.form_csrf().'
        <fieldset>
          <legend>Cambiar estatus</legend>
          <label><input type="radio" name="status" value="disponible" '.($current==='disponible'?'checked':'').'> Disponible</label>
          <label><input type="radio" name="status" value="en_llamada" '.($current==='en_llamada'?'checked':'').'> En llamada</label>
          <label><input type="radio" name="status" value="ausente" '.($current==='ausente'?'checked':'').'> Ausente</label>
          <label><input type="radio" name="status" value="offline" '.($current==='offline'?'checked':'').'> Offline</label>
        </fieldset>
        <button type="submit" class="btn-primary">Guardar</button>
      </form>
      <p class="small">Esta página se abre en una pestaña nueva desde el navbar.</p>
    </article>';
    render_footer(); exit;
}

if ($page === 'profile') {
    $u = user_by_id((int)$user['id']);
    render_header('Perfil', $user);
    render_toast_once();
    echo '<section class="auth-center">
      <style>.auth-center{min-height:calc(100vh - 120px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-center .card{max-width:520px;width:100%}</style>
      <article class="card">
        <h2 style="text-align:center;margin-top:0">Perfil</h2>
        <form method="post" action="?action=profile">'.form_csrf().'
          <label>Nombre<input name="name" value="'.e($u['name']).'" required></label>
          <label>Email<input type="email" value="'.e($u['email']).'" readonly></label>
          <label>Nueva contraseña (opcional)<input type="password" name="newpass" minlength="6" placeholder="••••••"></label>
          <button class="btn-primary" style="width:100%">Guardar</button>
        </form>
      </article>
    </section>';
    render_footer(); exit;
}

if ($page === 'settings') {
    $u = user_by_id((int)$user['id']);
    render_header('Configuración', $user);
    render_toast_once();
    $theme = $u['theme'] ?? 'light';
    echo '<section class="auth-center">
      <style>.auth-center{min-height:calc(100vh - 120px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-center .card{max-width:520px;width:100%}</style>
      <article class="card">
        <h2 style="text-align:center;margin-top:0">Apariencia</h2>
        <form method="post" action="?action=theme">'.form_csrf().'
          <label>Tono de la página
            <select name="theme">
              <option value="light" '.($theme==='light'?'selected':'').'>Claro</option>
              <option value="dark" '.($theme==='dark'?'selected':'').'>Oscuro</option>
            </select>
          </label>
          <button class="btn-primary" style="width:100%">Guardar</button>
        </form>
      </article>
    </section>';
    render_footer(); exit;
}

http_response_code(404);
render_header('404', $user);
render_toast_once();
echo '<h2>404</h2><p>Página no encontrada.</p>';
render_footer();
