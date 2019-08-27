<?php
$query = $GLOBALS['db']->prepare('INSERT INTO lists(`user`, `name`) VALUES(?,?)');
$query->execute([$GLOBALS['user'][0], $_POST['name']]);
print($GLOBALS['db']->lastInsertId());