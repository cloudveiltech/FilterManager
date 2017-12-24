<!-- Include appgroup Record Record Type JS. -->
<script src="{{ asset('js/admin/records/appgrouprecord.js') }}">
</script>


<!-- appgroup Editing Overlay -->
<div class="bg-dark"  id="overlay_appgroup_editor">
    <div id="div_appgroup_0">
        <div class="flex-grid">            
            <h1 id="appgroup_editing_title">Add Application Group Record</h1>
            <br/>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_appgroup_form" data-on-submit="submit">

                <div class="row cell-auto-size">
                    <div class="cell cell-auto-size">
                        <div class="input-control text expandto100percent" data-role="input">
                            <label class="editor_appgroup_label" for="editor_appgroup_name">Application Group Name:</label>
                            <input type="text"  name="editor_appgroup_name" id="editor_appgroup_name" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>
                <br />
                <div class="row cell-auto-size" align="center">                    
                    <div class="cell colspan5">
                        <h3>Applications</h3>
                        <select id="app_source_list" multiple>
                        </select>
                    </div>
                    <div class="cell colspan1" id="apply_togroup_actions">
                        <br /><div id="spiner">&nbsp;<img id="spiner_1" src="{{asset('img/loading7_navy_blue.gif')}}" width = "40px" /></div>
                        <button class="button primary" type="button" id="apps_source_to_target"> &gt;&gt; </button> <br />
                        <button class="button primary" type="button" id="app_source_to_target"> &gt; </button><br />
                        <button class="button primary" type="button" id="app_target_to_source"> &lt; </button><br />
                        <button class="button primary" type="button" id="apps_target_to_source"> &lt;&lt;  </button><br />
                        
                    </div>
                    
                    <div class="cell colspan5">
                        <h3>Selected Applications </h3>
                        <select id="app_target_list" multiple>
                        </select>
                    </div>
                </div>
                <br />
                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="appgroup_editor_submit" type="submit" class="button primary">Add User</button>
                        <button id="appgroup_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size" id="div_appgroup_2">
                    <div id="user_form_errors">                        
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>