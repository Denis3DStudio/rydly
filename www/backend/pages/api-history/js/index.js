$(document).ready(function () {
  renderTable();
});

// Get
function renderTable() {
  var dt = new AC_Datatable();
  dt.setAjaxUrl(BACKEND.API_HISTORY.INDEX)
    .setIdTable("dtHistory")
    .setOptions({
      dom: "Bftip",
      order: [[0, "desc"]],
      buttons: [
        {
          text: '<i class="fa fa-fw fa-sync"></i> Ricarica',
          className: "btn btn-sm btn-outline-primary",
          action: function (e, dt, node, config) {
            renderTable();
          },
        },
      ],
      columnDefs: [
        {
          title: "Data",
          type: "date-euro",
          render: function (data, type, row) {
            return fDate("d/m/Y H:i:s", row.Date);
          },
        },
        {
          title: "IP",
          header_filter: "IP",
          render: function (data, type, row) {
            return row.IP;
          },
        },
        {
          title: "Metodo",
          header_filter: "Method",
          render: function (data, type, row) {
            return row.Method;
          },
        },
        {
          title: "URI",
          render: function (data, type, row) {
            return `<span tooltip="${row.Uri}">${row.Uri.slice(0, 100)}</span>`;
          },
        },
      ],
      initComplete: function (settings, json) {
        hideLoader();
      },
      fnRowCallback: function (row, data, iDisplayIndex, iDisplayIndexFull) {
        if (data.Code != 200) $("td", row).addClass("bg-danger");
      },
    })
    .initDatatable();
}
