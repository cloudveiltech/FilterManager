<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/userrecord.js') }}">
</script>

<div class="bg-dark overlay" id="overlay_self_moderation_editor">
    <div id="self_moderation_editor_body">
        <div class="flex-grid">
            <h1 id="self_moderation_title" text-bind="title">Edit Site</h1>

            <form method="post" action="javascript:void(0)" elem-bind="form" event-submit="onSubmit" data-on-submit="submit">
                <div class="grid">
                    <div class="row cell-auto-size">
                        <div class="cell cell-auto-size">
                            <div class="input-control text" data-role="input">
                                <label for="editor_self_moderation_input_site">Site:</label>
                                <input type="text" value-bind="site" name="editor_self_moderation_input_site" id="editor_self_moderation_input_site">
                            </div>
                        </div>
                    </div>

                    <div class="row cell-auto-size">
                        <div class="form-actions">
                            <button id="self_moderation_submit" type="submit" class="button primary">Save</button>
                            <button id="self_moderation_cancel" event-click="cancelClick" type="button" class="button link">Cancel</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

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
                            <a href="#self_moderation_tab">User's self-moderation</a>
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
                                    <small>Self-moderation</small>
                                </h2>
                                <hr class="thin" />
                                <button type="button" event-click="addNewSelfModerationSite" class="button primary">Add Site</button>

                                <table id="self_moderation_table" class="table striped hovered border" style="width: 100%">

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
                                    <div class="slider" id="monday"></div>
                                    <div class="slider" id="tuesday"></div>
                                    <div class="slider" id="wednesday"></div>
                                    <div class="slider" id="thursday"></div>
                                    <div class="slider" id="friday"></div>
                                    <div class="slider" id="saturday"></div>
                                    <div class="slider" id="monday"></div>
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