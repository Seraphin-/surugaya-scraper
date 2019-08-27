<?php
const SAFE_PAGES = ['login', 'register', 'logout', 'import_list'];
const VALID_INVITES = ['some_invite_code'];
header('Referrer-Policy: no-referrer');

function showAuth() {
    header('Location: /scraper-site/login');
    exit();
}
function head($page) {
    ?>
<!doctype html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/scraper-site/in/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T">
    <style type="text/css">
        body {
            background-color: #343a40;
        }
        .triangle-topright {
            width: 0;
            height: 0;
            border-top: 100px solid green;
            border-left: 100px solid transparent;
            z-index: 99;
        }
    </style>
    <script src="/scraper-site/in/jquery-3.4.1.min.js" integrity="sha384-vk5WoKIaW/vJyUAd9n/wmopsmNhiy+L2Z+SBxGYnUkunIxVxAv/UtMOhba/xskxh"></script>
    <script src="/scraper-site/in/popper.min.js" integrity="sha384-L2pyEeut/H3mtgCBaUNw7KWzp5n9+4pDQiExs933/5QfaTh8YStYFFkOzSoXjlTb"></script>
    <script src="/scraper-site/in/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"></script>
    <?php
    if(in_array($page, SAFE_PAGES)):
        ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php
    endif;
    ?>
    <title><?= $page ?></title>
</head>
<body>
    <?php
}
function footer() {
    ?>
</body>
</html>
    <?php
}

function process_post() {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => 'RECAPTCHA-SECRET',
        'response' => $_POST["g-recaptcha-response"]
    );
    $options = array(
        'http' => array (
            'method' => 'POST',
            'content' => http_build_query($data),
            'header' => "Content-Type: application/x-www-form-urlencoded"
        )
    );
    $context  = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $captcha_success=json_decode($verify);
    if(!$captcha_success->success) {
        header('Location: /scraper-site/login?r=3');
        exit();
    }
    $db = new PDO("sqlite:/path-to-db.db3");
    if(isset($_POST['invite'])) {
        if(!in_array($_POST['invite'], VALID_INVITES)) {
            header('Location: /scraper-site/login?r=2');
            exit();
        }
        if(empty($_POST['email'])) $_POST['email'] = null;
        $query = $db->prepare('SELECT * FROM users WHERE `name` = ?');
        $query->execute([$_POST['username']]);
        if($query->fetch()) {
            header('Location: /scraper-site/login?r=5');
            exit();
        }
        $query = $db->prepare('INSERT INTO users(`name`, `password`, `email`) VALUES (?,?,?)');
        $query->execute([$_POST['username'], password_hash($_POST['password'],  PASSWORD_DEFAULT), $_POST['email']]);
        header('Location: /scraper-site/login?r=1');
        exit();
    }
    #ok, must be login
    $query = $db->prepare('SELECT * FROM users WHERE `name` = ?');
    $query->execute([$_POST['username']]);
    $user = $query->fetch(PDO::FETCH_NUM);
    if(!$user || !password_verify($_POST['password'], $user[1])) {
        header('Location: /scraper-site/login?r=4');
        exit();
    }
    $query = $db->prepare( 'UPDATE users SET `session` = ?, `session_ip` = ? WHERE `name` = ?');
    require_once 'in/lib/random.php';
    $session_token = bin2hex(random_bytes(32));
    $query->execute([$session_token, $_SERVER['REMOTE_ADDR'], $user[0]]);
    setcookie('session', $session_token, 0, '/scraper-site/', $_SERVER['HTTP_HOST'], true, true);
    header('Location: /scraper-site/home');
    exit();
}

$GLOBALS['path'] = explode('?', basename($_SERVER['REQUEST_URI']))[0];
if($_SERVER['REQUEST_METHOD'] == "POST" && $GLOBALS['path'] == "post") process_post();

if($GLOBALS['path'] == 'scraper-site') $GLOBALS['path'] = 'home';
$user = false;
if(!in_array($GLOBALS['path'], SAFE_PAGES)) {
    if (!isset($_COOKIE['session'])) showAuth();
    $GLOBALS['db'] = new PDO("sqlite:/surugaya-site.db3");
    $query = $GLOBALS['db']->prepare('SELECT rowid, * FROM users WHERE `session` = ? AND `session_ip` = ?');
    $query->execute([$_COOKIE['session'], $_SERVER['REMOTE_ADDR']]);
    $GLOBALS['user'] = $query->fetch(PDO::FETCH_NUM);
    if (!$GLOBALS['user']) showAuth();
}
if(strpos($_SERVER['REQUEST_URI'], '/list/') !== false) {
    $GLOBALS['list'] = $GLOBALS['path'];
    $GLOBALS['path'] = 'list';
}
if(strpos($_SERVER['REQUEST_URI'], 'ajax') !== false) {
    require_once 'ajax/' . $GLOBALS['path'] . '.php';
} else {
    head($GLOBALS['path']);
    require_once $GLOBALS['path'] . '.php';
    footer();
}
