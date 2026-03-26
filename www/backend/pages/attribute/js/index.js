$(document).ready(function () {
  getLanguages();
  renderTable();
});

function renderTable() {
  var dt = new AC_Datatable();
  dt.setAjaxUrl(BACKEND.ATTRIBUTE.ALL)
    .setIdTable("dtCategories")
    .setOptions({
      columnDefs: [
        {
          title: "Testo",
          render: function (data, type, row) {
            return row.Text;
          },
        },
        {
          title: "Lingue",
          render: function (data, type, row) {
            var text = "";

            global.Languages.forEach((language) => {
              // Check if website has this language
              var disabled =
                row.LanguagesIds.indexOf(parseInt(`${language.Language}`)) > -1
                  ? ""
                  : "disabled";

              text += `<i class="flag flag-${language.LanguageLower.toLowerCase()} ${disabled} me-1" tooltip="${language.LanguageLower.toUpperCase()}"></i>`;
            });

            return text;
          },
        },
        {
          title: "Azioni",
          render: function (data, type, row) {
            // Set the action and the disabled attribute
            var dltBtnAction = row.CanDelete
              ? `deleteAttribute(${row.IdAttribute})`
              : "";
            var disabled = row.CanDelete ? "" : "disabled";

            return `
                <a class="btn btn-outline-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute_value/${row.IdAttribute}">
                    <i class="fa fa-fw fa-list"></i>
                </a>
                <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute/${row.IdAttribute}">
                    <i class="fa fa-fw fa-edit"></i>
                </a>
                <button type="button" class="btn btn-link text-danger" onclick="${dltBtnAction}" ${disabled}>
                    <i class="fa fa-fw fa-trash"></i>
                </button>
            `;
          },
        },
      ],
      initComplete: function (settings, json) {
        hideLoader();
      },
    })
    .initDatatable();
}

function createNewAttribute() {
  showLoader();

  post_call(
    BACKEND.ATTRIBUTE.INDEX,
    null,
    function (response) {
      hideLoader();

      // Change the location to index page
      window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute/${response}`;
    },
    function () {
      hideLoader();

      notificationError("Qualcosa è andato storto!");
    }
  );
}
