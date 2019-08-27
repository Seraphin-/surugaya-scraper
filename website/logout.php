<?php
setcookie('session', '', 1, '/scraper-site/', $_SERVER['HTTP_HOST'], true, true);
$db = new PDO("sqlite:/surugaya-site.db3");
$query = $db->prepare('UPDATE users SET `session` = NULL, `session_ip` = NULL WHERE `session` = ? AND `session_ip` = ?');
$query->execute([$_COOKIE['session'], $_SERVER['REMOTE_ADDR']]);
header('Location: /scraper-site/login');