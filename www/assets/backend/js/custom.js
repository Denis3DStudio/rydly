var global = {
    LanguagesTabs: null,
    Languages: [],
};

$(document).ready(function () {
    navItemsVisibility();

    // Set toggle password visibility
    $(".js-passwordToggleView .btn").each(function () {
        $(this).on("click", function () {
            toggleVisibilityPassword(this);
        });
    });
});

//#region Theme and Tooltip

// Theme switch
var themeSwitch = document.getElementById("themeSwitch");
if (themeSwitch) {
    initTheme(); // on page load, if user has already selected a specific theme -> apply it

    themeSwitch.addEventListener("change", function (event) {
        resetTheme(); // update color theme
    });

    function initTheme() {
        var darkThemeSelected =
            localStorage.getItem("vthemeSwitch") !== null &&
            localStorage.getItem("vthemeSwitch") === "dark";
        // update checkbox
        themeSwitch.checked = darkThemeSelected;
        // update body data-theme attribute
        darkThemeSelected
            ? document.body.setAttribute("data-theme", "dark")
            : document.body.removeAttribute("data-theme");
    }

    function resetTheme() {
        if (themeSwitch.checked) {
            // dark theme has been selected
            document.body.setAttribute("data-theme", "dark");
            localStorage.setItem("vthemeSwitch", "dark"); // save theme selection
        } else {
            document.body.removeAttribute("data-theme");
            localStorage.removeItem("vthemeSwitch"); // reset theme selection
        }
    }
}

// Enable tooltips
const tooltipTriggerList = document.querySelectorAll(
    '[data-bs-toggle="tooltip"]'
);
const tooltipList = [...tooltipTriggerList].map(
    (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
);

//#endregion

//#region Sidebar collapse
document.addEventListener("DOMContentLoaded", function () {
    var navResizeButton = document.getElementById("js-navResizeButton");
    var navResize = document.getElementById("js-navResize");

    if (navResizeButton) {
        navResizeButton.addEventListener("click", function () {
            navResize.classList.toggle("is-hidden");

            if ($.fn.dataTable) {
                $(".dt-responsive").DataTable().columns.adjust();
            }
        });
    }
});
//#endregion

//#region input password toggle
function toggleVisibilityPassword(element) {
    var input = $(element).closest(".input-group").find("input");
    if (input.attr("type") === "password") {
        input.attr("type", "text");
        $(element).find("i").toggleClass("fa-eye-slash fa-eye");
    } else {
        input.attr("type", "password");
        $(element).find("i").toggleClass("fa-eye fa-eye-slash");
    }
}

//#endregion

//#region Languages Tabs

function checkLanguagesTabs(check_all_tabs = true, element_name = "tabLang") {
    // Use to check if there is at least one language inserted
    var at_least_one_inserted = false;
    // Use to check if all languages are valid
    var all_valid = true;

    // Init the response obj
    var response = {
        validity: true,
        Languages: [],
    };

    // Cycle all languages tabs
    $(`[name="${element_name}"]`).each((index) => {
        // Get the id of the container
        var tab_content_id = $($(`[name="${element_name}"]`)[index]).attr("id");
        var tab_id = $(`#${tab_content_id}`)
            .closest(".tab-pane")
            .attr("aria-labelledby");

        // Get the data inserted in the tab
        var data = getContentData(`#${tab_content_id}`);

        var to_check = false;

        // Remove the errors classes
        $(`#${tab_id}`).removeClass("bg-danger");
        $(`#${tab_content_id} .is-invalid`).removeClass("is-invalid");

        // Get the language of the tab
        var tab_language = $($(`[name="${element_name}"]`)[index]).attr("language");
        var mandatory_fields_container = `#${element_name}-${tab_language} [mandatory_fields_container]`;

        // Cycle all data and check if there is at least one value inserted
        Object.keys(data).forEach((key) => {
            // Get the value to check
            var values_to_check = data[key];

            // Check if the value is an array
            if (!Array.isArray(values_to_check)) {
                // Remove html tags from the value
                values_to_check = values_to_check.replace(/(<([^>]+)>)/gi, "").trim();

                // Check if the value to check is not null
                if (!isEmpty(values_to_check)) to_check = true;
            } else {
                // Cycle all values to check
                for (let index = 0; index < values_to_check.length; index++) {
                    let value_to_check = values_to_check[index];

                    // Remove html tags from the value
                    value_to_check = value_to_check.replace(/(<([^>]+)>)/gi, "").trim();

                    // Check if the value to check is not null
                    if (!isEmpty(value_to_check)) to_check = true;
                }
            }

            // Check if the value is empty
            if (to_check) at_least_one_inserted = true;
        });

        // Check if the tab is to check
        if (to_check == true || check_all_tabs == true) {

            // Set the validity of the content
            var validity = true;

            // Check the mandatory fields
            validity = checkMandatory(mandatory_fields_container);

            if (validity == false) {
                $(`#${tab_id}`).addClass("bg-danger");
                all_valid = false;
            } else {
                // Set the language in the data obj
                data["IdLanguage"] = tab_language;

                // Push the data
                response.Languages.push(data);
            }
        }
    });

    // Set the validity
    response.validity = all_valid && at_least_one_inserted;

    if (at_least_one_inserted == false)
        notificationError("Inserisci almeno una lingua");

    return response;
}

function getLanguagesTabs(
    template_id = "language_tab_template",
    prepend = true,
    tab_id = "nav-tab",
    nav_content_id = "nav-tabContent",
    language_tab_nav_template_id = "language_tab_nav_template",
    form_element_name = "tabLang"
) {
    getLanguages();

    // Check if the prepend is true
    if (prepend == true) {
        // Render tabs
        var template = new AC_Template();
        template
            .setContainerId(tab_id)
            .setTemplateId(language_tab_nav_template_id)
            .setObjects(global.Languages)
            .setPrepend(true)
            .renderView();

        // Remove the active nav tab
        $(`#${tab_id} .nav.nav-tabs .nav-item.active`).removeClass("active");

    } else {
        // Render tabs
        var template = new AC_Template();
        template
            .setContainerId(tab_id)
            .setTemplateId(language_tab_nav_template_id)
            .setObjects(global.Languages)
            .setAppend(true)
            .renderView();
    }

    // Render tabs content
    var template = new AC_Template();
    template
        .setContainerId(nav_content_id)
        .setTemplateId(template_id)
        .setObjects(global.Languages)
        .setPrepend(true)
        .renderView();

    // Check if the prepend is true
    if (prepend == true) {
        // Remove show tab content
        $(`#${nav_content_id} .tab-content .tab-pane`).removeClass("show").removeClass("active");

        // Set active tab
        if (typeof showFirstLanguageTab == "undefined" || showFirstLanguageTab) {

            // Set the first language tab as active
            $(`#${form_element_name}-${global.Languages[0].Language}`)
                .addClass("show")
                .addClass("active");

            // Set the first language nav tab as active
            $(`#${form_element_name}-${global.Languages[0].Language}-tab`).addClass("active");
        }
    }
}

function getLanguages() {

    // Get all languages
    const languages = ENUM.BASE_LANGUAGES.ALL;

    // Create a response array
    const response = [];

    // Cycle through all languages
    languages.forEach(language => {

        const obj = {};

        // Get IdLanguage
        obj.Language = Number(language);

        // Get the language abbreviation
        obj.LanguageLower = (language === ENUM.BASE_LANGUAGES.ENGLISH)
            ? "uk"
            : String(ENUM.BASE_LANGUAGES.ABBREVIATIONS[language]).toLowerCase();

        // Get the language name
        obj.LanguageName = ENUM.BASE_LANGUAGES.NAMES[language];

        // Push the object into the response array
        response.push(obj);
    });

    // Set in the global var
    global.Languages = response;
}

//#endregion

//#region Nav

function navItemsVisibility() {

    // Get navbar uls
    var uls = document.querySelectorAll("#navbarResponsive > ul");

    uls.forEach(ul => {

        // Remove nav-item with data-role
        navItemsRoles(ul);

        // Clear empty nav-items
        navItemsEmpty(ul);

        // Set active nav-items
        navItemsActive(ul);

    });

}
function navItemsRoles(ul) {

    // Get account role
    var idRole = Logged != null && "IdRole" in Logged ? parseInt(Logged.IdRole) : null;

    // Check if idRole is null
    if (idRole == null) return;

    // Get elements with data-role attribute
    var items = ul.querySelectorAll("[data-role]");

    // Cicle all the items
    items.forEach((item) => {

        // Get data-role
        var roles = item.getAttribute("data-role").split(",").map(Number);

        // Check if role is not in Logged.IdRole
        if (roles.indexOf(idRole) == -1) item.remove();
    });

}
function navItemsEmpty(ul) {

    // Get elements with data-role attribute
    var items = ul.querySelectorAll(".nav-item");

    // Cicle all the items
    items.forEach((item) => {

        // Check if has class nav-title
        if (item.classList.contains("nav-title")) {

            // Get the next element
            var nextElement = item.nextElementSibling;

            // Check if there is no next element or the next element is another nav-title
            if (!nextElement || nextElement.classList.contains("nav-title"))
                item.remove();

        } else {
            // Check if the item is empty
            if (item.innerHTML.trim() == "") item.remove();
        }

    });

}
function navItemsActive(ul) {
    var valid = [];

    // Get current pathname
    var path = navItemsLinkFormat(location.pathname);

    // Get elements with href attribute
    var items = ul.querySelectorAll(".nav-link[href]");

    // Cicle all the items to get the valid ones
    items.forEach((item) => {

        // Get href
        var href = item.getAttribute("href");

        // Check if empty or #
        if (href == null || href == "#" || href == "") return;

        // Get pathname
        var pathname = navItemsLinkFormat(href);

        // Check if the path includes the url pathname
        if (path.includes(pathname))
            valid.push(item);

    });

    // Check if valid contains the exact path
    var exact = valid.filter((item) => {

        // Get url
        var href = item.getAttribute("href");

        // Get pathname
        var pathname = navItemsLinkFormat(href);

        // Check if the path is the same
        return path == pathname;
    });

    // If exact is not empty, replace valid with exact
    if (exact.length > 0)
        valid = exact;

    // Set active class
    valid.forEach((item) => {
        item.classList.add("active");

        // Get parent li
        var li = item.closest("li.nav-item");

        // Add class active to parent li
        li.classList.add("active");

        // Check if the nav is inside a collapse sidenav-second-level
        if (li.closest("ul.collapse.sidenav-second-level") != null) {

            // Get ul element
            var ul = li.closest("ul.collapse.sidenav-second-level");

            // Add class show
            ul.classList.add("show");

            // Get parent li
            var li = ul.closest("li.nav-item");

            // Get child a.nav-link
            var a = li.querySelector("a.nav-link");

            // Remove class collapsed and add active
            if (a != null) {
                a.classList.remove("collapsed");
                a.classList.add("active");
            }

        }
    });

}
function navItemsLinkFormat(url) {

    // Remove query string
    url = url.split("?")[0];

    // Check trailing slash
    if (!url.endsWith("/")) url += "/";

    return url;

}

//#endregion

//#region DEV

function clearAllCache() {
    showLoader();

    delete_call(
        BACKEND.UTILITY.CACHE,
        null,
        function () {
            hideLoader();
            location.reload();
        },
        function () {
            hideLoader();
            notificationError();
        }
    );
}
function logout() {
    post_call(BACKEND.ACCOUNT.LOGOUT, null, function () {
        // Redirect to login
        window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/login`;
    });
}

function simpleDelete(type, value = null, callback = null) {

    confirmDeleteModal(
        function () {

            showLoader();

            var ref_id = ENUM.BASE_SIMPLE_DELETE.REF_IDS[type];
            var api_endpoint_method = Object.keys(ENUM.BASE_SIMPLE_DELETE.CUSTOM_API_ENDPOINT_METHODS).includes(`${type}`) ? ENUM.BASE_SIMPLE_DELETE.CUSTOM_API_ENDPOINT_METHODS[type] : "INDEX";
            var params = {};
            params[ref_id] = value ?? Url.Params[ref_id];

            delete_call(
                BACKEND[ENUM.BASE_SIMPLE_DELETE.API_ENDPOINTS[type]][api_endpoint_method],
                params,
                function (response, message) {

                    hideLoader();

                    if (callback == null) {

                        // Manage page
                        if ("Params" in Url && ref_id in Url.Params)
                            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/${ENUM.BASE_SIMPLE_DELETE.INDEX_PAGES[type]}?st=ok&m=${message}`

                        renderTable();
                    }
                    else
                        callback(response, message);

                    notificationSuccess(message);
                },
                function (response, message) {

                    hideLoader();
                    notificationError(message);
                }
            )
        },
        null,
        ENUM.BASE_SIMPLE_DELETE.MODAL_QUESTIONS[type],
    );
}

//#endregion
