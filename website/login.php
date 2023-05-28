<div class="container h-100">
    <div class="h-100 min-vh-100 row justify-content-center align-items-center">
        <div class="col-lg-4 col-md-6 col-xs-8">
            <?php
            if(isset($_GET['r'])) {
                ?>
                <div class="alert alert-info text-center">
                <?php
                switch($_GET['r']) {
                    case '1':
                        print('registered, please log in');
                        break;
                    case '2':
                        print('bad invite');
                        break;
                    case '3':
                        print('reCAPTCHA failure!');
                        break;
                    case '4':
                        print('invalid user/pass');
                        break;
                    case '5':
                        print('username already taken');
                        break;
                    default:
                        print('???');
                }
                ?>
                </div>
                <?php
            }
            ?>
            <div class="alert alert-warning text-center">
                not logged in | <a href="register">register</a>
            </div>
            <form class="row card card-body" action="post" name="login" method="post">
                <div class="form-group">
                    <label for="username" class="label">username</label>
                    <input class="form-control" type="text" placeholder="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password" class="label">password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <div class="g-recaptcha" data-sitekey="6LfLNLEUAAAAABpkq8IhO0d4LdFn3FEYsxnvv2wc"></div>
                <div class="form-group mt-2">
                    <button type="submit" class="btn btn-primary form-control">submit</button>
                </div>
            </form>
        </div>
    </div>
</div>