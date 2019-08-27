<?php
$GLOBALS['db']->beginTransaction();
$query = $GLOBALS['db']->prepare('SELECT rowid FROM lists WHERE `user` = ? AND `rowid` = ?');
$query->execute([$GLOBALS['user'][0], $_POST['list']]);
$list_array = $query->fetch(PDO::FETCH_NUM);
if(!$list_array) die('Not your list!');
if(isset($_POST['default'])) {
    $query = $GLOBALS['db']->prepare('UPDATE lists SET `default` = 0 WHERE `user` = ?');
    $query->execute([$GLOBALS['user'][0]]);
    $query = $GLOBALS['db']->prepare('UPDATE lists SET `default` = ? WHERE `rowid` = ?');
    $query->execute([($_POST['default'] == 'true') ? 1 : 0, $list_array[0]]);
} elseif(isset($_POST['enabled'])) {
    $query = $GLOBALS['db']->prepare('UPDATE lists SET `enabled` = ? WHERE `rowid` = ?');
    $query->execute([($_POST['enabled'] == 'true') ? 1 : 0, $list_array[0]]);
} elseif(isset($_POST['mode'])) {
    $query = $GLOBALS['db']->prepare('UPDATE lists SET `mode` = ? WHERE `rowid` = ?');
    $query->execute([($_POST['mode'] == 'true') ? 1 : 0, $list_array[0]]);
} elseif(isset($_POST['types']) || isset($_POST['filters']) || isset($_POST['products'])) {
    $type_list = $GLOBALS['db']->query('SELECT * FROM change_types ORDER BY id ASC', PDO::FETCH_NUM)->fetchAll();
    if(!isset($_POST['types'])) $_POST['types'] = [];
    if(!isset($_POST['filters'])) $_POST['filters'] = [];
    if(!isset($_POST['products'])) $_POST['products'] = [];

    function udiffCompare($a, $b) {
        return ($a == $b) ? 0 : 1;
    }

    $query = $GLOBALS['db']->prepare('SELECT change_type FROM list_triggers WHERE `list` = ?');
    $query->execute([$_POST['list']]);
    $triggers = $query->fetchAll(PDO::FETCH_COLUMN);
    foreach(array_udiff($_POST['types'], $triggers, 'udiffCompare') as $new_trigger) {
        $query = $GLOBALS['db']->prepare('INSERT INTO list_triggers(list,change_type) VALUES(?,?)');
        $query->execute([$list_array[0], $new_trigger]);
    }
    foreach(array_udiff($triggers, $_POST['types'], 'udiffCompare') as $old_trigger) {
        $query = $GLOBALS['db']->prepare('DELETE FROM list_triggers WHERE `list` = ? AND `change_type` = ?');
        $query->execute([$list_array[0], $old_trigger]);
    }

    $query = $GLOBALS['db']->prepare('SELECT * FROM list_filters WHERE `list` = ?');
    $query->execute([$_POST['list']]);
    $filters = $query->fetchAll(PDO::FETCH_NUM);
    foreach(array_udiff($_POST['filters'], $filters, 'udiffCompare') as $new_filter) {
        $new_filter[0] = $list_array[0]; #no thanks
        $query = $GLOBALS['db']->prepare('INSERT INTO list_filters(`list`,`from`,`to`,`operator`,`text`) VALUES(?,?,?,?,?)');
        $query->execute($new_filter);
    }
    foreach(array_udiff($filters, $_POST['filters'], 'udiffCompare') as $old_filter) {
        $old_filter[0] = $list_array[0]; #no thanks
        $query = $GLOBALS['db']->prepare('DELETE FROM list_filters WHERE `list` = ? AND `from` = ? AND `to` = ? AND `operator` = ? AND `text` = ?');
        $query->execute($old_filter);
    }

    $query = $GLOBALS['db']->prepare('SELECT productid FROM list_items WHERE `list` = ?');
    $query->execute([$_POST['list']]);
    $products = $query->fetchAll(PDO::FETCH_COLUMN);
    foreach(array_udiff($_POST['products'], $products, 'udiffCompare') as $new_item) {
        $query = $GLOBALS['db']->prepare('INSERT INTO list_items(`list`,`productid`) VALUES(?,?)');
        $query->execute([$list_array[0], $new_item]);
    }
    foreach(array_udiff($products, $_POST['products'], 'udiffCompare') as $old_item) {
        $query = $GLOBALS['db']->prepare('DELETE FROM list_items WHERE `list` = ? AND `productid` = ?');
        $query->execute([$list_array[0], $old_item]);
    }
};
$GLOBALS['db']->commit();
print("OK");