<?php
$q = 'UPDATE users SET `email` = ?';
$params = [$_POST['email']];
if(!empty($_POST['current_password'])) {
    if(!password_verify($_POST['current_password'], $GLOBALS['user'][1])) die('Bad current password');
    $q .= ', `password` = ?';
    $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
}
$q .= ' WHERE `name` = ?';
$params[] = $GLOBALS['user'][0];
$query = $GLOBALS['db']->prepare($q);
if($query->execute($params)) {
    print("Success");
} else {
    print("Unknown failure");
}