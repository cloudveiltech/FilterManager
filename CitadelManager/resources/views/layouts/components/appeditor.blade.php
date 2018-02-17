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
                <br />
                <div class="row cell-auto-size">
                    <div class="cell cell-auto-size" style="margin-right: 20px">
                        <div class="input-control text app_editor_control" data-role="input">
                            <label for="editor_application_name">Application Name:</label>
                            <input type="text"  name="editor_application_name" id="editor_application_name" >
                            <button class="button helper-button clear app_editor_close_button" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                
                    <div class="cell cell-auto-size">
                        <div class="input-control text app_editor_control" data-role="input">
                            <label for="editor_application_notes">Notes:</label>
                            <input type="text"  name="editor_application_notes" id="editor_application_notes" />
                            <button class="button helper-button clear app_editor_close_button" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>
                <div class="row">    
                    <div class="cell colspan5">
                        <h3 align="center">App Groups</h3>
                        <select id="editor_application_source_list" multiple>
                        </select>
                    </div>
                    <div class="cell colspan1" id="editor_application_actions">
                        <br /><br /><br /><div id="spiner">&nbsp;<img id="spiner_5" src="{{asset('img/loading7_navy_blue.gif')}}" width = "40px" /></div>
                        <button class="button primary" type="button" id="editor_application_right_all_btn"> &gt;&gt; </button> <br />
                        <button class="button primary" type="button" id="editor_application_right_btn"> &gt; </button><br />
                        <button class="button primary" type="button" id="editor_application_left_btn"> &lt; </button><br />
                        <button class="button primary" type="button" id="editor_application_left_all_btn"> &lt;&lt;  </button><br />
                        
                    </div>
                    
                    <div class="cell colspan5">
                        <h3 align="center">Selected App Groups</h3>
                        <select id="editor_application_target_list" multiple>
                        </select>
                    </div>
                </div>
                <br />
                <hr class="thin">
                <div class="row cell-auto-size marginTop20">
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