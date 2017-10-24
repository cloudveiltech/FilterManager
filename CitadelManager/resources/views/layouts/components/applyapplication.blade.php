<!-- Include Whitelist Record Record Type JS. -->
<script src="{{ asset('js/admin/applytogroupoverlay.js') }}">
</script>


<!-- Apply to group Overlay -->
<div class="bg-dark"  id="overlay_apply_togroup">
    <div id="div_apply_togroup_0">
        <div class="flex-grid">            
            <h1 id="apply_togroup_title">Apply to Group</h1>
            <br/>
            <hr class="thin" />
            <br/>
            <div class="row">

                <div class="cell colspan4" id="apply_togroup_div_c1">
                    <label class="input-control radio">
                        <input id="apply_togroup_radio_blacklist" type="radio" name="apply_togroup_n1" checked>
                        <span class="check"></span>
                        <span class="caption">Blacklist</span>
                    </label>
                    <label class="input-control radio">
                        <input id="apply_togroup_radio_whitelist" type="radio" name="apply_togroup_n1">
                        <span class="check"></span>
                        <span class="caption">Whitelist</span>
                    </label>
                    <hr class = "thin" />
                    <textarea readonly id="apply_togroup_white_blacklist"></textarea>
                </div>
                <div class="cell colspan3">
                <select id="apply_togroup_source_list" multiple>
                </select>
                </div>
                <div class="cell colspan1" id="apply_togroup_actions">
                    <br /><br /><br />
                    <button class="button primary" id="apply_togroup_right_all_btn"> &gt;&gt; </button> <br />
                    <button class="button primary" id="apply_togroup_right_btn"> &gt; </button><br />
                    <button class="button primary" id="apply_togroup_left_btn"> &lt; </button><br />
                    <button class="button primary" id="apply_togroup_left_all_btn"> &lt;&lt;  </button><br />
                    
                </div>
                
                <div class="cell colspan3">
                    <select id="apply_togroup_target_list" multiple>
                    </select>
                </div>
            </div>
            
            <div class="row cell-auto-size">
                <div class="form-actions">
                    <button id="apply_togroup_appy" type="button" class="button primary">Apply</button>
                    <button id="apply_togroup_close" type="button" class="button link">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>