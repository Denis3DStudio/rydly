global_k_table_bak = {};

// k-table.js
class KTable {

    //#region Properties

    #identifier = "#k-table";
    #containerIdentifier = null;
    #options = {
        ajax: {
            url: null,
            method: "GET",
            data: null,
        },
        columns: [],
        data: false,
        mode: null,
        sort: {},
        search: "",
        pagination: {
            page: 1,
            perPage: 10,
            slices: [10],
        },
        events: {},
        activeColumnVisibility: false,
        buttons: null,
        selectable: false,
        sortable: false,
        export: [],
        language: "it"
    };

    #kError = new KTableError();
    #kRender = null;
    #kEmitter = null;
    #kData = null;
    #kTranslation = null;

    //#endregion

    //#region Main

    constructor(identifier, options = null) {
        // Set
        this.#setIdentifier(identifier);
        this.#setOptions(options);
        this.#formatData();

        // Init translation
        this.#kTranslation = new KTableTranslation(this.#options.language);

        // Init events
        this.#kEmitter = new KTableEmitter(options.events);

        // Init data
        this.#kData = new KTableData(
            this.#options.ajax,
            this.#options.data,
            this.#options.pagination,
            this.#options.columns,
            this.#options.sort,
            this.#options.search,
            this.#options.sortable
        );
        this.#kData.kEmitter = this.#kEmitter;
        this.#kData.kError = this.#kError;

        // Init render
        this.#kRender = new KTableRender(this.#identifier, this.#containerIdentifier, this.#options);
        this.#kRender.kData = this.#kData;
        this.#kRender.kError = this.#kError;
        this.#kRender.kEmitter = this.#kEmitter;
        this.#kRender.kTranslation = this.#kTranslation;
        this.#kRender.rMain();

        // Set data
        this.#kData.kRender = this.#kRender;
        this.#kEmitter.kData = this.#kData;

        // Get data
        this.#initData(() => {
            // Check if error
            if (this.#kError.valid == false) {
                this.#kError.show();
                return;
            }
        });
    }

    //#endregion

    //#region Methods

    getSelected() {
        return this.#kData.selectedItems;
    }
    getLength(filtered = false) {
        return filtered == false ? this.#kData.itemsNumber : this.#kData.filteredItemsNumber;
    }
    isEmpty() {
        return this.#kData.itemsNumber == 0;
    }
    refresh(data = null) {

        // Check if data is set
        if (data != null)
            this.#kData.data = data;

        this.#initData(() => { });
    }
    sortableCompleted() {
        return this.#kRender.getSortedRowsIds();
    }
    exportCSV() {
        return this.#kData.exportCSV();
    }
    exportExcel() {
        return this.#kData.exportExcel();
    }

    //#endregion

    //#region Props

    #setIdentifier(identifier) {
        // Check if identifier is empty
        if (identifier == null || identifier == "")
            this.#kError.error = "Identifier is required";

        // Check if found table in page
        else if (
            document.querySelector(identifier) == null &&
            document.querySelector(`#${identifier}`) == null
        ) {
            this.#kError.error = "Table not found";

            identifier = null;
        }

        // Set identifier
        this.#identifier = identifier;

        // Generate hash from identifier in oneline
        this.#containerIdentifier = this.#hashString(this.#identifier);

        // Check if already exists and initialized
        if (document.querySelector(`[k-table-container-id='${this.#containerIdentifier}']`) != null) {

            // Get table backup
            let tableHTML = global_k_table_bak[this.#containerIdentifier];

            // Cast to element
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = tableHTML;

            // Get table element
            var newTableElement = tempDiv.firstElementChild;

            // Get target element
            var targetElement = document.querySelector(`[k-table-container-id='${this.#containerIdentifier}']`);

            // Insert new table after target element
            if (targetElement) targetElement.insertAdjacentElement("afterend", newTableElement);

            // Remove temporary div
            tempDiv.remove();

            // Remove container
            document.querySelector(`[k-table-container-id='${this.#containerIdentifier}']`).remove();

        }
        // New table - Add to global
        else
            global_k_table_bak[this.#containerIdentifier] = document.querySelector(this.#identifier).outerHTML;

    }

    #setOptions(options) {
        if (this.#kError.valid == false) return;

        // Check if mode is set
        if (typeof options.mode != "undefined" && options.mode != null) {

            // Check if mode is valid
            if (options.mode == "minimal") {

                options.search = false;
                options.pagination = false;

            }
        }

        // Check if sortable is active
        if (!options.sortable === false) {

            // Check if jQuery sortable exists
            if (typeof $.fn.sortable == "function") {

                // Disable pagination and search
                options.pagination = false;
                options.search = false;

            }
            else console.error("jQuery sortable is not loaded and is required for sortable tables");
        }

        // Check pagination
        if (options.pagination) {

            // If false, do not show paging
            if (!("slices" in options.pagination) || ("slices" in options.pagination && options.pagination.slices !== false)) {

                // If not declared or empty
                if (
                    !("slices" in options.pagination) ||
                    options.pagination.slices.length == 0
                )
                    options.pagination.slices = [10];

                // Check if not array
                else if (!Array.isArray(options.pagination.slices))
                    options.pagination.slices = [options.pagination.slices];

                // Sort slices
                options.pagination.slices = options.pagination.slices.sort((a, b) => a - b);
            }
            // Declare slices as empty array
            else options.pagination.slices = [];

            // Set perPage
            options.pagination.perPage = options.pagination.slices.length > 0 ? options.pagination.slices[0] : 10;

            // Check if page is set
            if (!("page" in options.pagination) || options.pagination.page == null)
                options.pagination.page = 1;
        }

        // Check sort
        if ("sort" in options && options.sort != null) {

            // Set to lowercase
            for (let key in options.sort) options.sort[key] = options.sort[key].toLowerCase();

        }

        // Check events
        if (!("events" in options) || options.events == null) options.events = {};

        // Uppercase export values
        if ("export" in options && Array.isArray(options.export))
            options.export = options.export.map((value) => value.toUpperCase());

        // Merge options with default and overwrites
        this.#options = {
            ...this.#options,
            ...options,
        };
    }

    //#endregion

    //#region Data

    #formatData() {

        // Check if table already has thead
        if (document.querySelector(`${this.#identifier} thead`) != null) {

            // Get columns from thead
            let columns = document.querySelectorAll(`${this.#identifier} thead th`);

            // Loop columns
            for (let i = 0; i < columns.length; i++) {
                let column = columns[i];

                // Get title
                let title = column.innerHTML;

                // Check if index exists in columns
                if (this.#options.columns[i] == null) this.#options.columns[i] = {};

                // Set title
                this.#options.columns[i].title = title;
            }

            // Remove thead
            document.querySelector(`${this.#identifier} thead`).remove();
        }

        // Check if table already has tbody
        if (document.querySelector(`${this.#identifier} tbody`) != null) {

            // Check if has values
            if (document.querySelectorAll(`${this.#identifier} tbody tr`).length > 0) {

                // Remove AJAX
                this.#options.ajax.url = null;

                // Get rows
                let rows = document.querySelectorAll(`${this.#identifier} tbody tr`);

                // Init data
                this.#options.data = [];

                // Loop rows
                for (let i = 0; i < rows.length; i++) {
                    let row = rows[i];

                    // Get columns
                    let columns = row.querySelectorAll("td");

                    // Init row
                    let r = [];

                    // Loop columns
                    for (let j = 0; j < columns.length; j++) {
                        let column = columns[j];

                        // Get value
                        let value = column.innerHTML;

                        // Set column value
                        r.push(value);

                        // Set render method
                        this.#options.columns[j].render = (data, index) => {
                            return data[index];
                        }
                    }

                    // Push row to data
                    this.#options.data.push(r);
                }

                // Remove tbody
                document.querySelector(`${this.#identifier} tbody`).remove();
            }

        }
    }

    #initData(callback) {
        if (this.#kError.valid == false) callback();

        // Get
        this.#kData.init(callback);
    }

    //#endregion

    //#region Utils

    #hashString(str) {

        let hash = 5381;

        for (let i = 0; i < str.length; i++) {
            hash = (hash * 33) ^ str.charCodeAt(i);
        }

        return (hash >>> 0).toString(36);

    }

    //#endregion
}

class KTableRender {

    //#region Properties

    kError;
    kEmitter;
    kData;
    kTranslation;

    #searchTimer;

    #identifier;
    #containerIdentifier;
    #rendered = false;
    #columnsDefs = [];
    #search;
    #pagination;
    #paging;
    #sort;
    #activeColumnVisibility;
    #selectable;
    #sortable;
    #buttons;
    #export;

    get columns() {
        return this.#columnsDefs.map((column) => column.title);
    }
    get hiddenIds() {
        return this.#columnsDefs
            .map((column, index) => {
                return "visible" in column && column.visible == false ? index : null;
            })
            .filter((column) => column != null);
    }
    get columnsNumber() {
        // Check if selectable is true
        var sum = this.#selectable === true ? 1 : 0;

        return this.columns.length + sum;
    }

    //#endregion

    //#region Classes

    #tableClasses = ["table", "k-table", "k-table-hover", "has--actions"];
    #containerClasses = ["k-table-container", "k-table-responsive-scrolling", "k-table-selectable", "k-table-sticky-header", "k-table-responsive-cards", "cards-scrolling"];

    //#endregion

    //#region Template Properties

    #TEMPLATES = {
        MAIN: {
            NAME: `script[k-table-template]#k-table-main-template`,
        },
        TABLE: {
            ANCHOR: ".k-table-table-container",
            NAME: `script[k-table-template]#k-table-table`,
        },
        ROW: {
            ID: `k-table-row-id`,
        },
        HEADER: {
            SELECT: `script[k-table-template]#k-table-header-select-filter`
        },
        SEARCH: {
            PARENT: ".k-table-search",
            NAME: `script[k-table-template]#k-table-search`,
        },
        PAGING: {
            PARENT: ".k-table-paging",
            NAME: `script[k-table-template]#k-table-paging-template`,
            ELEMENT: `.paging`
        },
        TOTAL_ITEMS: {
            PARENT: ".k-table-paging",
            NAME: `script[k-table-template]#k-table-total-items-template`,
            ELEMENT: `.visible-elements`
        },
        PAGINATION: {
            PARENT: ".k-table-pagination",
            NAME: `script[k-table-template]#k-table-pagination-template`,
        },
        BUTTONS: {
            PARENT: ".k-table-actions-left",
        },
        SELECTION: {
            PARENT: ".k-table-paging",
            NAME: `script[k-table-template]#k-table-selected-items-template`,
            ELEMENT: `.selected-rows-count`,
            CHECKBOX_ALL: `script[k-table-template]#k-table-checkbox-selectable-all`,
            CHECKBOX: `script[k-table-template]#k-table-checkbox-selectable`
        },
        SORTABLE: {
            NAME: `script[k-table-template]#k-table-icon-sortable`,
        },
        IMAGE: {
            BOX: `script[k-table-template]#k-table-k-box-image`,
        },
        EXPORT: {
            CSV: {
                TEMPLATE: `script[k-table-template]#k-table-export-csv-template`,
                SELECTOR: `[k-table-export-csv-button]`
            },
            EXCEL: {
                TEMPLATE: `script[k-table-template]#k-table-export-excel-template`,
                SELECTOR: `[k-table-export-excel-button]`
            }
        },
        LOADING: {
            NAME: `script[k-table-template]#k-table-loading-row`,
        },
        EMPTY: {
            NAME: `script[k-table-template]#k-table-empty-row`,
        },
        ERROR: {
            NAME: `script[k-table-template]#k-table-error-row`,
        },
    };

    //#endregion

    constructor(identifier, containerIdentifier, options) {
        this.#identifier = identifier;
        this.#containerIdentifier = containerIdentifier;
        this.#columnsDefs = options.columns;
        this.#search = options.search;
        this.#pagination = options.pagination;
        this.#paging = options.pagination.slices || [];
        this.#sort = options.sort;
        this.#activeColumnVisibility = options.activeColumnVisibility;
        this.#selectable = options.selectable;
        this.#sortable = options.sortable;
        this.#buttons = options.buttons;
        this.#export = options.export;
    }

    //#region Render

    rMain() {
        if (this.#rendered) return true;

        // Check identifier
        if (this.#identifier == null) {
            this.kError.error = "Identifier is required";
            return false;
        }

        // Get attributes from element
        let attributes = document.querySelector(this.#identifier).attributes;

        // Get thead from element
        let thead = document.querySelector(`${this.#identifier} thead`);

        // Insert after element
        this.#after(this.#TEMPLATES.MAIN.NAME, this.#identifier);

        // Check if error
        if (this.kError.valid == false) return false;

        // Get closest container
        let container = document.querySelector(this.#identifier).nextElementSibling;

        // Set container identifier to container
        container.setAttribute("k-table-container-id", this.#containerIdentifier);

        // Render table
        this.#after(
            this.#TEMPLATES.TABLE.NAME,
            this.#parentSelector(this.#TEMPLATES.TABLE.ANCHOR)
        );

        // Check if error
        if (this.kError.valid == false) return false;

        // Remove table anchor
        document.querySelector(this.#parentSelector(this.#TEMPLATES.TABLE.ANCHOR)).remove();

        // Remove table
        document.querySelector(this.#identifier).remove();

        // Set attributes
        for (let i = 0; i < attributes.length; i++) {
            let attribute = attributes[i];

            document
                .querySelector("[k-table-template-initial]")
                .setAttribute(attribute.name, attribute.value);
        }

        // Remove attribute k-table-template-initial from [k-table-template-initial]
        document
            .querySelector("[k-table-template-initial]")
            .removeAttribute("k-table-template-initial");

        // Set thead
        if (thead != null)
            document.querySelector(`table${this.#identifier}`).appendChild(thead);

        // Render
        this.#rColumns();

        // Check if error
        if (this.kError.valid == false) return false;

        // Set container's classes
        this.#rContainerClasses();

        this.#rendered = true;

        return true;
    }

    rAfterData() {
        if (this.rMain() == false) return;

        // Build components
        this.#rSearch();
        this.#rPaging();
        this.#rPagination();
        this.#rActiveColumnVisibility();
        this.#rButtons();
        this.#rHeaderFilter();

        // Render data
        this.rData();

        // Init sortable
        this.#initSortable();
    }

    #rContainerClasses() {

        // Get container
        let container = document.querySelector(`[k-table-container-id='${this.#containerIdentifier}']`);

        // Check if container exists
        if (container == null) return;

        // Get table classes
        let classes = document.querySelector(this.#identifier).classList;

        // Get container classes
        let cClasses = container.classList;

        // Merge classes
        classes = [...classes, ...cClasses];

        // Init table classes and container classes
        let tableClasses = [];
        let containerClasses = [];

        // Loop classes and divide them
        for (let i = 0; i < classes.length; i++) {
            let className = classes[i];

            if (this.#tableClasses.includes(className)) tableClasses.push(className);
            else if (this.#containerClasses.includes(className)) containerClasses.push(className);
        }

        // Set table's classes
        document.querySelector(this.#identifier).classList = tableClasses.join(" ");

        // Set container's classes
        container.classList = containerClasses.join(" ");

    }

    #rColumns(reload = false) {
        if (this.kError.valid == false) return;

        // Check if already have thead
        if (document.querySelector(`${this.#identifier} thead`) != null) {
            if (reload == false) {

                // Refresh select all checkbox
                this.#rSelectableCheckbox(document.querySelector(this.#parentSelector(".k-table-select-all .form-check-input")), this);

                return;
            }

            // Remove thead
            document.querySelector(`${this.#identifier} thead`).remove();
        }

        // Check if columns are set
        if (this.columns.length == 0) {
            this.kError.error = "Columns are required";
            return;
        }

        // Create thead
        let thead = document.createElement("thead");

        // Create tr
        let tr = document.createElement("tr");

        for (let i = 0; i < this.columns.length; i++) {
            let column = this.columns[i];

            // Check if is hidden
            var isHidden = this.hiddenIds.indexOf(i) > -1;

            // Create th
            let th = document.createElement("th");
            th.innerHTML = column;

            // Check if has description
            if ("description" in this.#columnsDefs[i] && this.#columnsDefs[i].description != "")
                th.innerHTML += ` <i class="fa fa-fw fa-info-circle" data-bs-toggle="tooltip" title="${this.#columnsDefs[i].description}"></i>`

            // If Hidden set display none
            if (isHidden) th.style.display = "none";

            // Check if is not hidden and the table is not sortable
            if (!isHidden && this.#sortable === false) {

                // Check if table has not class has--actions OR not last column and table has class has--actions
                if (
                    !document.querySelector(this.#identifier).classList.contains("has--actions")
                    ||
                    (i != this.columns.length - 1 && document.querySelector(this.#identifier).classList.contains("has--actions"))
                ) {

                    // Check filterable
                    if ("filterable" in this.#columnsDefs[i] && this.#columnsDefs[i].filterable == true) {

                        // Set filterable class
                        th.classList.add("has-filter-select");

                        // Render select filter
                        let selectFilter = document.querySelector(this.#TEMPLATES.HEADER.SELECT).innerHTML;

                        // Set column index
                        selectFilter = selectFilter.replace("$1", i);

                        // Set innerHTML
                        th.innerHTML = selectFilter;

                        // Create first option with the column name
                        let option = document.createElement("option");
                        option.value = "";
                        option.innerHTML = column;

                        // Add attribute default-column-name
                        option.setAttribute("default-column-name", true);

                        // Append option to select
                        th.querySelector("select").appendChild(option);

                    }
                    // Check sortable
                    else if (!("sortable" in this.#columnsDefs[i]) || this.#columnsDefs[i].sortable == true) {

                        // Set sortable class
                        th.classList.add("is-sortable");

                        // Check if sort is set
                        if (typeof this.#sort[i] != "undefined")
                            th.classList.add(`sort-${this.#sort[i]}`);

                        // Set click event
                        th.addEventListener("click", () => {
                            // Get column
                            let column = i;

                            // Get sort
                            var sort = this.#sort[column];

                            // Init value
                            var value = "asc";

                            // Check if sort is already set for column
                            if (typeof sort == "undefined")
                                // Clear sort
                                this.#sort = {};

                            // Set opposite sort
                            else
                                // Set value
                                value = sort.toLowerCase() == "asc" ? "desc" : "asc";

                            // Get columns with is-sortable
                            let ths = document.querySelectorAll(`table${this.#identifier} thead tr th.is-sortable`);

                            // Remove class sort-asc and sort-desc
                            for (let i = 0; i < ths.length; i++) {
                                ths[i].classList.remove("sort-asc");
                                ths[i].classList.remove("sort-desc");
                            }

                            // Add sort class
                            th.classList.add(`sort-${value}`);

                            // Set sort
                            this.#sort[column] = value;

                            // Show loading
                            this.rLoading();

                            setTimeout(() => {

                                // Call sort data
                                this.kData.sorting(column, value, () => {

                                    // Reload data
                                    this.rData();

                                    // Call emitter
                                    this.kEmitter.sorted();
                                });

                            }, 100);
                        })
                    }

                }

            }

            // Append th to tr
            tr.appendChild(th);
        }

        // Check if selectable is true
        if (this.#selectable === true) {
            // Create th
            let th = document.createElement("th");

            // Set innerHTML
            th.innerHTML = document.querySelector(this.#TEMPLATES.SELECTION.CHECKBOX_ALL).innerHTML;

            // Prepend th to tr
            tr.prepend(th);

            // Get checkbox
            let checkbox = tr.querySelector(".form-check-input");

            // Set click event on checkbox
            checkbox.addEventListener("click", (e) => {
                this.#rSelectableCheckbox(e.target, this);
            });
        }

        // Check if sortable is active
        if (!this.#sortable === false) {
            // Create th
            let th = document.createElement("th");

            // Set innerHTML
            th.innerHTML = "";

            // Prepend th to tr
            tr.prepend(th);
        }

        // Append tr to thead
        thead.appendChild(tr);

        // Append thead to table
        document.querySelector(`table${this.#identifier}`).appendChild(thead);

        // Init tooltips
        setTimeout(() => {

            // Init tooltips
            document.querySelectorAll(`table${this.#identifier} thead th[data-bs-toggle="tooltip"]`).forEach((tooltip) => {
                new bootstrap.Tooltip(tooltip);
            });

        }, 200);
    }

    rData() {
        if (this.rMain() == false) return;

        // Reload components after data
        this.#rFilterApplied();

        // Get data
        var data = this.kData.currentData;

        // Check if found some results
        if (this.kData.filteredItemsNumber == 0) {

            // Call emitter completed
            this.kEmitter.completed(this.kData.objects, data);

            // Render empty
            this.rEmpty();
            return;
        }

        // Get rows objects
        var rowsObject = this.kData.data[this.kData.pagination.page];

        // Clear table
        this.#clear();

        // Init length sum (the number of columns to add for special columns like selectable or sortable)
        var length_sum = 0;

        // Check selectable and sortable 
        if (this.#selectable === true) length_sum++;
        if (this.#sortable) length_sum++;

        // Loop data
        for (let i = 0; i < data.length; i++) {
            // Create tr
            let tr = document.createElement("tr");

            // Get row
            let row = data[i];

            // Get row object
            let rowObject = rowsObject[i];

            // Loop columns
            for (let j = 0; j < this.columns.length + length_sum; j++) {
                // Calculate column index
                var column = j - length_sum;

                // Create td
                let td = document.createElement("td");

                // Init value
                var value = row[column];

                // Check length sum == 2 (selectable and sortable)
                if (length_sum == 2 && j < 2) {

                    // First column is sortable
                    if (j == 0) value = document.querySelector(this.#TEMPLATES.SORTABLE.NAME).innerHTML;

                    // Second column is selectable
                    else if (j == 1) value = document.querySelector(this.#TEMPLATES.SELECTION.CHECKBOX).innerHTML;

                }
                // Only one (selectable or sortable)
                else if (length_sum == 1 && j == 0) {

                    // Check if sortable is true
                    if (this.#sortable) value = document.querySelector(this.#TEMPLATES.SORTABLE.NAME).innerHTML;
                    else if (this.#selectable === true) value = document.querySelector(this.#TEMPLATES.SELECTION.CHECKBOX).innerHTML;

                }
                else {

                    // Check if is hidden
                    var isHidden = this.hiddenIds.indexOf(column) > -1;

                    // If Hidden set display none
                    if (isHidden) td.style.display = "none";

                    // Check if is a column of image type
                    if ("image" in this.#columnsDefs[column] && this.#columnsDefs[column].image === true) {
                        // Set image
                        var image_html = document.querySelector(this.#TEMPLATES.IMAGE.BOX).innerHTML;
                        value = image_html.replace(/\$1/g, value);
                    }

                }

                // Set td innerHTML
                td.innerHTML = value;

                // Set attribute id
                tr.setAttribute(this.#TEMPLATES.ROW.ID, rowObject.identifier);

                // Append td to tr
                tr.appendChild(td);

            }

            // Check if selectable is true
            if (this.#selectable === true) {

                // Get checkbox
                let checkbox = tr.querySelector(".form-check-input");

                // Set checked to checkbox
                checkbox.checked = rowObject.selected;

                // Set click event on checkbox
                checkbox.addEventListener("click", (e) => {
                    this.#rSelectableCheckbox(e.target, this);
                });

            }

            // Append tr to tbody
            document.querySelector(`table${this.#identifier} tbody`).appendChild(tr);

            // Call emitter row created
            this.kEmitter.rowCreated(tr, i);
        }

        // Call emitter completed
        this.kEmitter.completed(this.kData.objects, data);
    }

    #rSearch() {
        if (this.kError.valid == false) return;

        // Check if search is false
        if (this.#search === false) {

            // Get container
            var container = document.querySelector(this.#parentSelector(this.#TEMPLATES.SEARCH.PARENT));

            // Remove search container
            if (container != null) container.remove();

            return;
        }

        // Insert search
        this.#insert(
            this.#TEMPLATES.SEARCH.NAME,
            this.#parentSelector(this.#TEMPLATES.SEARCH.PARENT)
        );

        // Get input
        let input = document.querySelector(
            `${this.#parentSelector()} input[k-table-search-input]`
        );

        // Check if search is set
        if (this.#search != "") {
            input.value = this.#search;

        }

        // Set keyup event on input debounce
        input.addEventListener("keyup", () => {
            // Get value
            let value = input.value.trim();

            // Check if value is the same
            if (this.#search == value) return;

            // Set searching value
            this.#search = value;

            // Clear timeout
            clearTimeout(this.#searchTimer);

            // Show loading
            this.rLoading();

            this.#searchTimer = setTimeout(() => {
                // Call emitter
                this.kEmitter.searching(value);

                // Get data
                this.kData.searching(value, () => {
                    // Render data
                    this.rData();

                    // Call emitter
                    this.kEmitter.searched(value);
                });
            }, 500);
        });
    }

    #rPaging() {
        if (this.kError.valid == false) return;

        // Check if paging is set
        if (this.#paging.length == 0)
            return;

        // Get element
        var pagingElement = document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGING.PARENT)} ${this.#TEMPLATES.PAGING.ELEMENT}`);

        // Check if paging element already exists
        var pagingElementExists = pagingElement != null;

        // Check if paging element already exists
        if (!pagingElementExists)
            // Insert paging
            this.#insert(
                this.#TEMPLATES.PAGING.NAME,
                this.#parentSelector(this.#TEMPLATES.PAGING.PARENT),
                false
            );

        // Set paging
        var slices = this.#paging;

        // Get paging numbers > total elements
        var greater = slices.filter(
            (value) => value >= this.kData.filteredItemsNumber
        );

        // Remove paging numbers > total elements except the first
        if (greater.length > 1) {
            slices = slices.filter(
                (value) => value < this.kData.filteredItemsNumber
            );
            slices.unshift(greater[0]);
        }

        // Get select
        let select = document.querySelector(
            `${this.#parentSelector(this.#TEMPLATES.PAGING.PARENT)} select`
        );

        // Clear options
        select.innerHTML = "";

        // Add options
        for (let i = 0; i < slices.length; i++) {
            let option = document.createElement("option");
            option.value = slices[i];
            option.innerHTML = slices[i];

            select.appendChild(option);
        }

        // Check if this.#pagination.perPage in slices
        if (slices.indexOf(this.#pagination.perPage) == -1) {

            // Set value
            select.value = slices[0];

            // Trigger change event
            setTimeout(() => {
                select.dispatchEvent(new Event("change"));
            }, 200);

        }

        // Set value
        else
            select.value = this.#pagination.perPage;

        // Set change event
        if (!pagingElementExists)
            select.addEventListener("change", (e) => {
                // Get value
                let value = e.target.value;

                // Set first page
                this.#pagination.page = 1;

                // Set pagination perPage
                this.#pagination.perPage = value;

                // Reload data
                this.kData.paging(value, () => {
                    this.#rPagination();
                    this.rData();
                });
            });

        // Get paging element
        pagingElement = document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGING.PARENT)} ${this.#TEMPLATES.PAGING.ELEMENT}`);

        // Check if to disable
        select.disabled = this.kData.filteredItemsNumber == 0 || (this.kData.filteredItemsNumber <= this.#pagination.perPage && slices.length == 1);
    }

    #rTotalItems() {
        if (this.kError.valid == false) return;

        // Get total items
        let totalItems = this.kData.totalItems;

        // Get element
        var totalElement = document.querySelector(`${this.#parentSelector(this.#TEMPLATES.TOTAL_ITEMS.PARENT)} ${this.#TEMPLATES.TOTAL_ITEMS.ELEMENT}`);

        // Check if total items element already exists
        if (totalElement == null)
            this.#insert(
                this.#TEMPLATES.TOTAL_ITEMS.NAME,
                this.#parentSelector(this.#TEMPLATES.TOTAL_ITEMS.PARENT),
                false
            );

        // Get totalItemsElement and filteredItemsElement
        let totalItemsElement = document.querySelector(
            `${this.#parentSelector()} [k-table-total-items]`
        );
        let filteredItemsContainerElement = document.querySelector(
            `${this.#parentSelector()} [k-table-filtered-items-container]`
        );
        let filteredItemsElement = document.querySelector(
            `${this.#parentSelector()} [k-table-filtered-items]`
        );

        // Set total items
        totalItemsElement.innerHTML = totalItems;

        var filteredItemsDisplay = "none";

        // Check if to render also filtered items
        if (this.kData.filteredItemsNumber != totalItems) {
            filteredItemsDisplay = "";

            // Set filtered items
            filteredItemsElement.innerHTML = this.kData.filteredItemsNumber;
        }

        // Set display
        filteredItemsContainerElement.style.display = filteredItemsDisplay;
    }

    #rPagination() {
        if (this.kError.valid == false) return;

        // Check if pagination is set
        if (this.#pagination === false) {
            // Get pagination container
            let paginationContainer = document.querySelector(this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT));

            // Remove pagination container
            if (paginationContainer != null) paginationContainer.remove();

            // Call to render data without emit
            // this.#rPaginationButtons(false);

            return;
        }

        // Clear pagination
        this.#clearParent(this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT));

        // Get pages number
        let pages = this.kData.pages;

        // Insert
        this.#insert(
            this.#TEMPLATES.PAGINATION.NAME,
            this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)
        );

        // Get select
        let select = document.querySelector(
            `${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} select`
        );

        // Add options
        for (let i = 0; i < pages; i++) {
            let option = document.createElement("option");
            option.value = i + 1;
            option.innerHTML = i + 1;

            select.appendChild(option);
        }

        // Set value
        select.value = this.#pagination.page;

        // Check if pages are 1
        select.disabled = pages == 1;

        // Call to render data as init
        this.#rPaginationButtons(false, false);

        // Set change event
        select.addEventListener("change", (e) => {
            this.#pagination.page = e.target.value;
            this.#rPaginationButtons();
        });

        // Set click event on first page button
        document
            .querySelector(
                `${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-first]`
            )
            .addEventListener("click", () => {
                this.#pagination.page = 1;
                this.#rPaginationButtons();
            });

        // Set click event on prev page button
        document
            .querySelector(
                `${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-prev]`
            )
            .addEventListener("click", () => {
                this.#pagination.page--;
                this.#rPaginationButtons();
            });

        // Set click event on next page button
        document
            .querySelector(
                `${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-next]`
            )
            .addEventListener("click", () => {
                this.#pagination.page++;
                this.#rPaginationButtons();
            });

        // Set click event on last page button
        document
            .querySelector(
                `${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-last]`
            )
            .addEventListener("click", () => {
                this.#pagination.page = pages;
                this.#rPaginationButtons();
            });
    }

    #rPaginationButtons(callback = true, emit = true) {
        // Get selected page
        let page = this.#pagination.page;

        // Check if exists pagination
        if (document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)}`) != null) {
            // Get last page number
            let lastPage = this.kData.pages;

            // Set selected page
            document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} select`).value = page;

            // Set buttons to disabled
            document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-first]`).disabled = page == 1;
            document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-prev]`).disabled = page == 1;
            document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-next]`).disabled = page == lastPage;
            document.querySelector(`${this.#parentSelector(this.#TEMPLATES.PAGINATION.PARENT)} [k-table-pagination-button-last]`).disabled = page == lastPage;
        }

        // Emit page changing
        if (emit) this.kEmitter.pageChanging(page);

        // Get page data
        this.kData.paginating(page, () => {

            // Check callback
            if (callback) this.rData();

            // Emit page changed
            if (emit) this.kEmitter.pageChanged(page);
        });
    }

    #rFilterApplied() {

        this.#rPaging();
        this.#rPagination();
        this.#rTotalItems();
        this.#rSelectableNumber();

    }

    #rActiveColumnVisibility() {
        if (this.kError.valid == false) return;

        // Empty k-table-column-visibility-dropdown
        document.querySelector(this.#parentSelector(`[k-table-column-visibility-dropdown]`)).innerHTML = "";

        // Check if activeColumnVisibility is false
        if (this.#activeColumnVisibility === false) {

            // Hide dropdown
            document.querySelector(this.#parentSelector(`.action-column-visibility.dropdown`)).style.display = "none";

            return;

        }

        // Build li
        for (let index = 0; index < this.columns.length; index++) {
            var column = this.columns[index];

            // Create li
            let li = document.createElement("li");

            // Build button inside li
            let button = document.createElement("button");

            // Add class dropdown-item
            button.classList.add("dropdown-item");

            // Check if is visibile and add class active
            if (this.hiddenIds.indexOf(index) == -1)
                button.classList.add("active");

            // Check if column is empty, if true set to -
            if (column.trim() == "") column = "-";

            // Set button innerHTML
            button.innerHTML = column;

            // Set click event
            button.addEventListener("click", () => {
                // Check if is hidden
                var isHidden = this.hiddenIds.indexOf(index) > -1;

                // Edit columnsDefs
                this.#columnsDefs[index].visible = isHidden;

                // Toggle active class on button
                button.classList.toggle("active");

                // Call to render columns
                this.#rColumns(true);

                // Call to render data
                this.rData();
            });

            // Append button to li
            li.appendChild(button);

            // Append li to dropdown
            document.querySelector("[k-table-column-visibility-dropdown]").appendChild(li);

        }

        // Show dropdown
        document.querySelector(this.#parentSelector(`.action-column-visibility.dropdown`)).style.display = "";
    }

    #rSelectableNumber() {
        if (this.kError.valid == false) return;

        // Check if selectable is false
        if (this.#selectable === false) return;

        // Add class to container
        document.querySelector(this.#parentSelector()).classList.add("k-table-selectable");

        // Get element
        var selectionElement = document.querySelector(`${this.#parentSelector(this.#TEMPLATES.SELECTION.PARENT)} ${this.#TEMPLATES.SELECTION.ELEMENT}`);

        // Check if selected items element already exists
        if (selectionElement == null)
            this.#insert(
                this.#TEMPLATES.SELECTION.NAME,
                this.#parentSelector(this.#TEMPLATES.SELECTION.PARENT),
                false
            );

    }
    #rSelectableCheckbox(checkbox, instance) {

        if (typeof checkbox == "undefined" || checkbox == null) return;

        // Check if checked
        var checked = checkbox.checked;

        // Get row index
        var index = checkbox.closest("tr").rowIndex - 1;

        // Get checkbox parent
        var parent = checkbox.parentElement;

        // Check if parent has class .k-table-select-all
        var isSelectAll = parent.classList.contains("k-table-select-all");

        // Get data
        instance.kData.selecting(index, checked, isSelectAll, (row) => {

            // Check if is select all
            if (!isSelectAll) {

                // Call emitter
                if (checked) instance.kEmitter.selected(row);
                else instance.kEmitter.unselected(row);

            }
            // Select/unselect all
            else
                document.querySelectorAll(instance.#parentSelector('[type="checkbox"].form-check-input')).forEach((checkbox) => {
                    checkbox.checked = checked;
                });

            // Get selected items number container
            var selectedItemsContainer = document.querySelector(instance.#parentSelector(instance.#TEMPLATES.SELECTION.ELEMENT));

            // Hide container
            if (selectedItemsContainer != null) selectedItemsContainer.style.display = "none";

            // Get selected items number
            var selectedItems = instance.kData.selectedItems.length;

            // Check if selected items
            if (selectedItems > 0) {

                // Set selected items number
                document.querySelector(instance.#parentSelector("[k-table-selected-items]")).innerHTML = selectedItems;

                // Show container
                selectedItemsContainer.style.display = "";

            }

            // Get select all checkbox
            var selectAllCheckbox = document.querySelector(instance.#parentSelector(".k-table-select-all > .form-check-input"));

            // Remove indeterminate prop
            selectAllCheckbox.indeterminate = false;

            // Check if nothing selected or at least one selected
            if (selectedItems == 0)
                selectAllCheckbox.checked = false;

            // Check if all selected
            else if (selectedItems == instance.kData.totalItems)
                selectAllCheckbox.checked = true;

            // Not all but at least one selected
            if (selectedItems > 0 && selectedItems < instance.kData.totalItems) {
                selectAllCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            }

        });

    }

    #rButtons() {
        if (this.kError.valid == false) return;

        // Check if buttons are set
        if (this.#buttons == null) return;

        // Check if export is active
        if (this.#export.length > 0) {

            var button_templates = [];

            // Check if contains "CSV"
            if (this.#export.indexOf("CSV") > -1)
                button_templates.push(this.#TEMPLATES.EXPORT.CSV.TEMPLATE);

            // Check if contains "XLS"
            if (this.#export.indexOf("XLS") > -1)
                button_templates.push(this.#TEMPLATES.EXPORT.EXCEL.TEMPLATE);

            // Get export buttons
            for (let index = 0; index < button_templates.length; index++) {
                const template = button_templates[index];

                // Get template
                var button_html = document.querySelector(template).innerHTML;

                // Add to buttons
                this.#buttons += button_html;

            }

            // Init buttons events
            $(document).on("click", this.#TEMPLATES.EXPORT.CSV.SELECTOR, () => {
                this.kData.exportCSV();
            });
            $(document).on("click", this.#TEMPLATES.EXPORT.EXCEL.SELECTOR, () => {
                this.kData.exportExcel();
            });
        }

        // Delete all in this.#TEMPLATES.BUTTONS.PARENT except .action-column-visibility
        document.querySelectorAll(`${this.#parentSelector(this.#TEMPLATES.BUTTONS.PARENT)} > :not(.action-column-visibility)`).forEach((element) => {
            element.remove();
        });

        // Prepend buttons
        this.#prepend(
            null,
            this.#parentSelector(this.#TEMPLATES.BUTTONS.PARENT),
            this.#buttons
        );

    }

    #rHeaderFilter() {

        // Get data from kData
        var data = this.kData.getFilterableData();

        // Check if data is empty
        if (data.length == 0) return;

        // Loop Object.keys
        for (let i = 0; i < Object.keys(data).length; i++) {
            var key_index = Object.keys(data)[i];

            // Get select element
            var select = document.querySelector(this.#parentSelector(`thead th.has-filter-select select[k-table-filter-select='${key_index}']`));

            // Check if select exists
            if (select == null) continue;

            // Get option with default-column-name
            var defaultOption = select.querySelector("option[default-column-name]").outerHTML;

            // Get data for the options
            var options = data[key_index];

            // Set options
            select.innerHTML = defaultOption + options.map((option) => `<option>${option}</option>`).join("");

            // Set change event
            select.addEventListener("change", (e) => {

                var obj = {};

                // Get selected option of this table
                var selects = document.querySelectorAll(this.#parentSelector(`thead th.has-filter-select select`));

                // Build object
                selects.forEach((select) => {

                    // Get options
                    var options = select.options;

                    // Get selected option
                    for (let index = 0; index < options.length; index++) {
                        const option = options[index];

                        // Check if selected and not has default-column-name
                        if (option.selected && !("default-column-name" in option.attributes)) {

                            // Get term
                            var term = option.text;

                            // Get index by attribute k-table-filter-select
                            var c_index = select.getAttribute("k-table-filter-select");

                            // Check if c_index exists
                            if (!(c_index in obj)) obj[c_index] = [];

                            // Set object
                            obj[c_index].push(term);
                        }
                    }

                });

                // Render data
                this.kData.filtering(obj, () => {
                    this.rData();
                });

            });
        }
    }

    rLoading() {
        if (this.rMain() == false) return;

        // Clear table
        this.#clear();

        // Insert loading
        this.#insert(
            this.#TEMPLATES.LOADING.NAME,
            `table${this.#identifier} tbody`
        );

        // Check if error
        if (this.kError.valid == false) {
            this.rError();
            return;
        }

        // Add colspan to row
        document
            .querySelector(
                `table${this.#identifier} tbody tr td[k-table-loading-row-td]`
            )
            .setAttribute("colspan", this.columnsNumber);
    }

    rEmpty() {
        if (this.rMain() == false) return;

        // Clear table
        this.#clear();

        // Insert loading
        this.#insert(this.#TEMPLATES.EMPTY.NAME, `table${this.#identifier} tbody`);

        // Check if error
        if (this.kError.valid == false) {
            this.rError();
            return;
        }

        // Add colspan to row
        document
            .querySelector(
                `table${this.#identifier} tbody tr td[k-table-empty-row-td]`
            )
            .setAttribute("colspan", this.columnsNumber);
    }

    rError() {
        if (this.rMain() == false) return;

        // Clear table
        this.#clear();

        // Insert error
        this.#insert(this.#TEMPLATES.ERROR.NAME, `table${this.#identifier} tbody`);

        // Selector
        let selector = `table${this.#identifier} tbody tr td[k-table-error-row-td]`;

        // Check if inserted and add colspan
        if (document.querySelector(selector) != null)
            document
                .querySelector(selector)
                .setAttribute("colspan", this.columnsNumber);
    }

    //#endregion

    //#region Methods

    #initSortable() {
        if (this.#sortable === false) return;

        // Init instance
        var instance = this;

        // Init sortable
        $(this.#parentSelector("tbody")).sortable({
            items: "tr", // Which items can be sorted
            handle: "[k-table-sortable-grip]", // Which element can be used to drag
            axis: "y",
            placeholder: "ui-state-highlight", // The class of the placeholder while dragging

            // Keep the placeholder height
            helper: function (e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    $(this).width($originals.eq(index).outerWidth());
                });
                return $helper;
            },
            start: function (event, ui) {
                // Set placeholder height to match the dragged row
                ui.placeholder.height(ui.item.height());
            },
            // Drag ended
            stop: function (event, ui) {

                // Get sorted rows
                var response = instance.getSortedRowsIds();

                // Call emit
                instance.kEmitter.sortableCompleted(response);

            }
        });

    }

    //#endregion

    //#region Utils

    #parentSelector(parent = "") {
        return `[k-table-container-id='${this.#containerIdentifier}'] ${parent}`.trim();
    }

    #insert(template, parent, override = true) {
        // Get template
        let templateElement = document.querySelector(template);

        // Check if template exists
        if (templateElement == null) {
            this.kError.error = `Template ${template} not found`;
            return;
        }

        // Get parent
        let parentElement = document.querySelector(parent);

        // Check if parent exists
        if (parentElement == null) {
            this.kError.error = `Parent ${parent} not found`;
            return;
        }

        // Get innerHTML
        let innerHTML = this.kTranslation.translate(templateElement.innerHTML);

        // Insert template
        if (override)
            parentElement.innerHTML = innerHTML;
        else
            parentElement.insertAdjacentHTML("beforeend", innerHTML);
    }
    #after(template, parent) {
        // Get template
        let templateElement = document.querySelector(template);

        // Check if template exists
        if (templateElement == null) {
            this.kError.error = `Template ${template} not found`;
            return;
        }

        // Get parent
        let parentElement = document.querySelector(parent);

        // Check if parent exists
        if (parentElement == null) {
            this.kError.error = `Parent ${parent} not found`;
            return;
        }

        // Get innerHTML
        let innerHTML = this.kTranslation.translate(templateElement.innerHTML);

        // Insert template
        parentElement.insertAdjacentHTML("afterend", innerHTML);
    }
    #prepend(template, parent, html = null) {

        if (html == null) {

            // Get template
            let templateElement = document.querySelector(template);

            // Check if template exists
            if (templateElement == null) {
                this.kError.error = `Template ${template} not found`;
                return;
            }

            html = templateElement.innerHTML;

        }

        // Get parent
        let parentElement = document.querySelector(parent);

        // Check if parent exists
        if (parentElement == null) {
            this.kError.error = `Parent ${parent} not found`;
            return;
        }

        // Get innerHTML
        let innerHTML = this.kTranslation.translate(html);

        // Insert template as the first child
        parentElement.insertAdjacentHTML("afterbegin", innerHTML);
    }

    #clearParent(parent) {
        let parentElement = document.querySelector(parent);

        if (parentElement != null) parentElement.innerHTML = "";
    }
    #clear() {
        document.querySelector(`table${this.#identifier} tbody`).innerHTML = "";
    }

    getSortedRowsIds() {

        // Get rows
        var rows = document.querySelectorAll(this.#parentSelector("tbody tr"));

        // Get ids
        var ids = Array.from(rows).map((row) => row.getAttribute(this.#TEMPLATES.ROW.ID));

        // Create instance
        var instance = this;

        // Get first (and only) page rows
        var rows = this.kData.data[this.kData.pagination.page];

        // Create array of objects with identifier and index
        return ids.map((id, index) => {

            // Create obj
            var obj = {
                OrderNumber: index + 1,
            };

            // Set default key
            var keys = ["Id"];

            // Init isDefault
            var isDefault = true;

            // Check if typeofSortable is string, array or object
            if (typeof this.#sortable == "object" || Array.isArray(this.#sortable) || typeof this.#sortable == "string") {

                // Check if sortable is an array
                if (Array.isArray(instance.#sortable))
                    keys = instance.#sortable;

                else if (typeof instance.#sortable == "string")
                    keys = [instance.#sortable];

                // Set isDefault to false
                isDefault = false;
            }

            // Loop keys
            keys.forEach((key) => {

                // Get row by identifier
                var rowData = rows.find((row) => row.identifier == id);

                // Check if rowData exists
                if (rowData == null)
                    return;

                // Check if isDefault
                if (isDefault)
                    obj[key] = rowData.identifier;

                else {

                    // Check if key exists in rowData.object
                    if (key in rowData.object)
                        obj[key] = rowData.object[key];

                    else if (key.toLowerCase() in rowData.object)
                        obj[key] = rowData.object[key.toLowerCase()];

                    else
                        obj[key] = null;

                }

            });

            return obj;
        });
    }

    //#endregion
}

class KTableEmitter {
    //#region Properties

    kData;

    #events = {
        searching: null,
        searched: null,
        sorted: null,
        sortableCompleted: null,
        selected: null,
        unselected: null,
        pageChanging: null,
        pageChanged: null,
        rowCreated: null,
        completed: null,
    };

    //#endregion

    constructor(events) {
        this.#setEvents(events);
    }

    //#region Events

    searching(value) {
        this.#emit("searching", value);
    }
    searched(value) {
        this.#emit("searched", value);
    }
    sorted() {
        this.#emit("sorted");
    }
    sortableCompleted(value) {
        this.#emit("sortableCompleted", value);
    }
    selected(row) {
        this.#emit("selected", row);
    }
    unselected(row) {
        this.#emit("unselected", row);
    }
    pageChanging(page) {
        this.#emit("pageChanging", page);
    }
    pageChanged(page) {
        this.#emit("pageChanged", page);
    }
    rowCreated(row, index) {
        this.#emit("rowCreated", row, index);
    }
    completed(data, rows) {
        this.#emit("completed", data, rows);
    }

    //#endregion

    //#region Utils

    #setEvents(events) {
        // Get events keys
        let classEvents = Object.keys(this.#events);
        let userEvents = Object.keys(events);

        // Check if user events are set
        if (userEvents.length == 0) return;

        // Check if user events are valid
        for (let i = 0; i < userEvents.length; i++) {
            let userEvent = userEvents[i];

            // Check if event is valid
            if (classEvents.includes(userEvent))
                this.#events[userEvent] = events[userEvent];
        }
    }

    #checkEvent(event) {
        return this.#events[event] != null;
    }

    #emit(...args) {
        // Get event
        let event = args[0];

        // Get other args
        let data = args.slice(1);

        // Check if event is valid
        if (this.#checkEvent(event)) this.#events[event](...data);
    }

    //#endregion
}

class KTableData {

    //#region Properties

    ajax;
    data = [];
    currentData = [];
    pagination;

    kRender;
    kEmitter;
    kError;

    totalItems = 0;

    #activeFilters = {
        search: null,
        paging: null,
    };
    search = null;
    #headerFilter = {};
    #sort = {};
    #sortable = {};

    #columnDefs = [];

    get selectedItems() {

        // Get flat data
        var data = this.#getFlatData();

        // Filter selected
        return data.filter((row) => row.selected).map((row) => row.object);
    }
    get pages() {
        // Get data keys and remove Hidden if exists
        let keys = Object.keys(this.data).filter((key) => key != "Hidden");

        // Return last key
        return keys[keys.length - 1] ?? 1;
    }
    get objects() {
        return this.#getFlatData().map((row) => row.object);
    }
    get itemsNumber() {
        return this.#getFlatData().length;
    }
    get filteredItemsNumber() {
        var response = 0;

        // Get data keys and remove Hidden if exists
        let keys = Object.keys(this.data).filter((key) => key != "Hidden");

        // Loop keys
        for (let i = 0; i < keys.length; i++) response += this.data[keys[i]].length;

        return response;
    }

    //#endregion

    constructor(ajax, data, pagination, columnDefs, sort, search, sortable) {
        this.ajax = ajax;
        this.data = data;
        this.#columnDefs = columnDefs;
        this.#sort = sort;
        this.#sortable = sortable;
        this.search = search;

        // Check if pagination is false
        if (pagination !== false)
            this.pagination = pagination;
        else
            this.pagination = {
                page: 1,
                perPage: data.length,
            };
    }

    init(callback) {
        // Call render for loading
        this.kRender.rLoading();

        // Get data
        setTimeout(() => {
            this.#getData(() => {
                // Check if error
                if (this.kError.valid == false) this.kRender.rError();
                else this.kRender.rAfterData();

                callback();
            });
        }, 100);
    }

    //#region Events

    searching(term, callback) {
        // Set search
        this.search = term;

        // Active filters
        this.#filterData();

        this.#setCurrentData(callback);
    }
    paging(perPage, callback) {
        // Set first page
        this.pagination.page = 1;

        // Set pagination perPage
        this.pagination.perPage = perPage;

        // Active filters
        this.#filterData();

        this.#setCurrentData(callback);
    }
    paginating(page, callback) {
        // Set page
        this.pagination.page = page;

        this.#setCurrentData(callback);
    }
    sorting(column, value, callback) {

        // Clear sort
        this.#sort = {};

        // Set sort
        this.#sort[column] = value;

        // Active filters
        this.#filterData();

        this.#setCurrentData(callback);

    }
    selecting(index, selected, all, callback) {

        // Check if all is true
        if (all) {

            // Get keys
            var keys = Object.keys(this.data);

            // Loop data
            for (let i = 0; i < keys.length; i++) {

                // Get key
                var key = keys[i];

                // Get row
                var rows = this.data[key];

                // Set selected to all rows
                for (let j = 0; j < rows.length; j++)
                    rows[j].selected = selected;
            }

            callback();

        } else {

            // Get row
            var row = this.data[this.pagination.page][index];

            // Set selected
            row.selected = selected;

            this.#setCurrentData(callback(row.object));

        }
    }
    filtering(terms, callback) {

        // Set
        this.#headerFilter = terms;

        // Active filters
        this.#filterData();

        this.#setCurrentData(callback);
    }
    exportCSV() {
        this.#export();
    }
    exportExcel() {
        this.#export(true);
    }

    //#endregion

    //#region Data

    #getData(callback) {

        // Check if ajax url is set
        if (this.ajax.url != null && this.ajax.url != "")
            this.#getAjaxData(callback);

        // Check if data is set
        else if (this.data) {
            // Init data
            this.#initData(this.data);

            callback();

            return;
        }
    }
    #getAjaxData(callback) {
        // Get data
        get_call(
            this.ajax.url,
            this.ajax.data,
            (response) => {
                // Init data
                this.#initData(response);

                // Callback
                callback();
            },
            (response, message) => {
                this.kError.error = message;

                callback();
            }
        );
    }

    #initData(data) {
        // Set total items
        this.totalItems = data.length;

        // Init data
        this.data = [];

        // Loop data
        for (let i = 0; i < data.length; i++)
            this.data.push(new KTableRow(this.#columnDefs, data[i], i, this.#sortable, this.#removeHTML));

        // Filter data to calculate sorting, searching and pagination
        this.#filterData();

        // Calculate current page
        this.#setCurrentData();
    }
    #filterData() {

        // Flatten data
        this.#flattenData();

        // Search data
        this.#searchData();

        // Header filter data
        this.#headerFilterData();

        // Sort data
        this.#sortData();

        // Paginate
        this.#paginateData();

    }
    #flattenData() {
        // Check if data is not an object
        if (typeof this.data != "object") return this.data;

        // Flatten data
        var response = this.#getFlatData();

        // Sort data
        this.data = response.sort((a, b) => a.orderNumber - b.orderNumber);
    }
    #searchData() {
        var response = this.data;

        // Check if search is empty
        if ((this.search == null || this.search == "") && this.#activeFilters.search == this.search) return response;

        // Set active filters
        this.#activeFilters.search = this.search;

        // Loop data
        for (let i = 0; i < response.length; i++)
            response[i].searchIn(this.search);

        // Set data
        this.data = response;
    }
    #headerFilterData() {
        var response = this.data;

        // Loop data
        for (let i = 0; i < response.length; i++) {
            // If already hidden by search, ignore
            if (!response[i].hiddenBySearch)
                response[i].filterIn(this.#headerFilter, this.#removeHTML);
        }

        // Set data
        this.data = response;
    }
    #sortData() {

        // Check if sort is empty
        if (Object.keys(this.#sort).length == 0) return;

        // Get columns
        var columns = Object.keys(this.#sort);

        // Get column
        var column = columns[0];

        // Get sort
        var sort = this.#sort[column];

        // Init instance
        var instance = this;

        // Sort data
        this.data = this.data.sort((a, b) => {
            // Get values
            var valueA = a.cells[column].value;
            var valueB = b.cells[column].value;

            // Format
            valueA = instance.#castToTimestamp(instance.#removeHTML(valueA));
            valueB = instance.#castToTimestamp(instance.#removeHTML(valueB));

            // Check if sort is asc
            if (sort == "asc") {
                if (valueA < valueB) return -1;
                if (valueA > valueB) return 1;
            }
            // Check if sort is desc
            else if (sort == "desc") {
                if (valueA > valueB) return -1;
                if (valueA < valueB) return 1;
            }

            return 0;
        });
    }
    #paginateData() {
        if (this.pagination === false) return;

        // Set perPage as int
        this.pagination.perPage = "perPage" in this.pagination ? parseInt(this.pagination.perPage) : this.totalItems;

        var response = {};
        response.Hidden = [];

        // Init page
        var page = 1;

        // Loop data
        for (let i = 0; i < this.data.length; i += 1) {
            // Get row
            var row = this.data[i];

            // Check if row is visible
            if (row.visible) {
                // Check if already exists page
                if (page in response) response[page].push(row);
                else response[page] = [row];

                // Check if to increment page
                if (response[page].length == this.pagination.perPage) page++;
            }
            else
                response.Hidden.push(row);
        }

        // Set paging active filters
        this.#activeFilters.paging = this.pagination.perPage;

        // Set data
        this.data = response;
    }

    #setCurrentData(callback = null) {

        var current = this.data;

        if (this.pagination !== false) {

            // Get current page as int
            var page = parseInt(this.pagination.page);

            // Check if page is valid
            if (page in this.data)
                this.pagination.page = page;

            // Set page 1
            else
                this.pagination.page = 1;

            // Check if index is valid
            if (!(this.pagination.page in this.data)) {
                this.currentData = [];

                // Call callback
                if (callback != null) callback();
                return;
            }

            // Get current data
            current = this.data[this.pagination.page];

        }

        // Reset
        this.currentData = [];

        // Loop current data
        for (let i = 0; i < current.length; i++)
            this.currentData.push(current[i].values);

        // Call callback
        if (callback != null) callback();
    }
    getFilterableData() {
        var response = {};

        // Get flat data
        var data = this.#getFlatData();

        if (data.length == 0) return response;

        // Get first row's cells
        var cells = data[0].cells;

        // Get filterable cells index
        var filterableCellsIndex = [];

        // Loop cells
        for (let i = 0; i < cells.length; i++) {
            // Get cell
            var cell = cells[i];

            // Check if cell is filterable
            if (cell.filterable) filterableCellsIndex.push(i);
        }

        // Check if there are filterable cells
        if (filterableCellsIndex.length == 0) return response;

        // Get filterable data
        for (let i = 0; i < data.length; i++) {
            // Get row
            var row = data[i];

            // Get values
            var values = row.cells.filter((cell, index) => filterableCellsIndex.includes(index)).map((cell) => cell.value);

            // Set data
            for (let index = 0; index < filterableCellsIndex.length; index++) {
                const key_index = filterableCellsIndex[index];

                if (response[key_index] == undefined) response[key_index] = [];

                response[key_index].push(values[index]);
            }
        }

        // Remove duplicates
        for (let key in response) {
            response[key] = [...new Set(response[key])];

            // Sort
            // response[key].sort();
        }

        return response;

    }

    //#endregion

    //#region Utils

    #getFlatData() {

        // Flatten data
        var response = [];

        // Check if data is not an object
        if (typeof this.data != "object") return response;

        // Init keys
        var keys = Object.keys(this.data);

        // Check if data is already an array and the keys are numbers consecutives
        let max = Math.max(...keys);
        let uniqueNumbers = new Set(keys);

        // There should be (max + 1) unique elements, and the sequence must start from 0
        // [0, 1, 2, 3, 4] > true
        // [0, 1, 3, 4] > false
        // [1, 2, 3, 4, 5] > false
        if (uniqueNumbers.size === max + 1 && Math.min(...keys) === 0)
            return this.data;

        // Loop data
        for (let i = 0; i < keys.length; i++) {
            // Get key
            var key = keys[i];

            // Get values
            var values = this.data[key];

            // Merge
            response = response.concat(values);
        }

        return response;

    }
    #removeHTML(value) {

        // Check if is html
        if (value == "" || /<([a-z][a-z0-9]*)\b[^>]*>(.*?)<\/\1>/i.test(value) == false)
            return value;

        // Strip html
        var div = document.createElement("div");
        div.innerHTML = value;
        value = div.textContent || div.innerText || "";
        div.remove();

        return value;
    }
    #castToTimestamp(value) {

        // Regex to match date
        const regex = /^(\d{2})\/(\d{2})\/(\d{4})[ T]?(\d{2}:\d{2}(:\d{2})?)?$/;

        if (value == "" || regex.test(value) == false)
            return value;

        // Normalize the string
        const normalizedStr = value.replace(regex, (_, dd, mm, yyyy, time) => {
            return `${yyyy}-${mm}-${dd}T${time || "00:00"}`;
        });

        // Create a new date object
        const date = new Date(normalizedStr);

        // Check if the date is valid
        if (!isNaN(date.getTime()))
            return date.getTime();

        return value;
    }

    //#endregion

    //#region Export

    #export(excel = false) {

        // Init instance
        var instance = this;

        // Get header names
        var headers = this.#columnDefs.filter(c => c.visible !== false).map(c => c.title);

        // Get values
        var rows = this.#getFlatData().map(r => r.cells.filter((c, i) => this.#columnDefs[i].visible !== false).map(c => instance.#removeHTML(c.value)));

        // Filename
        var ext = excel ? 'xlsx' : 'csv';
        var filename = `export-${new Date().toISOString().slice(0, 10)}-${new Date().toTimeString().slice(0, 8).replace(/:/g, '')}.${ext}`;

        // Check
        if (!rows || !rows.length) return;

        // Check if excel
        if (excel)
            this.#exportExcel(headers, rows, filename);

        else {

            // Merge headers and rows
            rows.unshift(headers);

            this.#exportCSV(rows, filename);
        }

    }
    #exportCSV(rows, filename) {

        var separator = ";";

        // Escape per CSV
        const escapeCSV = (v) => {
            const s = v == null ? '' : String(v);
            const mustQuote = s.includes('"') || s.includes('\n') || s.includes('\r') || s.includes(separator);
            const escaped = s.replace(/"/g, '""');
            return mustQuote ? `"${escaped}"` : escaped;
        };

        // First line with headers
        const lines = [];

        // Data lines
        for (const row of rows)
            lines.push(row.map(v => escapeCSV(v)).join(separator));

        // BOM for Excel UTF-8
        const content = '\uFEFF' + lines.join('\r\n');

        // Check MIME
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);

        // Create a link and click it to download
        const a = Object.assign(document.createElement('a'), { href: url, download: filename });
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);

    }
    #exportExcel(headers, rows, filename) {

        // Create data for each row with "key" + index as key
        const data = rows.map(row => {
            const obj = {};

            for (let index = 0; index < row.length; index++)
                obj[`key${index}`] = row[index].toString();

            return obj;
        });

        // Create schema
        var schema = [];

        // Create schema from headers
        for (let i = 0; i < headers.length; i++) {
            const header = headers[i];

            schema.push({
                column: header,
                type: String,
                value: (row) => row[`key${i}`]
            });
        }

        writeXlsxFile(data, {
            schema,
            fileName: filename
        });

    }

    //#endregion

}

class KTableError {
    #errors = [];

    set error(error) {
        this.#errors.push(error);
    }
    get valid() {
        return this.#errors.length == 0;
    }

    constructor() { }

    show() {
        // Join errors by -
        let errors = this.#errors.join(" - ");

        // Show error
        console.error(`KTable: ${errors}`);
    }
}
class KTableRow {

    identifier = null;
    search = "";
    cells = [];
    object = null;
    orderNumber = null;
    hiddenBySearch = false;
    hiddenByFilter = false;
    selected = false;

    get values() {
        // Get values
        let values = [];

        // Loop cells
        for (let i = 0; i < this.cells.length; i++) {
            // Get cell
            let cell = this.cells[i];

            // Push value
            values.push(cell.value);
        }

        return values;
    }

    get visible() {
        return !this.hiddenBySearch && !this.hiddenByFilter;
    }

    constructor(columnDefs, data, orderNumber, identifier_name, removeHtmlFunction) {

        // Set identifier
        this.identifier = typeof identifier_name == "boolean" || identifier_name == null || !(identifier_name in data) ? `${orderNumber}${this.#generateIdentifier()}` : data[identifier_name];
        this.identifier = this.identifier.toString().replace(/[^a-zA-Z0-9]/g, "");

        // Set object
        this.object = data;

        // Loop columns
        for (let i = 0; i < columnDefs.length; i++) {
            // Set order number
            this.orderNumber = orderNumber;

            // Get column
            var columnDef = columnDefs[i];

            // Init cell
            var cell = new KTableCell(columnDef, data, i);

            // Add cell to cells
            this.cells.push(cell);

            // Check if cell is searchable
            if (cell.searchable)
                this.search += ` ${removeHtmlFunction(cell.value)?.toString().toLowerCase()}`;
        }
    }

    //#region Methods

    clearFilters(row) {
        row.hiddenBySearch = false;
    }

    searchIn(search) {
        // Check if search is empty
        if (search == null || search == "") {
            this.hiddenBySearch = false;
            return;
        }

        // Check if cell search field is in search
        this.hiddenBySearch = !this.search.includes(search.toLowerCase());
    }

    filterIn(terms, removeHtmlFunction) {
        var hidden = false;

        Object.keys(terms).forEach((key) => {

            // Get values
            const values = terms[key];

            const hiddens = [];

            // Loop values
            for (let i = 0; i < values.length; i++) {
                const value = removeHtmlFunction(values[i]).toString().toLowerCase();

                if (value == "") continue;

                // Get cell
                var cell = this.cells[key];

                // Get value
                var cellValue = removeHtmlFunction(cell.value).toString().toLowerCase();

                // Check if cell value is not equal to term
                hiddens.push(cellValue != value);

            }

            // Check if all values are false
            if (hiddens.every((hidden) => hidden == true)) {
                hidden = true;
                return;
            }
        });

        // Check
        this.hiddenByFilter = hidden;
    }

    //#endregion

    //#region Utils

    /**
     * Alphanumeric uppercase 10 chars identifier generator
     * @returns {string} Returns the identifier
     */
    #generateIdentifier() {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 10; i++) {
            const randomIndex = Math.floor(Math.random() * characters.length);
            code += characters[randomIndex];
        }
        return code;
    }

    //#endregion
}
class KTableCell {
    value = null;
    searchable = true;
    orderable = true;
    visible = true;
    filterable = false;

    constructor(columnDef, data, index) {
        // Set value 
        this.value = "render" in columnDef ? columnDef.render(data, index) : (columnDef.title in data ? data[columnDef.title] : "");

        // Check if is a string and trim
        if (typeof this.value == "string") this.value = this.value.trim();

        // Set props
        this.searchable = "searchable" in columnDef ? columnDef.searchable : true;
        this.orderable = "orderable" in columnDef ? columnDef.orderable : true;
        this.visible = "visible" in columnDef ? columnDef.visible : true;
        this.filterable = "filterable" in columnDef ? columnDef.filterable : false;
    }
}

class KTableTranslation {

    //#region Properties

    #replaceable = {};
    #language = "it";
    #keyword = "TRANSLATION";
    #default = "IT";

    //#endregion

    //#region Translations

    #TRANSLATION = {
        IT: {
            SEARCH: {
                PLACEHOLDER: "Cerca..."
            },
            FULL_ROW: {
                NO_RESULTS: "Nessun risultato trovato",
                LOADING: "Caricamento in corso...",
                ERROR: "Errore durante il caricamento dei dati",
            },
            PAGING: {
                PER: "per",
                PAGE: "pagina",
            },
            TOTAL_ITEMS: {
                SHOWING: "Visualizzati",
                OF: "di",
            },
            SELECTED: {
                SELECTED: "Selezionati",
            },
            EXPORT: {
                CSV: "Esporta CSV",
                EXCEL: "Esporta Excel",
            }
        },
        EN: {
            SEARCH: {
                PLACEHOLDER: "Search..."
            },
            FULL_ROW: {
                NO_RESULTS: "No results found",
                LOADING: "Loading...",
                ERROR: "Error loading data",
            },
            PAGING: {
                PER: "per",
                PAGE: "page",
            },
            TOTAL_ITEMS: {
                SHOWING: "Showing",
                OF: "of",
            },
            SELECTED: {
                SELECTED: "Selected",
            },
            EXPORT: {
                CSV: "Export CSV",
                EXCEL: "Export Excel",
            }
        },
        ES: {
            SEARCH: {
                PLACEHOLDER: "Buscar..."
            },
            FULL_ROW: {
                NO_RESULTS: "No se encontraron resultados",
                LOADING: "Cargando...",
                ERROR: "Error al cargar los datos",
            },
            PAGING: {
                PER: "por",
                PAGE: "página",
            },
            TOTAL_ITEMS: {
                SHOWING: "Mostrando",
                OF: "de",
            },
            SELECTED: {
                SELECTED: "Seleccionados",
            }
        },
        FR: {
            SEARCH: {
                PLACEHOLDER: "Rechercher..."
            },
            FULL_ROW: {
                NO_RESULTS: "Aucun résultat trouvé",
                LOADING: "Chargement...",
                ERROR: "Erreur lors du chargement des données",
            },
            PAGING: {
                PER: "par",
                PAGE: "page",
            },
            TOTAL_ITEMS: {
                SHOWING: "Affichage",
                OF: "de",
            },
            SELECTED: {
                SELECTED: "Sélectionnés",
            },
            EXPORT: {
                CSV: "Exporter CSV",
                EXCEL: "Exporter Excel",
            }
        },
        DE: {
            SEARCH: {
                PLACEHOLDER: "Suchen..."
            },
            FULL_ROW: {
                NO_RESULTS: "Keine Ergebnisse gefunden",
                LOADING: "Wird geladen...",
                ERROR: "Fehler beim Laden der Daten",
            },
            PAGING: {
                PER: "pro",
                PAGE: "Seite",
            },
            TOTAL_ITEMS: {
                SHOWING: "Angezeigt",
                OF: "von",
            },
            SELECTED: {
                SELECTED: "Ausgewählt",
            },
            EXPORT: {
                CSV: "CSV exportieren",
                EXCEL: "Excel exportieren",
            }
        }
    };

    //#endregion

    constructor(language) {
        this.#language = language;
    }

    //#region Methods

    #buildReplaceable() {

        // Check if #replaceable is not empty
        if (Object.keys(this.#replaceable).length > 0) return this.#replaceable;

        // Get translation
        var translations = this.#language.toUpperCase() in this.#TRANSLATION ? this.#TRANSLATION[this.#language.toUpperCase()] : this.#TRANSLATION[this.#default];

        var response = {};

        // Loop TRANSLATION
        Object.keys(translations).forEach((key) => {
            // Get translation
            var obj = translations[key];

            // Get keys
            var keys = Object.keys(obj);

            // Loop keys
            for (let i = 0; i < keys.length; i++) {
                // Get key
                var k = keys[i];

                // Create response key
                var uppercase = `[${this.#keyword.toUpperCase()}.${key.toUpperCase()}.${k.toUpperCase()}]`;
                var lowercase = `[${this.#keyword.toLowerCase()}.${key.toLowerCase()}.${k.toLowerCase()}]`;

                // Set response
                response[uppercase] = obj[k];
                response[lowercase] = obj[k];
            }
        });

        // Set #replaceable
        this.#replaceable = response;

        // Return response
        return response;
    }

    translate(innerHTML) {

        // Get replaceable
        var replaceable = this.#buildReplaceable();

        // Loop replaceable
        Object.keys(replaceable).forEach((key) => {
            // Get value
            var value = replaceable[key];

            // Replace
            innerHTML = innerHTML.replaceAll(key, value);
        });

        // Return innerHTML
        return innerHTML;
    }

    //#endregion

}




//#region TODO

// Fixed Header
/*
(function () {
    function fixMe() {
        var table = document.querySelector(".k-table-sticky-header table.k-table");
        if (!table) return;

        var tableContainer = document.createElement("div");
        tableContainer.classList.add("k-stable-sticky-header-container");
        table.parentNode.insertBefore(tableContainer, table);
        var fixedTable = table.cloneNode(true);
        fixedTable.classList.add("k-table-fixed");
        fixedTable.querySelector("tbody").remove();
        tableContainer.appendChild(fixedTable);

        function resizeFixed() {
            var originalHeaders = table.querySelectorAll("th");
            var fixedHeaders = fixedTable.querySelectorAll("th");
            for (var i = 0; i < originalHeaders.length; i++) {
                fixedHeaders[i].style.width = originalHeaders[i].offsetWidth + "px";
            }
        }

        function scrollFixed() {
            var offset = window.scrollY || document.documentElement.scrollTop;
            var tableOffsetTop = table.offsetTop;
            var tableOffsetBottom =
                tableOffsetTop +
                table.offsetHeight -
                table.querySelector("thead").offsetHeight;
            if (offset < tableOffsetTop || offset > tableOffsetBottom) {
                fixedTable.style.display = "none";
            } else if (
                offset >= tableOffsetTop &&
                offset <= tableOffsetBottom &&
                fixedTable.style.display === "none"
            ) {
                fixedTable.style.display = "table";
            }
        }

        window.addEventListener("resize", resizeFixed);
        window.addEventListener("scroll", scrollFixed);
        resizeFixed();
        scrollFixed();
    }

    document.addEventListener("DOMContentLoaded", function () {
        fixMe();
    });
})();
*/

//#endregion