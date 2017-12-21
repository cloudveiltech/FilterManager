<!-- Include AppList Record Record Type JS. -->
<script src="{{ asset('js/admin/records/apprecord.js') }}">
</script>


<!-- application Editing Overlay -->
<div class="bg-dark"  id="overlay_application_editor">
    <div id="div_application_0">
        <div class="flex-grid">            
            <h1 id="application_editing_title">Add Application</h1>
            <br/>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_application_form" data-on-submit="submit">

                <div class="row cell-auto-size">
                    <div class="cell cell-auto-size">
                        <div class="input-control text app_editor_control" data-role="input">
                            <span for="editor_application_name">Application Name:</span>
                            <input type="text"  name="editor_application_name" id="editor_application_name" >
                            <button class="button helper-button clear app_editor_close_button" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>
                <div class="row cell-auto-size">
                    <div class="cell cell-auto-size">
                        <div class="input-control text app_editor_control" data-role="input">
                            <span for="editor_application_notes">Application Notes:</span>
                            <textarea type="text"  name="editor_application_notes" id="editor_application_notes" ></textarea>
                            <button class="button helper-button clear app_editor_close_button" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>
                <div class="row cell-auto-size marginTop80">
                    <div class="form-actions">
                        <button id="application_editor_submit" type="submit" class="button primary">Add Application</button>
                        <button id="application_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size" id="div_application_2">
                    <div id="user_form_errors">                        
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>