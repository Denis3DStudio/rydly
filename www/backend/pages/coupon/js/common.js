function deleteCoupon(idCoupon) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.COUPON.INDEX,
                {
                    IdCoupon: idCoupon ?? Url.Params.IdCoupon
                },
                function () {

                    hideLoader();

                    var message = "Buono sconto eliminato con successo!";

                    // Manage page
                    if ("Params" in Url && "IdCoupon" in Url.Params)
                        location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/coupon?st=ok&m=${message}`

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