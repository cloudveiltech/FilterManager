<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/appuseractivationrecord.js') }}">
</script>


<!-- User Editing Overlay -->
<div class="bg-dark"  id="overlay_activation_editor">
    <div id="div_activation_0">
        <div class="flex-grid">
            
            <h1  id="activation_editing_title">Edit App User Activations</h1>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_activation_form" data-on-submit="submit">

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_user_full_name">User Name:</label>
                            <input type="text" disabled="disabled" name="editor_activation_input_user_full_name" id="editor_activation_input_user_full_name" />                        
                        </div>
                    </div>
                    
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_identifier">Identifier:</label>
                            <input type="text"  disabled="disabled"  name="editor_activation_input_identifier" id="editor_activation_input_identifier" />                        
                        </div>
                    </div>
                    
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_device_id">Device Id:</label>
                            <input type="text"  disabled="disabled" name="editor_activation_input_device_id" id="editor_activation_input_device_id" />             
                        </div>
                    </div>
                </div> <br/>
                <br/>
                <div class="row cell-auto-size">
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_ip_address">IP Address:</label>
                            <input type="text"  disabled="disabled" name="editor_activation_input_ip_address" id="editor_activation_input_ip_address" />             
                        </div>
                    </div>
                </div>
               
                <br/>
                <br/>
                <div class="row cell-auto-size">
                    
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_bypass_quantity">Bypass Quantity:</label>
                            <input type="number"  name="editor_activation_input_bypass_quantity" id="editor_activation_input_bypass_quantity" />
                            <button class="button helper-button reveal" tabindex="-1" type="button"><span class="mif-looks"></span></button>                        
                        </div>
                    </div>

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_bypass_period">Bypass Period (min):</label>
                            <input type="number" name="editor_activation_input_bypass_period" id="editor_activation_input_bypass_period"  />
                            <button class="button helper-button clear" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_activation_input_bypass_used">Bypass Used:</label>
                            <input type="number"  disabled="disabled"  name="editor_activation_input_bypass_used" id="editor_activation_input_bypass_used"  />
                        </div>
                    </div>

                    
                </div>
                
                <br/>
                <br/>
                
    
                
                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="activation_editor_submit" type="submit" class="button primary">Save</button>
                        <button id="activation_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size">
                    <div id="activation_form_errors">                        
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>