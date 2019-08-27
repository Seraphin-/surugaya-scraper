<?php
require_once 'nav.php';
?>
<div class="container-fluid">
    <div class="m-auto">
        <div class="text-light"><h4>User Info</h4></div>
        <span class="text-light">User #: <pre class="d-inline text-light"><?= $GLOBALS['user'][0] ?></pre></span><br>
        <span class="text-light">Username: <pre class="d-inline text-light"><?= $GLOBALS['user'][1] ?></pre></span><br>
        <span class="text-light">Session IP: <pre class="d-inline text-light"><?= $GLOBALS['user'][4] ?></pre></span>
    </div>
    <form class="m-auto" id="settings">
        <div class="form-inline mt-1">
            <label for="email" class="badge badge-primary mr-1">Email</label>
            <input class="form-control" type="email" name="email" value="<?= $GLOBALS['user'][5] ?>">
        </div>
        <div class="form-inline mt-1">
            <label for="current_password" class="badge badge-primary mr-1">Current Password</label>
            <input class="form-control" type="password" name="current_password">
        </div>
        <div class="form-inline mt-1">
            <label for="new_password" class="badge badge-primary mr-1">New Password</label>
            <input class="form-control" type="password" name="new_password">
        </div>
        <div class="form-group mt-1">
            <button type="submit" class="btn btn-warning" id="update">Update Info</button>
            <span class="text-light ml-1" id="update_text"></span>
        </div>
    </form>
</div>
<script type="text/javascript">
    $(() => {
        $('#update').on('click', function (ev) {
            ev.preventDefault();
            $.post('/scraper-site/ajax/update_settings', {email: $('#settings input[name=email]').val(),
                current_password: $('#settings input[name=current_password]').val(),
                new_password: $('#settings input[name=new_password]').val()}, (r) => {
                $('#update_text').text(r).show().delay(1000).fadeOut("slow");
            });
        });
    });
</script>