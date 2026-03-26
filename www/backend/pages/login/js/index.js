$(document).ready(function () {

    checkLogged();

    hideLoader();

});

$(document).on("keyup", "[name='Username'], [name='Password']", function (e) {
    if (e.keyCode == 13)
        login();
});

function login() {

    if (checkMandatory('#loginForm')) {

        showLoader();

        post_call(
            BACKEND.ACCOUNT.LOGIN,
            getContentData("#loginForm"),
            function (response) {

                // Call the Crypt API
                get_call(
                    BACKEND.ACCOUNT.CRYPT,
                    getContentData("#loginForm"),
                    function (remember_me) {

                        // Clear Remember Me
                        if (localStorage.getItem("RM") != null)
                            localStorage.clear("RM");

                        // Set Remember Me
                        localStorage.setItem("RM", remember_me);

                        // Redirect to dashboard
                        location.href = "r" in Url.Query ? Url.Query.r : `/${ENUM.BASE_KEYS.BACKEND_PATH}/dashboard/`;
                    },
                    function () {

                        hideLoader();

                        // Clear Remember Me
                        if (localStorage.getItem("RM") != null)
                            localStorage.clear("RM");
                        
                        notificationError("Utente non trovato");
                    }
                )

            },
            function () {

                hideLoader();

                notificationError("Utente non trovato");

            }
        )

    }

}
function checkLogged() {

    if (localStorage.getItem("RM") != null) {

        get_call(
            BACKEND.ACCOUNT.DECRYPT,
            {
                Crypted_string: localStorage.getItem("RM")
            },
            function (credentials) {
                $('[name=Username]').val(credentials.Username);
                $('[name=Password]').val(credentials.Password);
            }
        )

    }

}