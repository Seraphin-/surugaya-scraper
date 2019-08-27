<?php
const SAFE_NAMES = ['productid', 'from', 'to', 'type'];
$itemdb = new PDO("sqlite:/surugaya.db3");
$query = 'SELECT * FROM changes LEFT JOIN items ON items.productid = changes.productid';
$params = [];
$wheres = 0;
foreach($_GET as $k => $v) {
    if(!in_array($k, SAFE_NAMES)) continue;
    $wheres += 1;
    if($wheres > 1) $query .= ' AND';
    else $query .= ' WHERE';
    $query .= ' changes.`' . $k . '` = ?';
    $params[] = $v;
}

if(isset($_GET['name'])) {
    $wheres++;
    if($wheres > 1) $query .= ' AND';
    else $query .= ' WHERE';
    $query .= ' items.`name` LIKE "%" || ? || "%"';
    $params[] = $_GET['name'];
}
$query .= ' ORDER BY found DESC LIMIT 1000';
//var_dump($query);
$changes = $itemdb->prepare($query);
$changes->execute($params);
print(json_encode($changes->fetchAll(PDO::FETCH_ASSOC)));