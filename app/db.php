<?php
declare(strict_types=1);

define('APP_ROOT', realpath(__DIR__ . '/..'));
define('DATA_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'data');
define('DB_FILE', DATA_DIR . DIRECTORY_SEPARATOR . 'app.db');

function ensure_data_dir(): void {
    if (!is_dir(DATA_DIR)) { @mkdir(DATA_DIR, 0777, true); }
    if (!is_writable(DATA_DIR)) { @chmod(DATA_DIR, 0777); }
    if (!file_exists(DB_FILE)) { @touch(DB_FILE); }
    if (!is_writable(DB_FILE)) { @chmod(DB_FILE, 0666); }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    ensure_data_dir();

    $dsn = 'sqlite:' . DB_FILE;
    try {
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
        migrate($pdo);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        $msg = "No se pudo abrir la base de datos SQLite en: " . DB_FILE . "\\nError: " . $e->getMessage();
        echo nl2br(htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        exit;
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->query('PRAGMA table_info('.$table.')');
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $r) {
        if (strcasecmp($r['name'], $column) === 0) return true;
    }
    return false;
}

function migrate(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS users(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        pass TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT "user",
        theme TEXT NOT NULL DEFAULT "light",
        notify_email INTEGER NOT NULL DEFAULT 1,
        last_active INTEGER DEFAULT NULL,
        created_at INTEGER NOT NULL
    )');
    // Add status columns if missing
    if (!column_exists($pdo, 'users', 'status')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN status TEXT NOT NULL DEFAULT "offline"');
    }
    if (!column_exists($pdo, 'users', 'status_since')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN status_since INTEGER DEFAULT NULL');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS tickets(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        agent_id INTEGER REFERENCES users(id),
        title TEXT NOT NULL,
        category TEXT NOT NULL,
        priority TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT "abierto",
        description TEXT,
        created_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS comments(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        body TEXT NOT NULL,
        created_at INTEGER NOT NULL
    )');

    // seed demo accounts if empty
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $now=time();
        $stmt=$pdo->prepare('INSERT INTO users(name,email,pass,role,theme,created_at,status,status_since) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute(['Admin', 'admin@demo.local', password_hash('Admin123!', PASSWORD_DEFAULT), 'admin', 'dark', $now,'disponible',$now]);
        $stmt->execute(['Agente Demo', 'agent@demo.local', password_hash('Agent123!', PASSWORD_DEFAULT), 'agent', 'dark', $now,'disponible',$now]);
        $stmt->execute(['Usuario Demo', 'user@demo.local', password_hash('User123!', PASSWORD_DEFAULT), 'user', 'light', $now,'offline',NULL]);
    }
}
