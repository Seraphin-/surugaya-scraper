<?php
$query = $GLOBALS['db']->prepare('SELECT `rowid` FROM lists WHERE `default` = 1 AND `user` = ?');
$query->execute([$GLOBALS['user'][0]]);
$defaultList = $query->fetch(PDO::FETCH_NUM);
$query = $GLOBALS['db']->prepare('DELETE FROM list_items WHERE `list` = ? AND `productid` = ?');
$query->execute([$defaultList[0], $_POST['id']]);