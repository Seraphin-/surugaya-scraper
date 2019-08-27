<div class="container h-100">
    <div class="h-100 min-vh-100 row justify-content-center align-items-center">
        <div class="col-lg-4 col-md-6 col-xs-8">
            <div class="alert alert-warning text-center">
                invite only | <a href="login">login</a>
            </div>
            <form class="row card card-body" action="post" name="register" method="post">
                <div class="form-group">
                    <label for="username" class="label">username</label>
                    <input class="form-control" type="text" placeholder="RainHeaven" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password" class="label">password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="invite" class="label">invite code</label>
                    <input class="form-control" type="text" placeholder="d0nj0n" name="invite" required>
                </div>
                <div class="form-group">
                    <label for="email" class="label">email</label>
                    <input class="form-control" type="email" placeholder="optional" name="email">
                </div>
                <div class="g-recaptcha" data-sitekey="6LfLNLEUAAAAABpkq8IhO0d4LdFn3FEYsxnvv2wc"></div>
                <div class="form-group mt-2">
                    <button type="submit" class="btn btn-primary form-control">submit</button>
                </div>
            </form>
        </div>
    </div>
</div>