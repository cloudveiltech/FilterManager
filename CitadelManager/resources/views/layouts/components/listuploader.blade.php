<!-- Include List Upload Overlay JS. -->
<script src="{{ asset('js/admin/listuploadoverlay.js') }}">
</script>

<!-- Group Editing Overlay -->
<div class="bg-dark" id="overlay_list_upload">
    <div id="div_0717_0">
        <div class="flex-grid">

            <h1 id="h1_0717_0">Upload Filter Lists</h1>
            <br/>
            <hr class="thin">
            <br/>

            <div class="row cell-auto-size">

                <div class="cell cell-auto-size">
                    <button id="upload_list_select_file" class="button">
                        <span class="mif-file-zip"></span> Select File</button>
                </div>

                <div class="cell cell-auto-size">
                    <div class="input-control text" data-role="input" id="div_0717_1">
                        <label for="upload_list_selected_file_name">Selected File:</label>
                        <input type="text" name="upload_list_selected_file_name" id="upload_list_selected_file_name" readonly>
                    </div>
                </div>

            </div>

            <br/>

            <div class="row cell-auto-size">

                <div class="cell cell-auto-size">
                    <div class="input-control text" data-role="input">
                        <label for="upload_list_namespace">Collection Name:</label>
                        <input type="text" list="existing_list_namespaces" maxlength="40" name="upload_list_namespace" id="upload_list_namespace">
                    </div>

                    <!-- Used to dynamically display existing filter group names for autocompletion assistance. -->
                    <datalist id="existing_list_namespaces">

                    </datalist>
                </div>

                <div class="cell size4" id="div_0717_2">
                    <label for="upload_list_overwrite">Overwrite:</label>
                    <label class="switch-original" id="label_0717_0">
                        <input type="checkbox" id="upload_list_overwrite" name="upload_list_overwrite">
                        <span class="check"></span>
                    </label>
                </div>
            </div>

            <br/>

            <div class="row cell-auto-size">
                <div class="form-actions">
                    <button id="upload_list_submit" type="submit" class="button primary">Upload</button>
                    <button id="upload_list_cancel" type="button" class="button link">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Div where we programmatically create a dropzone. -->
<form id="filter_list_dropzone" method="post" name="filter_list_dropzone" action="/capi/admin/adminctlapi.php" class="dropzone needsclick dz-clickable">
    <div class="dz-message"></div>
</form>