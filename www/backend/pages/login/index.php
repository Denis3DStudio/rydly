<style>
    .content-wrapper {
        margin: 0;
    }

    .card-login {
        position: absolute;
        top: 40%;
        left: 0;
        right: 0;
        transform: translate(0, -60%);
        margin: 0 auto;
        max-width: 400px;
        width: 100%;
    }

    .card-login .form-group {margin-bottom:25px;}
</style>

<div class="card bg-transparent card-login mx-auto mt-5 border-0">
    <div class="card-header bg-transparent text-center border-0">
        <h4><?= SITE_NAME ?></h4>
    </div>
    <div class="card-body">
        <form id="loginForm" method="POST" class="form-signin">
            <div class="form-group">
                <div class="has-icon icon-left">
                    <input type="email" name="Username" class="form-control form-control-lg" autocomplete="new-password"
                        placeholder="Username" mandatory />
                    <i class="fa fa-fw fa-user"></i>
                </div>
            </div>
            <div class="form-group">
                <div class="has-icon icon-left">
                    <input type="password" name="Password" class="form-control form-control-lg"
                        autocomplete="new-password" placeholder="Password" mandatory />
                    <i class="fa fa-fw fa-key"></i>
                </div>
            </div>

            <div class="d-flex justify-content-center">
                <button type="button" class="btn btn-block btn-success btn-lg mt-4" onclick="login()">
                    Accedi
                </button>
            </div>
        </form>
    </div>
</div>