<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/appuseractivationrecord.js') }}">
</script>

<script src="{{ asset('plugins/jquery-ui-1.13.2.custom/jquery-ui.min.js') }}"></script>

<!-- User Editing Overlay -->
<div class="bg-dark" id="overlay_activation_editor">
    <div id="div_activation_0">
        <div class="flex-grid">

            <h1 id="activation_editing_title">Edit App User Activations</h1>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_activation_form" data-on-submit="submit">
                <div class="tabcontrol" data-role="tabcontrol">
                    <ul class="tabs">
                        <li class="active">
                            <a href="#activation_info_tab">Activation</a>
                        </li>
                        <li>
                            <a href="#activation_self_moderation_tab">Blocked Sites</a>
                        </li>
                        <li>
                            <a href="#activation_whitelist_tab">Allowed Sites</a>
                        </li>
                        <li>
                            <a href="#activation_trigger_blacklist_tab">Blocked Triggers</a>
                        </li>
                        <li>
                            <a href="#activation_apps_blocked_tab">Blocked Apps</a>
                        </li>
                        <li>
                            <a href="#time_restrictions_tab_activations">Time Restrictions</a>
                        </li>
                    </ul>

                    <div class="frames">
                        <div class="frame" id="activation_info_tab">
                            <div class="row cell-auto-size">
                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_user_full_name">User Name:</label>
                                        <input type="text" disabled="disabled"
                                               name="editor_activation_input_user_full_name"
                                               id="editor_activation_input_user_full_name"
                                        />
                                    </div>
                                </div>

                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_identifier">Identifier:</label>
                                        <input type="text" disabled="disabled" name="editor_activation_input_identifier"
                                               id="editor_activation_input_identifier"
                                        />
                                    </div>
                                </div>

                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_device_id">Device Id:</label>
                                        <input type="text" disabled="disabled" name="editor_activation_input_device_id"
                                               id="editor_activation_input_device_id"/>
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <br/>
                            <div class="row cell-auto-size">
                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_ip_address">IP Address:</label>
                                        <input type="text" disabled="disabled" name="editor_activation_input_ip_address"
                                               id="editor_activation_input_ip_address"
                                        />
                                    </div>
                                </div>
                                <div class="cell cell-auto-size check-label" style="margin-right: -160px">
                                    <label for="editor_activation_debug_enabled">Debug Enabled:</label>
                                    <br/>
                                    <br/>
                                    <label class="switch-original">
                                        <input type="checkbox" id="editor_activation_debug_enabled"
                                               name="editor_activation_debug_enabled">
                                        <span class="check"></span>
                                    </label>
                                    <label id="editor_activation_debug_enabled_text" class="report-level-width">Debug mode disabled</label>
                                </div>
                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        &nbsp;
                                    </div>
                                </div>
                            </div>

                            <br/>
                            <br/>
                            <div class="row cell-auto-size">

                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_bypass_quantity">Bypass Quantity:</label>
                                        <input type="number" name="editor_activation_input_bypass_quantity"
                                               id="editor_activation_input_bypass_quantity"/>
                                        <button class="button helper-button reveal" tabindex="-1" type="button">
                                            <span class="mif-looks"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_bypass_period">Bypass Period (min):</label>
                                        <input type="number" name="editor_activation_input_bypass_period"
                                               id="editor_activation_input_bypass_period"/>
                                        <button class="button helper-button clear" type="button">
                                            <span class="mif-cross"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="cell cell-auto-size">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_bypass_used">Bypass Used:</label>
                                        <input type="number" disabled="disabled"
                                               name="editor_activation_input_bypass_used"
                                               id="editor_activation_input_bypass_used"
                                        />
                                    </div>
                                    <button class="button primary" type="button" event-click="resetBypassUsed()">Reset
                                    </button>
                                </div>
                            </div>

                            <br/>
                            <br/>


                            <div class="row">

                                <div class="cell cell-auto-size">
                                    <div class="input-control select" data-role="input">
                                        <label for="editor_activation_input_group_id">Group:</label>
                                        <select name="editor_ctivation_input_group_id"
                                                id="editor_ctivation_input_group_id">
                                        </select>
                                    </div>
                                </div>

                                <div class="cell cell-auto-size padding-left-100">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_friendly_name">Friendly Name:</label>
                                        <input type="text"
                                               name="editor_activation_input_friendly_name"
                                               id="editor_activation_input_friendly_name"
                                        />
                                    </div>
                                </div>
                                <div class="cell cell-auto-size padding-left-100">
                                    <div class="input-control text" data-role="input">
                                        <label for="editor_activation_input_update_channel">Update Channel:</label>
                                        <select name="editor_activation_input_update_channel"
                                                id="editor_activation_input_update_channel">
                                            <option value=""></option>
                                            <option value="Stable">Stable</option>
                                            <option value="Alpha">Alpha</option>
                                            <option value="Beta">Beta</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="frame" id="activation_self_moderation_tab">
                            <div class="grid" id="activation_self_moderation_list">
                                <h2>
                                    <small>Blocked Sites</small>
                                </h2>
                                <hr class="thin"/>
                                <button type="button" event-click="addNewSelfModerationSite" class="button primary">Add
                                    Site
                                </button>

                                <div style="max-height: 350px; overflow-y: auto;">
                                    <table id="activation_self_moderation_table"
                                           class="table striped hovered border black-background" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>Site</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                        </thead>
                                        <tbody id="activation_self_moderation_insert">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div class="frame" id="activation_whitelist_tab">
                            <div class="grid" id="activation_whitelist_list">
                                <h2>
                                    <small>Allowed Sites</small>
                                </h2>
                                <hr class="thin"/>
                                <button type="button" event-click="addNewWhitelistSite" class="button primary">Add
                                    Site
                                </button>
                                <div style="max-height: 350px; overflow-y: auto;">
                                    <table id="activation_whitelist_table"
                                           class="table striped hovered border black-background" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>Site</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                        </thead>
                                        <tbody id="activation_whitelist_table">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="frame" id="activation_trigger_blacklist_tab">
                            <div class="grid">
                                <h2>
                                    <small>Blocked Triggers</small>
                                </h2>
                                <hr class="thin"/>
                                <button type="button" event-click="addNewCustomTextTrigger" class="button primary">Add
                                    Text Trigger
                                </button>
                                <div style="max-height: 350px; overflow-y: auto;">
                                    <table id="activation_trigger_table"
                                           class="table striped hovered border black-background" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>Text Trigger</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                        </thead>
                                        <tbody id="activation_trigger_insert">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="frame" id="activation_apps_blocked_tab">
                            <div class="grid">
                                <h2>
                                    <small>Blocked Apps</small>
                                </h2>
                                <hr class="thin"/>
                                <button type="button" event-click="addNewBlockedApp" class="button primary">Add
                                    App
                                </button>
                                <div style="max-height: 350px; overflow-y: auto;">
                                    <table id="activation_app_list_table"
                                           class="table striped hovered border black-background" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>App Name</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                        </thead>
                                        <tbody id="activation_apps_blocked_insert">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="frame" id="time_restrictions_tab_activations">
                            @include('layouts.components.timerestrictionseditor')
                        </div>
                    </div>

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
                </div>

            </form>
        </div>
    </div>
</div>