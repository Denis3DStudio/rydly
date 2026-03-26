$(document).ready(function () {

    renderTable();
});

function renderTable() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.EMAIL.ALL)
        .setIdTable("dtEmails")
        .setOptions({
            order: [[2, 'desc']],
            columnDefs: [
                {
                    title: 'Info',
                    render: function (data, type, row) {

                        var text = `<b>Da:</b> ${row.Sender} <br>
                                    <b>A:</b> ${row.Receiver}`;
                            
                        // Check if the IsSent is not 1
                        if (row.IsSent == 0)
                            text += `<br><b>Tentativi:</b> ${row.Attempt}`;

                        return text;
                    }
                },
                {
                    title: 'Oggetto',
                    render: function (data, type, row) {
                        return row.Subject;
                    }
                },
                {
                    title: 'Data Inserimento',
                    type: "date-euro",
                    render: function (data, type, row) {
                        return fDate("d/m/Y H:i", row.InsertDate);
                    }
                },
                {
                    title: 'Data Invio',
                    type: "date-euro",
                    render: function (data, type, row) {
                        return !isEmpty(row.SentDate) ? fDate("d/m/Y H:i", row.SentDate) : "";
                    }
                },
                {
                    title: 'Stato',
                    render: function (data, type, row) {

                        if (row.IsSent == 1)
                            return `<span class="badge text-bg-success">Inviata</span>`;
                        else if (row.Attempt < 5)
                            return `<span class="badge text-bg-warning">In Coda</span>`;
                        else
                            return `<span class="badge text-bg-danger">Fallita</span>`;
                    }   
                },
                {
                    title: 'Azioni',
                    render: function (data, type, row) {

                        return `
                            <a class="btn btn-outline-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/email/${row.IdEmail}" tooltip="Vedi Email">
                                <i class="fa fa-fw fa-eye"></i>
                            </a>
                        `
                    }
                },
            ],
            initComplete: function (settings, json) {

                initTooltip();

                hideLoader();
            }
        })
        .initDatatable();
}

function approveEmail(idEmail) {

    confirmGenericModal(
        function () {

            post_call(
                BACKEND.EMAIL.APPROVE,
                {
                    IdEmail: idEmail
                },
                function () {

                    notificationSuccess("Email approvata con successo!");
                    renderTable();
                },
                function () {

                    notificationError("Qualcosa è andato storto!");
                }
            )
        },
        null,
        "Sei sicuro di approvare l'email?"
    );
}

function rejectEmail(idEmail) {

    confirmDeleteModal(

        function () {

            post_call(
                BACKEND.EMAIL.REJECT,
                {
                    IdEmail: idEmail
                },
                function () {

                    notificationSuccess("Email scartata con successo!");
                    renderTable();
                },
                function () {

                    notificationError("Qualcosa è andato storto!");
                }
            )
        }
        ,
        null,
        "Sei sicuro di scartare l'email?"
    );
}