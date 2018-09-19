<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/deactivationrequestrecord.js') }}">
</script>

<!-- Deactivation Request Editing Overlay -->
<div class="bg-dark" id="overlay_deactivation_request_editor">
    <div id="div_f213_0">
        <div class="flex-grid">

            <h1 id="deactivation_request_editing_title">Create New Group</h1>
            <br/>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_deactivation_request_form" data-on-submit="submit">

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_deactivation_request_input_username">User Name:</label>
                            <input type="text" name="editor_deactivation_request_input_username" id="editor_deactivation_request_input_username">
                            <button class="button helper-button clear" tabindex="-1" type="button">
                                <span class="mif-cross"></span>
                            </button>
                        </div>
                    </div>

                    <div class="cell size4" id="div_f213_1">
                        <label for="editor_deactivation_request_input_isgranted">Granted:</label>
                        <label class="switch-original" id="label_f213_0">
                            <input type="checkbox" id="editor_deactivation_request_input_isgranted" name="editor_deactivation_request_input_isgranted">
                            <span class="check"></span>
                        </label>
                    </div>

                </div>

                <br/>

                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="deactivation_request_editor_submit" type="submit" class="button primary">Create Group</button>
                        <button id="deactivation_request_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size" id="div_f213_2">
                    <div id="deactivation_request_form_errors">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>