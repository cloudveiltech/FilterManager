<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/applyapptoappgroup.js') }}">
</script>


<!-- Apply to group Overlay -->
<div class="bg-dark"  id="overlay_apply_app_to_app_group">
    <div id="div_apply_app_to_app_group_0">
        <div class="flex-grid">            
            <h1 id="apply_app_to_app_group_title">Apply Application to AppGroup</h1><br/>
            <hr class="thin" /><br/>
            <div class="row cell-auto-size">
                <div class="cell cell-auto-size">
                    <div class="input-control text expandto100percent" data-role="input">
                        <label class="editor_appgroup_label">Application </label>
                        <select id="app_name_list" ></select>
                    </div>
                </div>
            </div>
            <div class="row">    
                <div class="cell colspan5">
                <label>Exist App Groups</label>
                <select id="apply_app_to_app_group_source_list" multiple>
                </select>
                </div>
                <div class="cell colspan1" id="apply_app_to_app_group_actions">
                    <br /><div id="spiner">&nbsp;<img id="spiner_2" src="{{asset('img/loading7_navy_blue.gif')}}" width = "40px" /></div>
                    <button class="button primary" type="button" id="apply_app_to_app_group_right_all_btn"> &gt;&gt; </button> <br />
                    <button class="button primary" type="button" id="apply_app_to_app_group_right_btn"> &gt; </button><br />
                    <button class="button primary" type="button" id="apply_app_to_app_group_left_btn"> &lt; </button><br />
                    <button class="button primary" type="button" id="apply_app_to_app_group_left_all_btn"> &lt;&lt;  </button><br />
                    
                </div>
                
                <div class="cell colspan5">
                    <label>Selected App Groups</label>
                    <select id="apply_app_to_app_group_target_list" multiple>
                    </select>
                </div>
            </div>
            <br/>
            <hr class="thin">
            <div class="row cell-auto-size">
                <div class="form-actions">
                    <button id="apply_app_to_app_group_appy" type="button" class="button primary">Apply</button>
                    <button id="apply_app_to_app_group_close" type="button" class="button link">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>