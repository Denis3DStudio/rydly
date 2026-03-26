class AC_List {
    //#region Constructor
    constructor(treeId, expanded = false, openTree = "") {
        this.cleanProperties();
        this.setTreeId(treeId);

        this.treeExpanded = expanded;
        this.openTree = openTree;

        this.buildTree();
    }
    //#endregion

    //#region Setters
    setTreeId(value) {
        if (value[0] == ".") {
            value = value.replace(".", "");
            this.identifierType = "class";
        }
        else
            value = value.replace("#", "");

        this.treeContainer = value;
    }
    //#endregion

    //#region Methods
    cleanProperties() {
        this.treeContainer = "";
        this.identifierType = "id";
        this.treeExpanded = false;
        this.openTree = "";
    }
    buildTree() {
        // Get container
        var container = (this.identifierType == "id") ? document.getElementById(this.treeContainer) : document.getElementsByClassName(this.treeContainer)[0];

        if (container != null) {
            // Get all li
            var items = Array.from(container.getElementsByTagName('li'));
            

            if (items != null && items.length > 0) {

                // Loop the tmp array
                for (let index = 0; index < items.length; index++) {
                    const element = items[index];

                    // Get parent-id attribute
                    var parent = element.getAttribute("parent-id");

                    // Get data-id attribute
                    var idItem = element.getAttribute("data-id");

                    // Hide btn close
                    element.getElementsByClassName('item__openclose')[0].style.display = "none";

                    // Macro category
                    if (parent == 0 || parent == undefined || parent == "undefined" || parent == null || parent == "null") {
                        // Insert ol
                        element.insertAdjacentHTML('afterend', '<ol id="item' + idItem + 'Childs"></ol>');
                    }

                    // Someone's child
                    else {
                        
                        // Get parent li
                        var parentLi = document.querySelector("[data-id='" + parent + "']");
                        
                        // Check if parent exists
                        if (parentLi != null) {

                            // Get parent container OL
                            var parentContainer = document.getElementById("item" + parent + "Childs");

                            // Check if parent container already exists
                            if (parentContainer == null) {
                                // Create parent container
                                parentLi.insertAdjacentHTML('afterend', '<ol id="item' + parent + 'Childs"></ol>');

                                // Get parent container OL
                                var parentContainer = document.getElementById("item" + parent + "Childs");
                            }

                            // Show folding btn to the parent
                            parentLi.getElementsByClassName('item__openclose')[0].style.removeProperty("display");

                            // Add class to the parent li
                            var classExp = (this.treeExpanded) ? 'expanded' : 'collapsed';
                            var classIcon = (this.treeExpanded) ? 'fa-angle-down' : 'fa-angle-right';

                            parentLi.classList.add("mjs-nestedSortable-" + classExp);
                            document.getElementById('collapseIcon' + parent).classList.add(classIcon);

                            // Move item to the parent OL
                            parentContainer.appendChild(element);

                        }
                    }
                }

                if (!isEmpty(this.openTree) && !this.treeExpanded) {

                    var to_open = this.openTree;
                    var found = false;

                    while (!found) {
                        var parent_id = $('[data-id=' + to_open + ']').attr('parent-id');

                        if (parent_id == 0 || parent_id == undefined || parent_id == "undefined" || parent_id == null || parent_id == "null")
                            found = true;
                        else {
                            to_open = parent_id;

                            var parent = $('[data-id=' + parent_id + ']');
                            if (parent != undefined && parent != null) {

                                // toggle classes
                                $(parent).removeClass("mjs-nestedSortable-collapsed");
                                $(parent).addClass("mjs-nestedSortable-expanded");

                                $('#collapseIcon' + parent_id).removeClass("fa-angle-right");
                                $('#collapseIcon' + parent_id).addClass("fa-angle-down");
                            }
                        }
                    }
                }

            }
        }
    }
    //#endregion
}