<!-- Include Whitelist Record Record Type JS. -->
<script src="{{ asset('js/admin/records/whitelistrecord.js') }}">
</script>


<!-- Whitelist Editing Overlay -->
<div class="bg-dark"  id="overlay_whitelist_editor">
    <div id="div_whitelist_0">
        <div class="flex-grid">            
            <h1 id="whitelist_editing_title">Add Whitelist item</h1>
            <br/>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_whitelist_form" data-on-submit="submit">

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_whitelist_name">Application Name:</label>
                            <input type="text"  name="editor_whitelist_name" id="editor_whitelist_name" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    
                    <div class="cell size4" id="div_09c2_1">
                        <label for="editor_whitelist_input_isactive">Enabled:</label>                        
                        <label class="switch-original" id="label_whitelist_enabled_0">
                            <input type="checkbox" id="editor_whitelist_input_isactive" name="editor_whitelist_input_isactive">
                            <span class="check"></span>
                        </label>
                    </div>
                </div>
                
                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="whitelist_editor_submit" type="submit" class="button primary">Add User</button>
                        <button id="whitelist_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size" id="div_whitelist_2">
                    <div id="user_form_errors">                        
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>