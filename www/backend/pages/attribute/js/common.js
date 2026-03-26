function deleteAttribute(idAttribute) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.ATTRIBUTE.INDEX,
                {
                    IdAttribute: idAttribute ?? Url.Params.IdAttribute
                },
                function () {

                    hideLoader();

                    var message = "Attributo eliminato con successo!";

                    // Manage page
                    if ("Params" in Url && "IdAttribute" in Url.Params)
                        location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute?st=ok&m=${message}`

                    notificationSuccess(message);
                    renderTable();
                },
                function () {
                    hideLoader();

                    notificationError("Qualcosa è andato storto!");
                }
            )

        }
    )

}