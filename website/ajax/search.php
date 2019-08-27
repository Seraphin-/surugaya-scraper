<?php
$time_start = microtime(true);
$itemdb = new PDO("sqlite:/var/www/html/db/surugaya.db3");
$query = $itemdb->prepare('SELECT * FROM items WHERE `productid` = ? OR `name` LIKE "%" || ? || "%" OR circle LIKE "%" || ? || "%" OR release LIKE "%" || ? || "%"');
$query->execute([$_POST['search'], $_POST['search'], $_POST['search'], $_POST['search']]);
$items = $query->fetchAll(PDO::FETCH_NUM);
$items[0][] = microtime(true) - $time_start;
print(json_encode($items));