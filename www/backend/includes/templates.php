<script type="text/html" id="language_tab_nav_template">
    <a class="nav-item nav-link text-dark op-5" id="tabLang-{{Language}}-tab" data-bs-toggle="tab" href="#tabLang-{{Language}}" role="tab" aria-controls="tabLang-{{Language}}" aria-selected="true">
        <i class="flag flag-{{LanguageLower}}"></i>
    </a>
</script>

<script type="text/html" id="language_filter_template">
    <option value="{{Language}}">{{LanguageLower}}</option>
</script>

<!-- Language nav bar for Caption -->
<script type="text/html" id="language_caption_image_tab_nav_template">
    <a class="nav-item nav-link text-dark op-5" id="image-caption-tabLang-{{Language}}-tab" data-bs-toggle="tab" href="#image-caption-tabLang-{{Language}}" role="tab" aria-controls="image-caption-tabLang-{{Language}}" aria-selected="true">
        <i class="flag flag-{{LanguageLower}}"></i>
    </a>
</script>

<!-- Language content for Caption -->
<script type="text/html" id="language_caption_image_tab_template">
    <div class="tab-pane fade" language="{{Language}}" id="image-caption-tabLang-{{Language}}" role="tabpanel" aria-labelledby="image-caption-tabLang-{{Language}}-tab">
        <div id="image-caption-tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Didascalia</label>
                        <textarea class="form-control" name="Caption" cols="30" rows="10"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<!-- File Manager -->
<script type="text/html" id="languageCaptionFileTabNavTemplate">
    <a class="nav-item nav-link text-dark op-5" id="fileCaption-TabLang-{{Language}}-Tab" data-bs-toggle="tab" href="#fileCaption-TabLang-{{Language}}" role="tab" aria-controls="fileCaption-TabLang-{{Language}}" aria-selected="true">
        <i class="flag flag-{{LanguageLower}}"></i>
    </a>
</script>
<script type="text/html" id="modalCaptionLanguageTemplate">
    <div class="tab-pane fade" language="{{Language}}" name="tabLangCaptionFile" id="fileCaption-TabLang-{{Language}}" role="tabpanel" aria-labelledby="fileCaption-TabLang-{{Language}}-Tab">
        <div id="fileCaption-TabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Didascalia</label>
                        <textarea class="form-control" name="Caption" cols="30" rows="4" mandatory></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script type="text/html" id="modalCaptionLanguageContainerTemplate">
    <div class="row">
        <div class="col">
            <nav>
                <div class="nav nav-tabs" id="captionFileNavTab" role="tablist">
                </div>
            </nav>
            <div class="card mb-3 border-0">
                <div class="card-body">
                    <div class="tab-content" id="captionFileNavTabContent">
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script type="text/html" id="modalCaptionInputTemplate">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label>Didascalia</label>
                <textarea class="form-control" name="Caption" cols="30" rows="4" mandatory></textarea>
            </div>
        </div>
    </div>
</script>
<script type="text/html" id="templateButtonCaption-<?= Base_Files_Captions_Types::MONO_LANG ?>">
    <button tooltip="Didascalia" type="button" onclick="getFilesManagerCaption('{{IdFile}}', '{{Macro}}', '{{Type}}', '{{IdRow}}', <?= Base_Files_Captions_Types::MONO_LANG ?>)" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-fw fa-quote-left"></i>
    </button>
</script>
<script type="text/html" id="templateButtonCaption-<?= Base_Files_Captions_Types::MULTI_LANG ?>">
    <button tooltip="Didascalia" type="button" onclick="getFilesManagerCaption('{{IdFile}}', '{{Macro}}', '{{Type}}', '{{IdRow}}', <?= Base_Files_Captions_Types::MULTI_LANG ?>)" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-fw fa-quote-left"></i>
    </button>
</script>