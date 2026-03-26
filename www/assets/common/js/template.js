class AC_Template {
    constructor() {
        this.cleanProperties()
    }
    cleanProperties() {
        this.templateId = "";
        this.emptyTemplateId = "";
        this.containerId = "";
        this.objectIdentifier = "";
        this.pageIndex = 1;
        this.extraRules = [];
        this.elementAttributes = {};
        this.objects = [];
        this.api = "";
        this.append = false;
        this.prepend = false;
        this.after = false;
        this.return = false;
        this.cleaner = [];
        this.replaceEmpty = false;
    }

    //#region Setters
    setTemplateId(value) {
        this.templateId = value.replace("#", "");
        return this;
    }
    setEmptyTemplateId(value) {
        this.emptyTemplateId = value.replace("#", "");
        return this;
    }
    setContainerId(value) {
        this.containerId = value;
        return this;
    }
    setObjectIdentifier(value) {
        this.objectIdentifier = value;
        return this;
    }
    setPageIndex(value) {
        this.pageIndex = value;
        return this;
    }
    setExtraRules(value) {
        this.extraRules = value;
        return this;
    }
    setObjects(value) {
        this.objects = (!Array.isArray(value)) ? [value] : value;
        return this;
    }
    setApi(value) {
        this.api = value;
        return this;
    }
    setAppend(value) {
        this.append = value;
        return this;
    }
    setPrepend(value) {
        this.prepend = value;
        return this;
    }
    setAfter(value) {
        this.after = value;
        return this;
    }
    setReturn(value) {
        this.return = value;
        return this;
    }
    setReplaceEmpty(value) {
        this.replaceEmpty = value;
        return this;
    }
    //#endregion

    //#region Functions
    replaceWithAttributes(text) {
        if (text != undefined && text != "" && text != null) {
            // Replace attributes with values
            for (var attribute in this.elementAttributes) {
                var toReplace = "{{" + attribute + "}}";

                var re = new RegExp(toReplace, 'ig');

                if (text.match(re)) {
                    var val = this.elementAttributes[attribute];

                    // This is an html_entity_decode equivalent
                    val = $('<textarea />').html(val).text();

                    text = text.replace(re, val);
                }
            }
        }

        // Replace all {{*}} with empty string
        if ($("#is_prod").val() == 1 || this.replaceEmpty)
            // Replace all che {{*}} with empty string
            text = text.replace(/{{.*?}}/g, '');

        return text ?? '';
    }
    applyExtraRules(text) {
        for (var r in this.extraRules) {
            var rule = this.extraRules[r];
            var attr = this.elementAttributes[r];

            for (var prop in rule) {
                if (prop == attr.toString()) {
                    // Generate random id
                    var random = Math.round(Math.random() * 1000000) + "-" + Math.round(Math.random() * 1000000);
                    var id = "template-extra-rule-" + random;

                    var act = this.replaceWithAttributes(rule[prop]);
                    text += "<script id='" + id + "'>" + act + "</script>";

                    // Insert into cleaner array
                    this.cleaner.push(id);

                    break;
                }
            }
        }

        return text;
    }
    buildRowTemplate() {
        var tmp = $('#' + this.templateId).html();

        if (tmp != null && tmp != '' && tmp != undefined) {
            tmp = this.replaceWithAttributes(tmp);
            tmp = this.applyExtraRules(tmp);
        }

        return tmp ?? '';
    }
    insertCleanerScripts() {
        this.cleaner.forEach(clean => {
            $('#' + clean).remove();
        });
    }
    //#endregion

    //#region Render
    renderView(pageIndex = null) {
        if (pageIndex != null) this.pageIndex = parseInt(pageIndex);

        if (this.templateId != "" && (this.containerId != "" || this.return)) {
            var res = "";
            var tmp_attrs = {};

            // From API
            if (this.api != "") {
                var template_class = this;

                get_call(
                    this.api,
                    null,
                    function (data) {
                        var results = data;
                        if (!Array.isArray(results)) results = [results];

                        if (results.length > 0) {
                            for (let index = 0; index < results.length; index++) {
                                const element = results[index];

                                template_class.elementAttributes = element;

                                // Append Result
                                res += template_class.buildRowTemplate();
                            }
                        } else {
                            res = $('#' + template_class.emptyTemplateId).html();
                        }

                        return template_class.realRenderView(res);
                    }
                );
            }
            else {

                // From in page elements by identifier
                if (this.objectIdentifier != "") {

                    // Get from objects where PageIndex attribute is 
                    var objs = $(this.objectIdentifier + "[data-PageIndex=" + this.pageIndex + "]");

                    if (objs.length > 0) {
                        // Create elements
                        for (let index = 0; index < objs.length; index++) {
                            const element = objs[index];

                            // Loop all attributes
                            $(element).each(function () {
                                $.each(this.attributes, function () {
                                    // this.attributes is not a plain object, but an array
                                    // of attribute nodes, which contain both the name and value
                                    if (this.specified && this.name.indexOf("data-") !== -1) {
                                        tmp_attrs[this.name.replace("data-", "")] = this.value;
                                    }
                                });
                            });

                            this.elementAttributes = tmp_attrs;

                            // Append Result
                            res += this.buildRowTemplate();
                        }
                    } else {
                        res = $('#' + this.emptyTemplateId).html();
                    }
                }

                // From passed objects
                else if (this.objects.length > 0) {
                    for (let index = 0; index < this.objects.length; index++) {
                        const element = this.objects[index];

                        this.elementAttributes = element;

                        // Append Result
                        res += this.buildRowTemplate();
                    }
                }

                // Source not setted
                else {
                    res = (this.emptyTemplateId == "") ? '' : $('#' + this.emptyTemplateId).html();
                }

                return this.realRenderView(res);
            }

        } else {
            throw "Missing data";
        }
    }

    realRenderView(res) {
        res = res.trim();

        if (this.return === true)
            return res;

        if (this.append === true)
            $('#' + this.containerId).append(res);

        else if (this.after === true)
            $(res).insertAfter('#' + this.containerId);

        else if (this.prepend === true)
            $('#' + this.containerId).prepend(res);

        else
            $('#' + this.containerId).html(res);

        this.insertCleanerScripts();

    }
    //#endregion
}