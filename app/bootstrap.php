<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
date_default_timezone_set('America/Monterrey');

require_once __DIR__ . '/util.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/models.php';

$user = current_user();
if ($user) { touch_activity((int)$user['id']); }

$APP_NAME = "TicketZ";
$BASE_PATH = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
