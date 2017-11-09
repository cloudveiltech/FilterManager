<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/grouprecord.js') }}">
</script>

<!-- Group Editing Overlay -->
<div class="bg-dark"  id="overlay_group_editor">
    <div id="div_ea30_0">


        <h1  id="group_editing_title">Create New Group</h1>
        <br/>
        <br/>

        <!-- Tabs for the various settings. -->
        <div class="tabcontrol" data-role="tabcontrol">

            <!-- Tab definitions. -->
            <ul class="tabs">
                <li><a href="#settings_tab" id="a_ea30_0">Settings</a></li>
                <li><a href="#rule_selection_tab" id="a_ea30_1">Rule Selection</a></li>
                <li><a href="#app_filtering_tab" id="a_ea30_2">Application Filtering</a></li>
            </ul>

            <!-- Tab containers. -->
            <div class="frames">

                <!-- Settings tab. -->
                <div class="frame"  id="settings_tab">

                    <br>
                    <form method="post" action="javascript:void(0)" id="editor_group_form" data-on-submit="submit">

                        <div class="grid">

                            <br>
                            <h2><small>General</small></h2>
                            <hr class="thin" id="hr_ea30_0">
                            <br>

                            <div class="row cells3">

                                <div class="cell" id="div_ea30_1">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_group_input_groupname">Group Name</label>
                                        <br>
                                        <br>
                                        <input type="text" name="editor_group_input_groupname" id="editor_group_input_groupname">
                                        <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                                    </div>
                                </div>

                                <div class="cell" id="div_ea30_2">
                                    <label for="editor_cfg_update_frequency_input">Client Update Frequency (Minutes)</label>
                                    <br>
                                    <br>
                                    <label class="input-control" id="label_ea30_0">
                                        <input type="number" id="editor_cfg_update_frequency_input" name="editor_cfg_update_frequency_input">                                    
                                    </label>
                                </div>

                                <div class="cell" id="div_ea30_3">
                                    <label for="editor_group_input_isactive">Enabled:</label>
                                    <br>
                                    <br>
                                    <label class="switch-original" id="label_ea30_1">
                                        <input type="checkbox" id="editor_group_input_isactive" name="editor_group_input_isactive">
                                        <span class="check"></span>
                                    </label>
                                </div>

                            </div>

                            <div class="row cells3">
                                <div class="cell" id="div_ea30_4">
                                    <div class="input-control text" data-role="input">
                                            <label for="editor_cfg_primary_dns_input">Primary DNS</label>
                                            <br>
                                            <br>
                                            <input type="text" name="editor_cfg_primary_dns_input" id="editor_cfg_primary_dns_input">
                                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                                        </div>
                                </div>
                                <div class="cell" id="div_ea30_5">
                                    <div class="input-control text" data-role="input">
                                            <label for="editor_cfg_secondary_dns_input">Secondary DNS</label>
                                            <br>
                                            <br>
                                            <input type="text" name="editor_cfg_secondary_dns_input" id="editor_cfg_secondary_dns_input">
                                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                                        </div>
                                </div>

                                <div class="cell" id="div_ea30_6">
                                    <label for="editor_cfg_nlp_threshold_input">NLP Threshold (Percent, 0.0 to 1.0)</label>
                                    <br>
                                    <br>
                                    <label class="input-control" id="label_ea30_2">
                                        <input type="number" id="editor_cfg_nlp_threshold_input" name="editor_cfg_nlp_threshold_input" min="0" max="1" value="0">                                    
                                    </label>
                                </div>
                            
                            </div>
                            
                            <div class="row cells3">
                                
                                <div class="cell" id="div_ea30_6">
                                    <label for="editor_cfg_trigger_max_size_input">Text Trigger Scan Window Size (-1 for unlimited)</label>
                                    <br>
                                    <br>
                                    <label class="input-control" id="label_ea30_2">
                                        <input type="number" id="editor_cfg_trigger_max_size_input" name="editor_cfg_trigger_max_size_input" min="-1" value="-1">                                    
                                    </label>
                                </div>
                                
                                <div class="cell" id="div_ea30_6">
                                    <label for="editor_cfg_update_channel_input">Update Channel</label>
                                    <br>
                                    <br>
                                    <div class="input-control select" data-role="input">
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

                    <br>
                    <h2><small>Anti-Tamper</small></h2>
                    <hr class="thin" id="hr_ea30_1">
                    <br>

                    <div class="grid">
                        <div class="row cells3">

                            <div class="cell" id="div_ea30_7">
                                <label for="editor_cfg_no_terminate_input">Cannot Terminate</label>
                                <br>
                                <br>
                                <label class="switch-original" id="label_ea30_3">
                                    <input type="checkbox" id="editor_cfg_no_terminate_input" name="editor_cfg_no_terminate_input">
                                    <span class="check"></span>
                                </label>
                            </div>

                            <div class="cell" id="div_ea30_8">
                                <label for="editor_cfg_disable_internet_input">Disable Internet</label>
                                <br>
                                <br>
                                <label class="switch-original" id="label_ea30_4">
                                    <input type="checkbox" id="editor_cfg_disable_internet_input" name="editor_cfg_disable_internet_input">
                                    <span class="check"></span>
                                </label>
                            </div>

                            <div class="cell" id="div_ea30_9">
                                <label for="editor_cfg_use_threshold_input">Use Threshold</label>
                                <br>
                                <br>
                                <label class="switch-original" id="label_ea30_5">
                                    <input type="checkbox" id="editor_cfg_use_threshold_input" name="editor_cfg_use_threshold_input">
                                    <span class="check"></span>
                                </label>
                            </div>

                        </div>                    

                        <div class="row cells3">

                            <div class="cell" id="div_ea30_10">
                                <label for="editor_cfg_threshold_count_input">Threshold Trigger Count</label>
                                <br>
                                <br>
                                <label class="input-control" id="label_ea30_6">
                                    <input type="number" id="editor_cfg_threshold_count_input" name="editor_cfg_threshold_count_input">                                    
                                </label>
                            </div>
                            
                            <div class="cell" id="div_ea30_11">
                                <label for="editor_cfg_threshold_period_input">Threshold Trigger Period (Minutes)</label>
                                <br>
                                <br>
                                <label class="input-control" id="label_ea30_7">
                                    <input type="number" id="editor_cfg_threshold_period_input" name="editor_cfg_threshold_period_input">                                    
                                </label>
                            </div>
                            
                            <div class="cell" id="div_ea30_12">
                                <label for="editor_cfg_threshold_timeout_input">Threshold Timeout Period (Minutes)</label>
                                <br>
                                <br>
                                <label class="input-control" id="label_ea30_8">
                                    <input type="number" id="editor_cfg_threshold_timeout_input" name="editor_cfg_threshold_timeout_input">                                    
                                </label>
                            </div>
                            
                        </div>
                        
                        <div class="row cells3">
                            <div class="cell" id="div_ea30_13">
                                <label for="editor_cfg_bypasses_allowed_input">Bypasses Allowed Per Day</label>
                                <br>
                                <br>
                                <label class="input-control" id="label_ea30_9">
                                    <input type="number" id="editor_cfg_bypasses_allowed_input" name="editor_cfg_threshold_count_input">                                    
                                </label>
                            </div>
                            
                            <div class="cell" id="div_ea30_14">
                                <label for="editor_cfg_bypass_duration_input">Bypass Period (Minutes)</label>
                                <br>
                                <br>
                                <label class="input-control" id="label_ea30_10">
                                    <input type="number" id="editor_cfg_bypass_duration_input" name="editor_cfg_threshold_count_input">                                    
                                </label>
                            </div>

                        </div>                        
                        
                    </div>

                </div>

                <!-- Filter rules selection tab. -->
                <div class="frame"  id="rule_selection_tab">

                    <div id="div_ea30_15">
                        <p><span class="mif-filter fg-green" ></span>&nbsp;&nbsp;&nbsp;Filtering rules. These match specific request strings.</p>
                        <p><span class="mif-language"></span>&nbsp;&nbsp;&nbsp;Natural Language Processing. These categories will cause a block action when the text payload of a website is deemed to belong to selected categories.</p>
                        <p><span class="mif-warning fg-red"></span>&nbsp;&nbsp;&nbsp;Text triggers. These will cause blocking to happen if any of their content is detected in any text payload. Use these sparingly, in fact you should probably only use triggers for pornography.</p>                        
                    </div>

                    <br>
                    <hr class="thin" id="hr_ea30_2">
                    <br>

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
                    <div class="grid" id="div_ea30_21">
                        <div class="row cells5" id="div_ea30_22">
                            <div class="cell colspan3" id="div_ea30_23">

                                <h2><small>Apps To Filter</small></h2>
                                <hr class="thin" id="hr_ea30_3">
                                <br>
                                <textarea id="group_filtered_applications" ></textarea>
                            </div>
                            <div class="cell" id="div_ea30_24">

                                <h2><small>App List Function</small></h2>
                                <hr class="thin" id="hr_ea30_4">
                                <br>

                                <label class="input-control radio">
                                    <input id="group_filteredapps_radio_blacklist" type="radio" name="n1" checked>
                                    <span class="check"></span>
                                    <span class="caption">Blacklist</span>
                                </label>
                                <br>
                                <label class="input-control radio">
                                    <input id="group_filteredapps_radio_whitelist" type="radio" name="n1">
                                    <span class="check"></span>
                                    <span class="caption">Whitelist</span>
                                </label>

                                <br>
                                <br>

                                <div>
                                    <small>
                                    List executable names, including extension, in the box on the left.
                                    <br>
                                    Select whitelist if you want the listed apps to be excluded from filtering while all others are filtered.
                                    <br> 
                                    Select blacklist if you want only the specified applications to be forced through the filter.
                                    <br>
                                    Leave empty to force all traffic through the filter.
                                    </small>
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

        <div class="row cell-auto-size" id="div_ea30_25">
            <div id="group_form_errors">
            </div>
        </div>

    </div>
</div>