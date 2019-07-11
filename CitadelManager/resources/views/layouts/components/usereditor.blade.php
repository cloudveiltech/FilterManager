<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/userrecord.js') }}">
</script>

<!-- User Editing Overlay -->
<div class="bg-dark overlay" id="overlay_user_editor">
    <div id="user_editor_body">
        <div class="flex-grid">

            <h1 text-bind="m_editorTitleValue" id="user_editing_title">Create New User</h1>
            <br />
            <form method="post" action="javascript:void(0)" event-submit="onSubmit" id="editor_user_form" data-on-submit="submit">
                <div class="tabcontrol" data-role="tabcontrol">
                    <ul class="tabs">
                        <li class="active">
                            <a href="#user_info_tab">User Information</a>
                        </li>
                        <li>
                            <a href="#user_activation_tab">Activations</a>
                        </li>
                        <li>
                            <a href="#self_moderation_tab">User's Blocked Sites</a>
                        </li>
                        <li>
                            <a href="#custom_whitelist_tab">User's Allowed Sites</a>
                        </li>
                        <li>
                            <a href="#trigger_blacklist_tab">User's Blocked Triggers</a>
                        </li>
                        <li>
                            <a href="#time_restrictions_tab">User's Time Restrictions</a>
                        </li>
                    </ul>

                    <div class="frames">
                        <div class="frame" id="user_info_tab">
                            <div class="grid">
                                <h2>
                                    <small>User information</small>
                                </h2>
                                <hr class="thin" />
                                <br>
                                <div class="row cell-auto-size">
                                    <div class="cell cell-auto-size">
                                        <div class="input-control text" data-role="input">
                                            <label for="editor_user_input_user_full_name">User Full Name:</label>
                                            <input type="text" value-bind="m_fullName" name="editor_user_input_user_full_name" id="editor_user_input_user_full_name">
                                            <button class="button helper-button clear" tabindex="-1" type="button">
                                                <span class="mif-cross"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="cell cell-auto-size">
                                        <div class="input-control text" data-role="input">
                                            <label for="editor_user_input_username">User email:</label>
                                            <input type="text" value-bind="m_email" name="editor_user_input_username" id="editor_user_input_username">
                                            <button class="button helper-button clear" tabindex="-1" type="button">
                                                <span class="mif-cross"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="cell cell-auto-size">
                                        <div class="input-control text" data-role="input">
                                            <label for="editor_user_input_customer_id">Customer Id:</label>
                                            <input type="number" name="editor_user_input_customer_id" id="editor_user_input_customer_id">
                                            <button class="button helper-button reveal" tabindex="-1" type="button">
                                                <span class="mif-looks"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <br/>

                                <div class="row cell-auto-size">

                                    <div class="cell cell-auto-size">
                                        <div class="input-control password" data-role="input">
                                            <label for="editor_user_input_password">User password:</label>
                                            <input type="password" value-bind="m_password" name="editor_user_input_password" id="editor_user_input_password">
                                            <button class="button helper-button reveal" tabindex="-1" type="button">
                                                <span class="mif-looks"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="cell cell-auto-size">
                                        <div class="input-control password" data-role="input">
                                            <label for="editor_user_input_password_confirm">Password Confirm:</label>
                                            <input type="password" elem-bind="m_inputPasswordConfirm" name="editor_user_input_password_confirm" id="editor_user_input_password_confirm">
                                            <button class="button helper-button reveal" tabindex="-1" type="button">
                                                <span class="mif-looks"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="cell cell-auto-size">
                                        <div class="input-control text" data-role="input">
                                            <label for="editor_user_input_num_activations">Activations Permitted:</label>
                                            <input type="number" num-value-bind="m_numActivations" name="editor_user_input_num_activations" id="editor_user_input_num_activations">
                                            <button class="button helper-button clear" type="button">
                                                <span class="mif-cross"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <br/>

                                <div class="row cell-auto-size">
                                    <div class="cell cell-auto-size">
                                        <div class="input-control select" data-role="input">
                                            <label for="editor_user_input_group_id">Group:</label>
                                            <select name="editor_user_input_group_id" id="editor_user_input_group_id">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="cell cell-auto-size">
                                        <div class="input-control select" data-role="input">
                                            <label for="editor_user_input_role_id">Role:</label>
                                            <select name="editor_user_input_role_id" id="editor_user_input_role_id">
                                                @foreach($roles as $role)
                                                <option value="{{ $role->id }}"> {{ $role->display_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="cell">
                                        <label class="option-label" for="editor_user_input_isactive">Enabled:</label>
                                        <label class="switch-original margin-left-add-30">
                                            <input type="checkbox" id="editor_user_input_isactive" name="editor_user_input_isactive">
                                            <span class="check"></span>
                                        </label>
                                        <br />
                                        <br />
                                        <label class="option-label" for="editor_user_report_level">Report Level:</label>
                                        <label class="switch-original margin-left-add-30">
                                            <input type="checkbox" id="editor_user_report_level" name="editor_user_report_level">
                                            <span class="check"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="row cells3">
                                    <div class="cell">
                                        <div class="input-control text" data-role="input">
                                            <label for="user_editor_cfg_bypasses_allowed_input">Bypasses Allowed Per Day</label>
                                            <input type="number" id="user_editor_cfg_bypasses_allowed_input" name="user_editor_cfg_bypasses_allowed_input" num-value-bind="m_numBypassesPermitted">
                                        </div>
                                    </div>

                                    <div class="cell">
                                        <div class="input-control text" data-role="input">
                                            <label for="user_editor_cfg_bypass_duration_input" title="Bypass Period (Minutes)">Bypass Period</label>
                                            <input type="number" id="user_editor_cfg_bypass_duration_input" name="user_editor_cfg_bypass_duration_input" num-value-bind="m_bypassDuration">
                                        </div>
                                    </div>
                                </div>

                                <div class="row cell-auto-size">
                                    <div class="cell cell-auto-size">
                                        <div class="input-control password" data-role="input">
                                            <label for="editor_user_relaxed_policy_passcode">Relaxed Policy Passcode:</label>
                                            <input type="password" value-bind="m_relaxedPolicyPasscode"
                                                name="editor_user_relaxed_policy_passcode"
                                                id="editor_user_relaxed_policy_passcode" />
                                            <button class="button helper-button reveal" tabindex="-1" type="button">
                                                <span class="mif-looks"></span>
                                            </button>
                                        </div>
                                        <div>
                                            <label class="option-label" for="editor_user_input_passcode_enabled">Enabled</label>
                                            <label class="switch-original">
                                                <input type="checkbox" id="editor_user_input_passcode_enabled">
                                                <span class="check"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row cell-auto-size" id="div_08c2_2">
                                    <div id="user_form_errors">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="frame" id="user_activation_tab">
                            <div class="grid" id="activation_list">
                                <h2>
                                    <small>User Activations</small>
                                </h2>
                                <hr class="thin" />
                                <table id="user_activation_table" class="table striped hovered border" style="width:100%">

                                </table>
                            </div>
                        </div>

                        <div class="frame" id="self_moderation_tab">
                            <div class="grid" id="self_moderation_list">
                                <h2>
                                    <small>User's Blocked Sites</small>
                                </h2>
                                <hr class="thin" />
                                <button type="button" event-click="addNewSelfModerationSite" class="button primary">Add Site</button>

                                <table id="self_moderation_table" class="table striped hovered border black-background" style="width: 100%">
                                    <thead>
                                        <tr>
                                            <th>Site</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                    </thead>
                                    <tbody id="self_moderation_insert">

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="frame" id="custom_whitelist_tab">
                            <div class="grid">
                                <h2>
                                    <small>User's Allowed Sites</small>
                                </h2>
                                <hr class="thin" />
                                <button type="button" event-click="addNewWhitelistSite" class="button primary">Add Site</button>

                                <table id="custom_whitelist_table" class="table striped hovered border black-background" style="width: 100%">
                                    <thead>
                                        <tr>
                                            <th>Site</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                    </thead>
                                    <tbody id="custom_whitelist_insert">

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="frame" id="trigger_blacklist_tab">
                            <div class="grid">
                                <h2>
                                    <small>User's Blocked Triggers</small>
                                </h2>
                                <hr class="thin" />
                                <button type="button" event-click="addNewCustomTextTrigger" class="button primary">Add Text Trigger</button>

                                <table id="custom_trigger_table" class="table striped hovered border black-background" style="width: 100%">
                                    <thead>
                                        <tr>
                                            <th>Text Trigger</th>
                                            <th>...</th>
                                            <th>...</th>
                                        </tr>
                                    </thead>
                                    <tbody id="custom_trigger_insert">

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="frame" id="time_restrictions_tab">

                            <div class="grid">
                                <h2>
                                    <small>Time Restrictions</small>
                                </h2>
                                <hr class="thin" />
                                <div id="time_restrictions">
                                    <button class="button" type="button" event-click="eveningRestrictionsPreset">Evening</button>
                                    <button class="button" type="button" event-click="officeRestrictionsPreset">Office</button>
                                    <button class="button" type="button" event-click="noneRestrictionsPreset">None</button>

                                    @php
                                    $days = [["monday", "Monday"], ["tuesday", "Tuesday"], ["wednesday", "Wednesday"], ["thursday", "Thursday"], ["friday", "Friday"], ["saturday", "Saturday"], ["sunday", "Sunday"]];
                                    @endphp

                                    @foreach($days as $day)
                                        <div class="restrictions-row">
                                            <div class="checkbox">
                                                <input type="checkbox" id="{{$day[0]}}_checkbox" event-change="timeRestrictions.{{$day[0]}}.generateInternetLabel" value-bind="timeRestrictions.{{$day[0]}}.RestrictionsEnabled" />
                                                <label for="{{$day[0]}}_checkbox">{{$day[1]}}</label>
                                            </div>
                                            <div class="slider" data-caption="{{$day[1]}}" id="{{$day[0]}}"></div>
                                        </div>
                                        <div style="clear: both;"></div>
                                        <div class="restrictions-label" text-bind="timeRestrictions.{{$day[0]}}.internetLabel"></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row cell-auto-size">
                        <div class="form-actions">
                            <button id="user_editor_submit" type="submit" class="button primary">Create User</button>
                            <button id="user_editor_cancel" type="button" event-click="cancelClick" class="button link">Cancel</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>