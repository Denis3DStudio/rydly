class DB_Address {
    constructor() {
        this.cleanProperties()
    }
    cleanProperties() {

        // Global data
        this.countries_data = {};
        this.references = [];
        this.renderObject = {};
        this.renderOnClick = false;

        // Field ids
        this.idCountry = "Country";
        this.idRegion = "Region";
        this.idProvince = "Province";
        this.idProvinceContainer = "province_container";
        this.idCity = "City";
        this.idZipCode = "ZipCode";
        this.idZipCodeContainer = "zip_code_container";
        this.prefixes = [""];
        this.suffixes = [""];

        // Api call to get data
        this.async = false;
        this.url = eval(ENUM.BASE_PATH.API.replaceAll("/", "").toLocaleUpperCase()).UTILITY.PLACES

        // Loader
        this.useLoader = false;
    }

    //#region Setters
    setIdCountry(value) {
        this.idCountry = value.replace("#", "");
        return this;
    }
    setIdRegion(value) {
        this.idRegion = value.replace("#", "");
        return this;
    }
    setIdProvince(value) {
        this.idProvince = value.replace("#", "");
        return this;
    }
    setIdCity(value) {
        this.idCity = value.replace("#", "");
        return this;
    }
    setIdZipCode(value) {
        this.idZipCode = value.replace("#", "");
        return this;
    }
    setPrefixes(value) {
        this.prefixes = (!Array.isArray(value)) ? [value] : value;
        return this;
    }
    setSuffixes(value) {
        this.suffixes = (!Array.isArray(value)) ? [value] : value;
        return this;
    }
    setAsync(value) {
        this.async = value;
        return this;
    }
    setApiUrl(value) {
        this.url = value;
        return this;
    }
    setRenderOnClick(value) {
        this.renderOnClick = value;
        return this;
    }
    initLoader(value) {
        this.useLoader = value;
        return this;
    }
    //#endregion

    //#region Functions

    initCities() {

        var instance = this;

        // Check the country value selected
        var country_selected = $(`#${instance.idCountry}`).val();

        // Check if the selected = italy
        if (parseInt(country_selected) == ENUM.BASE_COUNTRY.ITALY) {

            var source = [];

            // Cycle zipcodes array
            $.each(instance.countries_data.ZipCode, function (city, values) {

                // Check if the values is an obj
                if (typeof values === "object" && !Array.isArray(values))
                    // Set the values like and array
                    values = [values];

                values.forEach(value => {

                    source.push({
                        label: `${city} - ${value.OptionValue}`,
                        value: `${city}`,
                        id: value.Id,
                        zipCode: value.OptionValue
                    });
                });

            });

            // Set mandatory attr
            $(`#${instance.idProvince}`).attr("mandatory", true);
            $(`#${instance.idZipCode}`).attr("mandatory", true);

            // Show Province e Zipcode inputs
            $(`#${instance.idProvinceContainer} input`).removeAttr("disabled");
            $(`#${instance.idZipCodeContainer} input`).removeAttr("disabled");

            $(`#${instance.idCity}`).autocomplete({
                source: source,
                select: function (event, item) {

                    // Get the data of the string clicked
                    var data = item.item;

                    // Get the id value
                    var id = data.id;
                    // Get the zip code value
                    var zipCode = data.zipCode;
                    // Get the province
                    var province = instance.countries_data.Province[id].Abbreviation;

                    // Set the value of the zip code
                    $(`#${instance.idZipCode}`).val(zipCode);
                    // Set the value of the province
                    $(`#${instance.idProvince}`).val(province);
                }
            });
        }
        else {

            // Check that the istance is setted
            if ($(`#${instance.idCity}`).autocomplete("instance") != undefined)
                // Destroy the autocomplete from the city
                $(`#${instance.idCity}`).autocomplete("destroy");

            // Clear inputs
            $(`#${instance.idProvince}`).val("");
            $(`#${instance.idZipCode}`).val("");

            $(`#${instance.idProvince}`).removeAttr("mandatory");
            $(`#${instance.idZipCode}`).removeAttr("mandatory");

            // Hide Province e Zipcode inputs
            $(`#${instance.idProvinceContainer} input`).attr("disabled","true");
            $(`#${instance.idZipCodeContainer} input`).attr("disabled","true");
        }
    }
    initCountries() {

        // Clear the html
        $(`#${this.idCountry}`).html("");
        // Refresh the selectpicker
        $(`#${this.idCountry}`).selectpicker("refresh");

        // Cycle all country and push in the country select
        this.countries_data.Country.forEach(country => {

            $(`#${this.idCountry}`).append(`<option value="${country.IdCountry}">${country.CountryName}</option>`);
        });

        // Refresh the selectpicker
        $(`#${this.idCountry}`).val(ENUM.BASE_COUNTRY.ITALY).selectpicker("refresh");

        var istance = this;

        $(`#${this.idCountry}`).on("change", function () {
            istance.initCities();
        });
    }

    //#endregion

    //#region Init

    initFields() {

        var instance = this;

        this.get_call(function () {

            instance.initCountries();
        });
    }

    //#endregion

    //#region Api Call

    get_call(callback = null) {

        // Check that the instance in null
        if (this.references.length > 0) {

            callback();
            return
        }

        var instance = this;

        // Get difference between suffixes and prefixes
        let missing = this.prefixes.length - this.suffixes.length;
        let prop = (missing < 0) ? "prefixes" : "suffixes";

        // Get positive number
        missing = Math.abs(missing);

        // Create an empty array and merge to existing one
        if (missing > 0)
            this[prop] = this[prop].concat(Array(missing).join(".").split("."))

        $.ajax({
            type: 'GET',
            async: this.async,
            url: this.formatUrl(this.url.Url),
        })
            .done(function (data, status) {
                if (data.status == "FAILED")
                    console.log(data);
                else {

                    // Save informations and references keys
                    instance.countries_data = data.Response;
                    instance.references = Object.keys(instance.countries_data);

                    callback();
                }
            })
            .fail(function (data, status) {
                console.log(data);
            });
    }
    formatUrl(url) {

        // Check if has the / at the end
        if (url[url.length - 1] != "/")
            url += "/";

        return url;
    }

    //#endregion

}