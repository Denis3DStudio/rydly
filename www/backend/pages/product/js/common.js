function deleteProduct(idProduct) {

    confirmDeleteModal(
        function () {

            delete_call(
                BACKEND.PRODUCT.INDEX,
                {
                    IdProduct: idProduct ?? Url.Params.IdProduct
                },
                function () {

                    var message = "Prodotto eliminato con successo!";

                    // Manage page
                    if ("Params" in Url && "IdProduct" in Url.Params)
                        location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product?st=ok&m=${message}`

                    notificationSuccess(message);
                    renderTable();
                },
                function () {
                    notificationError("Prodotto non eliminato...");
                }
            )
        }
    )

}