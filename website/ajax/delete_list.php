<?php
$query = $GLOBALS['db']->prepare('SELECT rowid FROM lists WHERE `user` = ? AND `rowid` = ?');
$query->execute([$GLOBALS['user'][0], $_POST['list']]);
$list_array = $query->fetch(PDO::FETCH_NUM);
if(!$list_array) die('Not your list!');
$query = $GLOBALS['db']->prepare('DELETE FROM lists WHERE `rowid` = ? AND `user` = ?');
$query->execute([$_POST['list'], $GLOBALS['user'][0]]);
$query = $GLOBALS['db']->prepare('DELETE FROM list_triggers WHERE `list` = ?');
$query->execute([$_POST['list']]);
$query = $GLOBALS['db']->prepare('DELETE FROM list_filters WHERE `list` = ?');
$query->execute([$_POST['list']]);
$query = $GLOBALS['db']->prepare('DELETE FROM list_items WHERE `list` = ?');
$query->execute([$_POST['list']]);
print("OK");