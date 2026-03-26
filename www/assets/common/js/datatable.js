class AC_Datatable {
    constructor() {
        this.cleanProperties()
    }
    cleanProperties() {
        this.ajaxUrl = "";
        this.options = new Object();
        this.idTable = "dtItems";
        this.defaults = new Object();
        this.rowClickCallback = null;
        this.ajaxData = null;
        this.rowClickObject = "";
        this.rowClickFunction = "";
        this.rowReorderCallback = "";
        this.tableData = null;
        this.serverSideKey = "";
        this.initComplete = [];
    }
    setProperties() {
        // Load defaults properties
        this.defaults.dom = 'ftip';
        this.defaults.responsive = !(this.rowReorderCallback != null && this.rowReorderCallback != "");
        this.defaults.destroy = true;
        this.defaults.deferRender = true;
        this.initComplete = [];

        if (this.ajaxUrl != "") {
            // Get url
            var url = check_ajax_request_url(this.ajaxUrl);

            // Check if url is valid
            if (url === false) this.ajaxUrl = "";

            // Set url
            this.ajaxUrl = url.url;

            // Ajax
            this.defaults.ajax = new Object();
            this.defaults.ajax.url = this.ajaxUrl;
            this.defaults.ajax.dataSrc = "Response";
            this.defaults.ajax.cache = true;
            this.defaults.ajax.headers = new Object();
            this.defaults.ajax.headers["Datatable-Ajax-Call"] = true;
            this.defaults.ajax.headers = Object.assign(this.defaults.ajax.headers, url.headers);
            if (this.ajaxData != null) this.defaults.ajax.data = this.ajaxData;
        } else {
            // Data
            this.defaults.data = this.tableData;
        }

        // Language
        this.defaults.language = new Object();
        this.defaults.language.url = "/assets/backend/vendors/datatables/datatables.italian.json";

        // If table is Minimal
        if ($('#' + this.idTable).hasClass("is--minimal")) {
            this.defaults.searching = false;
            this.defaults.paging = false;
            this.defaults.ordering = false;
            this.defaults.info = false;
        }
        else if ($('#' + this.idTable).hasClass("is--basic")) {
            this.defaults.searching = false;
            this.defaults.bLengthChange = false;
            this.defaults.searching = false;
            this.defaults.paging = false;
            this.defaults.ordering = false;
            this.defaults.info = false;
        }

        // Check if sortable
        if (this.rowReorderCallback != null && this.rowReorderCallback != "")
            this.defaults.rowReorder = true;

        var isServerSide = false;

        // Override defaults properties with mine and add the new
        for (const property in this.options) {

            // If the property is columnDefs, check if has the required "targets" property
            if (property.toUpperCase() == "COLUMNDEFS") {
                for (let index = 0; index < this.options.columnDefs.length; index++) {
                    const element = this.options.columnDefs[index];

                    if (typeof (element.targets) === "undefined")
                        this.options.columnDefs[index].targets = index;

                    if ("header_filter" in element && typeof (element.header_filter) !== "undefined") {
                        var filter_select_property = false;

                        if (typeof element.header_filter == "string") {

                            filter_select_property = element.header_filter;

                            // check if has { for keywords
                            if (!filter_select_property.includes('{'))
                                filter_select_property = '{' + filter_select_property.trim() + '}';
                        }

                        if (filter_select_property !== false) {

                            this.options.columnDefs[index].orderable = false;

                            this.initComplete.push({
                                "Column": index,
                                "Property": filter_select_property
                            });
                        }
                    }
                }
            }

            if (property.toUpperCase() == "SERVERSIDE")
                isServerSide = true;

            this.defaults[property] = this.options[property];
        }

        if (this.ajaxUrl != "" && isServerSide)
            this.defaults.ajax.headers["Dt-Server-Side"] = this.serverSideKey;

        if (this.initComplete.length > 0) {

            // Get initComplete from user defined
            var default_init_complete = ("initComplete" in this.defaults) ? this.defaults.initComplete : null;

            var to_use = this.initComplete;

            var objInit = {
                initComplete: function (settings, json) {

                    for (let index = 0; index < to_use.length; index++) {
                        const ele = to_use[index];

                        var used = [];

                        var table = settings.oInstance.api();

                        table.column(ele.Column).every(function () {

                            var column = this;

                            // Crete select in header
                            var select = $('<select class="form-select"><option value="">' + $(column.header()).text() + '</option></select>')
                                .appendTo($(column.header()).empty())
                                .on("change", function () {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());

                                    column.search(val ? "^" + val + "$" : "", true, false).draw();
                                });

                            // Check if use a defined variable
                            if (ele.Property != "") {

                                // cycle all objects values
                                for (let index = 0; index < json.Response.length; index++) {
                                    const element = json.Response[index];

                                    var value = ele.Property;

                                    // cycle all properties
                                    for (const property in element) {

                                        value = value.replaceAll('{' + property + '}', element[property]);
                                    }

                                    if (used.indexOf(value) == -1) {
                                        select.append(
                                            '<option value="' + value + '">' + value + "</option>"
                                        );
                                        used.push(value);
                                    }
                                }

                            }
                        });


                    }

                    // Call the user defined init complete
                    if (default_init_complete)
                        default_init_complete(arguments[0], arguments[1]);

                }
            };

            this.defaults = Object.assign(this.defaults, objInit);
        }

        // Get drawCallback from user defined
        var default_draw_callback = ("drawCallback" in this.defaults) ? this.defaults.drawCallback : null;

        var objDraw = {
            drawCallback: function () {

                $(".paginate_button.page-item.previous").show();
                $(".paginate_button.page-item.next").show();
                $(".dataTables_paginate.paging_simple_numbers").show();

                // Show/Hide << and >> buttons
                if ($(".paginate_button.page-item.previous.disabled").length > 0)
                    $(".paginate_button.page-item.previous.disabled").hide();

                if ($(".paginate_button.page-item.next.disabled").length > 0)
                    $(".paginate_button.page-item.next.disabled").hide();

                // Check if only the number 1 is visibile
                if ($(".paginate_button.page-item:visible").length == 1)
                    $(".dataTables_paginate.paging_simple_numbers").hide();

                // Call the user defined init complete
                if (default_draw_callback)
                    default_draw_callback(arguments[0], arguments[1]);

            }
        };

        this.defaults = Object.assign(this.defaults, objDraw);

    }

    setAjaxUrl(value) {
        this.ajaxUrl = value;
        return this;
    }
    setAjaxData(key, value) {
        if (this.ajaxData == null) this.ajaxData = new Object();

        this.ajaxData[key] = value;
        return this;
    }
    setOptions(value) {
        this.options = value;
        return this;
    }
    setIdTable(value) {
        this.idTable = value.replace("#", "");;
        return this;
    }
    setData(value) {
        this.tableData = value;
        return this;
    }
    setRowClickCallback(callback) {
        this.rowClickCallback = callback;
        return this;
    }
    setReorderCallback(callback) {
        this.rowReorderCallback = callback;
        return this;
    }
    setServerSideKey(value) {
        this.serverSideKey = value;
        return this;
    }

    initDatatable() {
        this.setProperties();
        var table = null;
        window[this.idTable] = table = $('#' + this.idTable).DataTable(this.defaults);
        var instance = this;

        var callback = null;

        // Check if there's a row click callback
        if (this.rowClickCallback != null) {
            callback = this.rowClickCallback;

            // Table row click select, deselect row
            var last_child = '';
            if (!$('#' + this.idTable).hasClass('not--last'))
                last_child = ':not(:last-child)';

            $(document).on('click', '#' + this.idTable + ' tbody tr td' + last_child, function () {
                var data = table.row(this.closest("tr")).data();

                callback(this.closest("tr"), data);
            });
        }

        // Check if sortable
        if (this.rowReorderCallback != null && this.rowReorderCallback != "") {
            callback = this.rowReorderCallback;
            var idTable = this.idTable;

            // This will not work if you not add to each row the attribute "id"
            table.on("row-reorder", function (e, diff, edit) {
                // Wait for the dom to settle before doing anything
                setTimeout(function () {
                    var order = [];

                    // Get table rows
                    var rows = $("#" + idTable + " > tbody  > tr");

                    for (let index = 0; index < rows.length; index++) {
                        const row = rows[index];

                        var obj = {};
                        obj.id = $(row).attr("id");

                        // Check custom attribute
                        var attr = $(row).attr('custom');
                        if (typeof attr !== 'undefined' && attr !== false)
                            obj.Custom = attr;

                        obj.order_number = index + 1;
                        order.push(obj);
                    }

                    callback(order);
                }, 10);
            });
        }

        // Ajax error handler
        table.on("error.dt", function (e, settings, techNote, message) {

            // Check if error callback exists
            if (typeof ajax_call_callback !== "undefined")
                ajax_call_callback(null, null, null);

            // Remove "Loading..." message and show "No results"
            $(`#${instance.idTable}`).DataTable().clear().draw();
        })

        // On page change
        table.on('page.dt', function () {

            setTimeout(() => {

                // Scroll to page top
                if ($(`#${this.id}`).hasClass("change_page_scroll_top"))
                    document.body.scrollTop = document.documentElement.scrollTop = 0;

                // Scroll to table top
                else
                    $('html, body').animate({ scrollTop: $(`#${this.id}`).offset().top - 150 }, 200);

            }, 1000);

        });

        this.cleanProperties();

        return table;
    }
}

/**
 * Date formatting (d/m/Y)
 * Example
  columnDefs: [
    { 
      targets: 0, 
      type: 'date-uk'
    }
  ]
*/
jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    "date-uk-pre": function (a) {
        if (a == null || a == "") {
            return 0;
        }
        var ukDatea = a.split('/');
        return (ukDatea[2] + ukDatea[1] + ukDatea[0]) * 1;
    },

    "date-uk-asc": function (a, b) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "date-uk-desc": function (a, b) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
});


/**
 * Date formatting (d/m/Y H:i:s)
 * Example
  columnDefs: [
    { 
      targets: 0, 
      type: 'date-euro'
    }
  ]
*/
jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    "date-euro-pre": function (a) {
        var x;

        if ($.trim(a) !== '') {
            var frDatea = $.trim(a).split(' ');
            var frTimea = (undefined != frDatea[1]) ? frDatea[1].split(':') : [00, 00, 00];
            var frDatea2 = frDatea[0].split('/');
            x = (frDatea2[2] + frDatea2[1] + frDatea2[0] + frTimea[0] + frTimea[1] + ((undefined != frTimea[2]) ? frTimea[2] : 0)) * 1;
        }
        else {
            x = Infinity;
        }

        return x;
    },

    "date-euro-asc": function (a, b) {
        return a - b;
    },

    "date-euro-desc": function (a, b) {
        return b - a;
    }
});

/**
 * Date formatting (H:i:s)
 * Example
  columnDefs: [
    { 
      targets: 0, 
      type: 'time-uni'
    }
  ]
*/
jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    "time-uni-pre": function (a) {
        var uniTime;

        if (a.toLowerCase().indexOf("am") > -1 || (a.toLowerCase().indexOf("pm") > -1 && Number(a.split(":")[0]) === 12)) {
            uniTime = a.toLowerCase().split("pm")[0].split("am")[0];
            while (uniTime.indexOf(":") > -1) {
                uniTime = uniTime.replace(":", "");
            }
        } else if (a.toLowerCase().indexOf("pm") > -1 || (a.toLowerCase().indexOf("am") > -1 && Number(a.split(":")[0]) === 12)) {
            uniTime = Number(a.split(":")[0]) + 12;
            var leftTime = a.toLowerCase().split("pm")[0].split("am")[0].split(":");
            for (var i = 1; i < leftTime.length; i++) {
                uniTime = uniTime + leftTime[i].trim().toString();
            }
        } else {
            uniTime = a.replace(":", "");
            while (uniTime.indexOf(":") > -1) {
                uniTime = uniTime.replace(":", "");
            }
        }
        return Number(uniTime);
    },

    "time-uni-asc": function (a, b) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "time-uni-desc": function (a, b) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
});

/* Create an array with the values of all the input boxes in a column */
$.fn.dataTable.ext.order['dom-text'] = function (settings, col) {
    return this.api().column(col, { order: 'index' }).nodes().map(function (td, i) {
        return $('span', td).text().trim();
    });
}

// Disable custom error alert
$.fn.dataTable.ext.errMode = 'throw';