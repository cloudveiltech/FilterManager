<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/grouprecord.js') }}">
</script>

<!-- Group Editing Overlay -->
<div class="bg-dark"  id="overlay_group_editor">
    <div id="div_ea30_0">


        <h1  id="group_editing_title">Create New Group</h1>
        <br/>

        <!-- Tabs for the various settings. -->
        <div class="tabcontrol" data-role="tabcontrol">

            <!-- Tab definitions. -->
            <ul class="tabs">
                <li><a href="#settings_tab" >Settings</a></li>
                <li><a href="#rule_selection_tab" >Rule Selection</a></li>
                <li><a href="#app_filtering_tab" >Application Filtering</a></li>
            </ul>

            <!-- Tab containers. -->
            <div class="frames">

                <!-- Settings tab. -->
                <div class="frame"  id="settings_tab">
                    <form method="post" action="javascript:void(0)" id="editor_group_form" data-on-submit="submit">

                        <div class="grid">
                            <h2><small>General</small></h2>
                            <hr class="thin" id="hr_ea30_0">
                            <br>

                            <div class="row cells3">

                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_group_input_groupname">Group Name</label>
                                        <input type="text" name="editor_group_input_groupname" id="editor_group_input_groupname">
                                        <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                                    </div>
                                </div>

                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_cfg_update_frequency_input" title="Client Update Frequency (Minutes)">Client Update Frequency</label>
                                        <input type="number" id="editor_cfg_update_frequency_input" name="editor_cfg_update_frequency_input">
                                    </div>
                                </div>

                                <div class="cell check-label" >
                                    <label for="editor_group_input_isactive">Enabled:</label><br/><br/>
                                    <label class="switch-original">
                                        <input type="checkbox" id="editor_group_input_isactive" name="editor_group_input_isactive">
                                        <span class="check"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="row cells3">
                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_cfg_primary_dns_input">Primary DNS</label>
                                        <input type="text" name="editor_cfg_primary_dns_input" id="editor_cfg_primary_dns_input">
                                        <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                                    </div>
                                </div>
                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_cfg_secondary_dns_input">Secondary DNS</label>
                                        <input type="text" name="editor_cfg_secondary_dns_input" id="editor_cfg_secondary_dns_input">
                                        <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                                    </div>
                                </div>

                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_cfg_nlp_threshold_input" title = "NLP Threshold (Percent, 0.0 to 1.0)">NLP Threshold (Percent...</label>
                                        <input type="number" id="editor_cfg_nlp_threshold_input" name="editor_cfg_nlp_threshold_input" min="0" max="1" value="0">                                    
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row cells3">
                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_cfg_trigger_max_size_input" title="Text Trigger Scan Window Size (-1 for unlimited)">Text Trigger Scan Win...</label>
                                        <input type="number" id="editor_cfg_trigger_max_size_input" name="editor_cfg_trigger_max_size_input" min="-1" value="-1">                                    
                                    </div>
                                </div>
                                
                                <div class="cell">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_cfg_update_channel_input">Update Channel</label>                                    
                                        <select name="editor_cfg_update_channel_input" id="editor_cfg_update_channel_input" >
                                            <option value="Stable">Stable</option>
                                            <option value="Alpha">Alpha</option>
                                            <option value="Beta">Beta</option>
                                        </select>                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <h2><small>Anti-Tamper</small></h2>
                    <hr class="thin" id="hr_ea30_1">
                    <br>

                    <div class="grid">
                        <div class="row cells3">

                            <div class="cell check-label">
                                <label for="editor_cfg_no_terminate_input">Cannot Terminate</label><br /><br/>
                                <label class="switch-original">
                                    <input type="checkbox" id="editor_cfg_no_terminate_input" name="editor_cfg_no_terminate_input">
                                    <span class="check"></span>
                                </label>
                                <br />
                            </div>

                            <div class="cell check-label">
                                <label for="editor_cfg_disable_internet_input">Disable Internet</label><br /><br/>
                                <label class="switch-original">
                                    <input type="checkbox" id="editor_cfg_disable_internet_input" name="editor_cfg_disable_internet_input">
                                    <span class="check"></span>
                                </label>
                                <br />
                            </div>

                            <div class="cell check-label">
                                <label for="editor_cfg_use_threshold_input">Use Threshold</label><br /><br/>
                                <label class="switch-original">
                                    <input type="checkbox" id="editor_cfg_use_threshold_input" name="editor_cfg_use_threshold_input">
                                    <span class="check"></span>
                                </label>
                                <br />
                            </div>
                        </div>                    

                        <div class="row cells3">
                            <div class="cell">
                                <div class="input-control text" data-role="input">
                                    <label for="editor_cfg_threshold_count_input">Threshold Trigger Count</label>
                                    <input type="number" id="editor_cfg_threshold_count_input" name="editor_cfg_threshold_count_input">
                                </div>
                            </div>
                            <div class="cell">
                                <div class="input-control text" data-role="input">
                                    <label for="editor_cfg_threshold_period_input" title="Threshold Trigger Period (Minutes)">Threshold Trigger Period</label>
                                    <input type="number" id="editor_cfg_threshold_period_input" name="editor_cfg_threshold_period_input">
                                </div>
                            </div>
                            <div class="cell">
                                <div class="input-control text" data-role="input">
                                    <label for="editor_cfg_threshold_timeout_input" title="Threshold Timeout Period (Minutes)">Threshold Timeout Period</label>
                                    <input type="number" id="editor_cfg_threshold_timeout_input" name="editor_cfg_threshold_timeout_input">                                    
                                </div>
                            </div>
                        </div>
                        
                        <div class="row cells3">
                            <div class="cell">
                                <div class="input-control text" data-role="input">
                                    <label for="editor_cfg_bypasses_allowed_input">Bypasses Allowed Per Day</label>
                                    <input type="number" id="editor_cfg_bypasses_allowed_input" name="editor_cfg_threshold_count_input">                                    
                                </div>
                            </div>
                            
                            <div class="cell">
                                <div class="input-control text" data-role="input">
                                    <label for="editor_cfg_bypass_duration_input" title="Bypass Period (Minutes)">Bypass Period</label>
                                    <input type="number" id="editor_cfg_bypass_duration_input" name="editor_cfg_threshold_count_input">                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter rules selection tab. -->
                <div class="frame"  id="rule_selection_tab">

                    <div class="info_div">
                        <p><span class="mif-filter fg-green" ></span>&nbsp;&nbsp;&nbsp;Filtering rules. These match specific request strings.</p>
                        <p><span class="mif-language"></span>&nbsp;&nbsp;&nbsp;Natural Language Processing. These categories will cause a block action when the text payload of a website is deemed to belong to selected categories.</p>
                        <p><span class="mif-warning fg-red"></span>&nbsp;&nbsp;&nbsp;Text triggers. These will cause blocking to happen if any of their content is detected in any text payload. Use these sparingly, in fact you should probably only use triggers for pornography.</p>                        
                    </div>

                    <br>
                    <hr class="thin" id="hr_ea30_2">

                    <div class="grid" id="div_ea30_16">
                        <div class="row cells4">
                            <div class="cell" id="div_ea30_17">
                                <div class="input-control modern text" style="">
                                    <input id="group_blacklist_filters_search_input" type="text" >
                                    <span class="label">Search Term</span>
                                    <span class="informer"></span>
                                    <span id="group_blacklist_filters_search_input_placeholder" class="placeholder">Type To Filter Selection</span>
                                </div>
                                <br>
                                <div id="group_blacklist_filters" class="example dragula-container" data-text="Blacklist Filters" >
                                </div>
                            </div>

                            <div class="cell" id="div_ea30_18">
                                <div class="input-control modern text" style="">
                                    <input id="group_whitelist_filters_search_input" type="text" >
                                    <span class="label">Search Term</span>
                                    <span class="informer"></span>
                                    <span id="group_whitelist_filters_search_input_placeholder" class="placeholder">Type To Filter Selection</span>
                                </div>
                                <br>
                                <div id="group_whitelist_filters" class="example dragula-container" data-text="Whitelist Filters" >
                                </div>
                            </div>

                            <div class="cell" id="div_ea30_19">
                                <div class="input-control modern text" style="">
                                    <input id="group_bypass_filters_search_input" type="text" >
                                    <span class="label">Search Term</span>
                                    <span class="informer"></span>
                                    <span id="group_bypass_filters_search_input_placeholder" class="placeholder">Type To Filter Selection</span>
                                </div>
                                <br>
                                <div id="group_bypass_filters" class="example dragula-container" data-text="Bypassable Filters" >
                                </div>
                            </div>

                            <div class="cell" id="div_ea30_20">
                                <div class="input-control modern text" style="">
                                    <input id="group_unassigned_filters_search_input" type="text" >
                                    <span class="label">Search Term</span>
                                    <span class="informer"></span>
                                    <span id="group_unassigned_filters_search_input_placeholder" class="placeholder">Type To Filter Selection</span>
                                </div>
                                <br>
                                <div id="group_unassigned_filters" class="example dragula-container" data-text="Available Filters" >

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Application filtering tab. -->
                <div class="frame"  id="app_filtering_tab">
                    <div class="info_div">
                        <p><span class="mif-filter fg-green" ></span>&nbsp;&nbsp;&nbsp;List executable names, including extension, in the box on the left.<br />
                        Select whitelist if you want the listed apps to be excluded from filtering while all others are filtered. <br />
                        Select blacklist if you want only the specified applications to be forced through the filter.
                        Leave empty to force all traffic through the filter.
                        </p>
                    </div>
                    <div class="grid">
                        <div class="row cells5 no-bottom">
                            <div class="cell width-70-percent">
                                <h2><small>Apps To Filter</small>
                                    <div id="group_filter_list">
                                        <label class="input-control radio">
                                            <input id="group_filteredapps_radio_blacklist" type="radio" name="n1" checked>
                                            <span class="check"></span>
                                            <span class="caption">Blacklist</span>
                                        </label>
                                        <label class="input-control radio">
                                            <input id="group_filteredapps_radio_whitelist" type="radio" name="n1">
                                            <span class="check"></span>
                                            <span class="caption">Whitelist</span>
                                        </label>
                                    </div>
                                </h2>
                                <hr class="thin">
                                <div class="list-control" align="center">                    
                                    <div class="width-40-percent">
                                        <h3>App Group List</h3>
                                        <select id="appgroup_source_list" multiple>
                                        </select>
                                    </div>
                                    <div class="width-20-percent" id="apply_togroup_actions">
                                        <br /><br /><div id="spiner">&nbsp;<img id="spiner_4" src="{{asset('img/loading7_navy_blue.gif')}}" width = "40px" /></div>
                                        <button class="button primary" type="button" id="appgroups_source_to_target"> &gt;&gt; </button> <br />
                                        <button class="button primary" type="button" id="appgroup_source_to_target"> &gt; </button><br />
                                        <button class="button primary" type="button" id="appgroup_target_to_source"> &lt; </button><br />
                                        <button class="button primary" type="button" id="appgroups_target_to_source"> &lt;&lt;  </button><br />
                                        
                                    </div>
                                    
                                    <div class="width-40-percent">
                                        <h3>Selected List </h3>
                                        <select id="appgroup_target_list" multiple>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="cell width-30-percent padding-13">
                                <h2><small>App List</small></h2>
                                <hr class="thin">
                                <h3 class='selected_applications_h3'>Applications </h3>
                                <div id="selected_applications">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <hr class="thin" id="hr_ea30_5">

        <div class="form-actions">
            <button id="group_editor_submit" type="submit" class="button primary">Create Group</button>
            <button id="group_editor_cancel" type="button" class="button link">Cancel</button>
        </div>

        <br/>

        <div class="row cell-auto-size error-div">
            <div id="group_form_errors">
            </div>
        </div>

    </div>
</div>