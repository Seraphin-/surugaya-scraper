<?php
header('Access-Control-Allow-Origin: *');
$GLOBALS['db'] = new PDO("sqlite:/surugaya-site.db3");
$query = $GLOBALS['db']->prepare('SELECT `rowid` FROM lists WHERE `key` = ?');
$query->execute([$_POST['key']]);
$list = $query->fetch(PDO::FETCH_NUM);
if(!$list) die('Invalid key!');
foreach($_POST['items'] as $item) {
    $query = $GLOBALS['db']->prepare('INSERT INTO list_items(`list`, `productid`) VALUES (?,?)');
    $query->execute([$list[0], $item]);
}
print("Added " . count($_POST['items']) . " items!");
?>
