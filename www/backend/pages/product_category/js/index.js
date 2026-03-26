$(document).ready(function () {

    getCategories();
    cleanUrl();
    hideLoader();
});

function getCategories() {
    if ($('#categoriesTree').length > 0 && $('#li_category_template').length > 0) {
        showLoader();

        get_call(
            BACKEND.CATEGORY_PRODUCT.ALL,
            null,
            function (resp) {
                loadCategories(resp);
            },
            function () {
                hideLoader();
                notificationError("Qualcosa è andato storto, riprova!");
            }
        );
    } else
        endCategoriesLoad();
}

function cleanUrl() {
    setTimeout(() => {

        // Get querystring params
        const urlParams = new URLSearchParams(window.location.search);

        // Check if exists at least one
        if (urlParams.has('openTree') == false)
            return;


        // Check if exists r param
        if (urlParams.get('openTree')) {
            redirect = urlParams.get('openTree');
            urlParams.delete("openTree");
        }

        var new_url_params = [];

        // Rebuild url params
        for (const [key, value] of urlParams.entries())
            new_url_params.push(`${key}=${value}`)

        // Overwrite with the pathname (the url without query)
        window.history.replaceState({}, document.title, window.location.pathname + `?${new_url_params.join("&")}`);

    }, 500);
}

//#region list

function loadCategories(obj) {
    var categories = obj;

    var li_list = [];
    for (let index = 0; index < categories.length; index++) {
        const category = categories[index];

        // get li template
        var li = $('#li_category_template').html();

        // format category name
        var categoryName = category.Name;
        if (isEmpty(category.IdCategoryParent))
            categoryName = `<b>${categoryName}</b>`;

        if (category.IdCategoryParent == null)
            categoryName += ` <i class="fa fa-fw fa-check text-success"></i>`;

        buttonCreateStyle = '';
        if (category.Position >= 2)
            buttonCreateStyle = 'none';

        // Replace creation button
        li = li.replaceAll(`{{CreateButton}}`, buttonCreateStyle);
        
        // replace category name
        li = li.replaceAll(`{{CategoryName}}`, categoryName);


        // set delete options
        deleteDisabled = (category.HasChilds) ? 'disabled' : '';
        deleteAction = (category.HasChilds) ? '' : 'deleteCategory(' + category.IdCategory + ')';
        li = li.replaceAll(`{{DeleteDisabled}}`, deleteDisabled);
        li = li.replaceAll(`{{DeleteAction}}`, deleteAction);

        // replace all with categories properties
        for (const key in category) {
            const value = category[key];
            li = li.replaceAll(`{{${key}}}`, value);
        }

        // push li in final list
        li_list.push(li);
    }

    $('#categoriesTree').html(li_list.join(""));
    renderCategoriesTree($('#openTree').val());
    endCategoriesLoad();
}
function renderCategoriesTree(openTree = '') {
    if ($('#categoriesTree').length > 0) {
        showLoader();

        var open = ($('[name=IT-Name]').length > 0) ? true : false;

        // Init ac List
        new AC_List("#categoriesTree", open, openTree);

        hideLoader();
    }
}
function endCategoriesLoad() {

    if ($('#IsChild').length > 0) {
        if ($('#IsChild').val() == "0")
            initSortableAnswers();
        else
            getSavedAnswer();
    }
    else
        initSortableAnswers();

    if ($('#activeTabAttribute').val() == "1")
        renderAttributesTab();




}
function initSortableAnswers() {
    if ($('#accordion').length > 0)
        $('#accordion').sortable({
            cancel: ".form-group"
        });
}

$("body").on("click", ".js-openCloseIcon", function () {
    openCloseTreeSublevels(this);
});
$("body").on("click", ".item__title", function () {
    var el = $(this).parent().children("div.item__openclose").children("i");
    openCloseTreeSublevels(el);
});
function openCloseTreeSublevels(element) {
    $(element)
        .closest("li")
        .toggleClass("mjs-nestedSortable-collapsed")
        .toggleClass("mjs-nestedSortable-expanded");
    $(element)
        .toggleClass("fa-angle-right")
        .toggleClass("fa-angle-down");
}

//#endregion



//#region category

function createCategory(id_parent = null) {

    showLoader();

    post_call(
        BACKEND.CATEGORY_PRODUCT.INDEX,
        {
            IdParent: id_parent
        },
        function (response) {

            hideLoader();

            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product_category/${response}`;
        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");

        }
    )

}

//#endregion