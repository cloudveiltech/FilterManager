<!-- Include Blacklist Record Record Type JS. -->
<script src="{{ asset('js/admin/records/blacklistrecord.js') }}">
</script>


<!-- Blacklist Editing Overlay -->
<div class="bg-dark"  id="overlay_blacklist_editor">
    <div id="div_blacklist_0">
        <div class="flex-grid">            
            <h1 id="blacklist_editing_title">Add Blacklist item</h1>
            <br/>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_blacklist_form" data-on-submit="submit">

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_blacklist_name">Application Name:</label>
                            <input type="text"  name="editor_blacklist_name" id="editor_blacklist_name" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    
                    <div class="cell size4" id="div_10c2_1">
                        <label for="editor_blacklist_input_isactive">Enabled:</label>                        
                        <label class="switch-original" id="label_blacklist_enabled_0">
                            <input type="checkbox" id="editor_blacklist_input_isactive" name="editor_blacklist_input_isactive">
                            <span class="check"></span>
                        </label>
                    </div>
                </div>
                
                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="blacklist_editor_submit" type="submit" class="button primary">Add User</button>
                        <button id="blacklist_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size" id="div_blacklist_2">
                    <div id="user_form_errors">                        
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>