<?php
$time_start = microtime(true);
$itemdb = new PDO("sqlite:/surugaya.db3");
if(isset($_GET['search'])) {
    if(strlen($_GET['search']) <= 2) die('[["Search is too vague"]]');
    $query = $itemdb->prepare('SELECT * FROM items WHERE `productid` = ? OR `name` LIKE "%" || ? || "%" OR release LIKE "%" || ? || "%" ORDER BY `price` DESC');
    $query->execute([$_GET['search'], $_GET['search'], $_GET['search']]);
    $items = $query->fetchAll(PDO::FETCH_NUM);
}
if(isset($_GET['timesale'])) {
    $items = $itemdb->query('SELECT * FROM items WHERE `timesale` = 1 ORDER BY `price` DESC')->fetchAll(PDO::FETCH_NUM);
}
if(count($items) == 0) $items = [[]];
$items[0][] = microtime(true) - $time_start;
print(json_encode($items));