function deleteCategory(idCategory) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.CATEGORY.INDEX,
                {
                    IdCategory: idCategory ?? Url.Params.IdCategory
                },
                function (response, message) {

                    hideLoader();

                    // Manage page
                    if ("Params" in Url && "IdCategory" in Url.Params)
                        location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/category?st=ok&m=${message}`

                    renderTable();
                    notificationSuccess(message);
                },
                function (response, message) {
                    
                    hideLoader();
                    notificationError(message);
                }
            )
        }
    )
}