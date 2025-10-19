<?php
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login() {
    if (!current_user()) { flash('msg','Inicia sesión para continuar'); redirect('?page=login'); }
}
function login(string $email, string $pass): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($pass, $u['pass'])) {
        $_SESSION['user'] = $u;
        return true;
    }
    return false;
}
function logout() { unset($_SESSION['user']); session_regenerate_id(true); }
function register_user(string $name, string $email, string $pass): array {
    $pdo=db();
    $stmt=$pdo->prepare('INSERT INTO users(name,email,pass,role,created_at) VALUES(?,?,?,?,?)');
    try {
        $stmt->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),'user',time()]);
        $id=(int)$pdo->lastInsertId();
        $u=$pdo->query('SELECT * FROM users WHERE id='.$id)->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user']=$u;
        return [true, "¡Bienvenido/a $name!"];
    } catch (PDOException $e) {
        return [false, 'El correo ya está registrado'];
    }
}
function touch_activity(int $uid) {
    $pdo=db();
    $stmt=$pdo->prepare('UPDATE users SET last_active=? WHERE id=?');
    $stmt->execute([time(),$uid]);
}
