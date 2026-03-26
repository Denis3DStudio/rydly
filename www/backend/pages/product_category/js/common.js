function deleteCategory(IdCategory) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.CATEGORY_PRODUCT.INDEX,
                {
                    IdCategory: IdCategory
                },
                function (IdParent) {

                    hideLoader();
                    notificationSuccess("Categoria eliminata con successo!");
                    location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product_category` + '?openTree=' + IdParent;
                    getCategories();
                },
                function () {
                    hideLoader();

                    notificationError("Errore eliminazione categoria");
                }
            )

        }
    )

}