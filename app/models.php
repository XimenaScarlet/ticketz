<?php
function user_by_id(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id=?'); $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function update_profile(int $id, string $name, string $theme, int $notify, ?string $newpass): bool {
    $pdo=db();
    if ($newpass) {
        $stmt=$pdo->prepare('UPDATE users SET name=?, theme=?, notify_email=?, pass=? WHERE id=?');
        return $stmt->execute([$name,$theme,$notify,password_hash($newpass,PASSWORD_DEFAULT),$id]);
    } else {
        $stmt=$pdo->prepare('UPDATE users SET name=?, theme=?, notify_email=? WHERE id=?');
        return $stmt->execute([$name,$theme,$notify,$id]);
    }
}
function update_status(int $id, string $status): bool {
    $allowed = ['disponible','en_llamada','ausente','offline'];
    if (!in_array($status, $allowed, true)) $status = 'offline';
    $st = db()->prepare('UPDATE users SET status=?, status_since=? WHERE id=?');
    return $st->execute([$status, time(), $id]);
}
/**
 * CREATE_TICKET: ahora asigna al creador como agente (agent_id = user_id)
 */
function create_ticket(int $user_id, string $title, string $category, string $priority, string $description): int {
    $pdo=db(); $now=time();
    $stmt=$pdo->prepare('INSERT INTO tickets(user_id,agent_id,title,category,priority,status,description,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$user_id,$user_id,$title,$category,$priority,'abierto',$description,$now,$now]);
    return (int)$pdo->lastInsertId();
}
function assign_ticket(int $ticket_id, ?int $agent_id) {
    $stmt=db()->prepare('UPDATE tickets SET agent_id=?, updated_at=? WHERE id=?');
    $stmt->execute([$agent_id, time(), $ticket_id]);
}
function set_ticket_status(int $ticket_id, string $status) {
    $stmt=db()->prepare('UPDATE tickets SET status=?, updated_at=? WHERE id=?');
    $stmt->execute([$status, time(), $ticket_id]);
}
function add_comment(int $ticket_id, int $user_id, string $body) {
    $stmt=db()->prepare('INSERT INTO comments(ticket_id,user_id,body,created_at) VALUES(?,?,?,?)');
    $stmt->execute([$ticket_id,$user_id,$body,time()]);
}
function list_my_tickets(int $uid, ?string $state=null): array {
    $sql='SELECT t.*, u.name AS user_name, a.name AS agent_name FROM tickets t
          JOIN users u ON u.id=t.user_id
          LEFT JOIN users a ON a.id=t.agent_id';
    $where=' WHERE t.user_id=?'; $params=[ $uid ];
    if ($state && $state!=='todos') { $where.=' AND t.status=?'; $params[]=$state; }
    $sql.=$where.' ORDER BY t.updated_at DESC';
    $st=db()->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function list_all_tickets(?string $state=null): array {
    $sql='SELECT t.*, u.name AS user_name, a.name AS agent_name FROM tickets t
          JOIN users u ON u.id=t.user_id
          LEFT JOIN users a ON a.id=t.agent_id';
    $where=''; $params=[];
    if ($state && $state!=='todos') { $where.=' WHERE t.status=?'; $params[]=$state; }
    $sql.=$where.' ORDER BY t.updated_at DESC';
    $st=db()->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function get_ticket(int $id): ?array {
    $st=db()->prepare('SELECT t.*, u.name AS user_name, a.name AS agent_name 
        FROM tickets t JOIN users u ON u.id=t.user_id LEFT JOIN users a ON a.id=t.agent_id WHERE t.id=?');
    $st->execute([$id]); $t=$st->fetch(PDO::FETCH_ASSOC);
    return $t ?: null;
}
function ticket_comments(int $tid): array {
    $st=db()->prepare('SELECT c.*, u.name FROM comments c JOIN users u ON u.id=c.user_id WHERE c.ticket_id=? ORDER BY c.created_at ASC');
    $st->execute([$tid]); return $st->fetchAll(PDO::FETCH_ASSOC);
}
function list_agents(): array {
    $st=db()->query('SELECT id,name,email,last_active,status,status_since FROM users WHERE role IN ("agent","admin") ORDER BY name');
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
