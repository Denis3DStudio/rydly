function deleteAttributeValue(idAttributeValue) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.ATTRIBUTE_VALUE.INDEX,
                {
                    IdAttribute: Url.Params.IdAttribute,
                    IdAttributeValue: idAttributeValue ?? Url.Params.IdAttributeValue
                },
                function () {

                    hideLoader();

                    // Check if the from page value is "product"
                    if ($('[name="from_page"]').length == 1 && $('[name="from_page"]').val() == "product")
                        window.top.close();
                    else {
                         
                        var message = "Valore dell'Attributo eliminato con successo!";
    
                        // Manage page
                        if ("Params" in Url && "IdAttributeValue" in Url.Params)
                            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute_value/${Url.Params.IdAttribute}?st=ok&m=${message}`
    
                        notificationSuccess(message);
                        renderTable();
                    }
                },
                function () {
                    hideLoader();

                    notificationError("Qualcosa è andato storto!");
                }
            )

        }
    )

}