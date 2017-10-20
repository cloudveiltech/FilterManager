<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/records/userrecord.js') }}">
</script>


<!-- User Editing Overlay -->
<div class="bg-dark" id="overlay_user_editor">
    <div id="div_08c2_0">
        <div class="flex-grid">

            <h1 id="user_editing_title">Create New User</h1>
            <br/>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="editor_user_form" data-on-submit="submit">

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_user_input_user_full_name">User Full Name:</label>
                            <input type="text" name="editor_user_input_user_full_name" id="editor_user_input_user_full_name">
                            <button class="button helper-button clear" tabindex="-1" type="button">
                                <span class="mif-cross"></span>
                            </button>
                        </div>
                    </div>

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_user_input_username">User email:</label>
                            <input type="text" name="editor_user_input_username" id="editor_user_input_username">
                            <button class="button helper-button clear" tabindex="-1" type="button">
                                <span class="mif-cross"></span>
                            </button>
                        </div>
                    </div>

                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="editor_user_input_num_activations">Activations Permitted:</label>
                            <input type="number" name="editor_user_input_num_activations" id="editor_user_input_num_activations">
                            <button class="button helper-button clear" type="button">
                                <span class="mif-cross"></span>
                            </button>
                        </div>
                    </div>

                </div>

                <br/>
                <br/>

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control password" data-role="input">
                            <label for="editor_user_input_password">User password:</label>
                            <input type="password" name="editor_user_input_password" id="editor_user_input_password">
                            <button class="button helper-button reveal" tabindex="-1" type="button">
                                <span class="mif-looks"></span>
                            </button>
                        </div>
                    </div>

                    <div class="cell cell-auto-size">
                        <div class="input-control password" data-role="input">
                            <label for="editor_user_input_password_confirm">Password Confirm:</label>
                            <input type="password" name="editor_user_input_password_confirm" id="editor_user_input_password_confirm">
                            <button class="button helper-button reveal" tabindex="-1" type="button">
                                <span class="mif-looks"></span>
                            </button>
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

                </div>

                <br/>
                <br/>

                <div class="row cell-auto-size">

                    <div class="cell cell-auto-size">
                        <div class="input-control select" data-role="input">
                            <label for="editor_user_input_group_id">Group:</label>
                            <select name="editor_user_input_group_id" id="editor_user_input_group_id">
                            </select>
                        </div>
                    </div>

                    <div class="cell size4" id="div_08c2_1">
                        <label for="editor_user_input_isactive">Enabled:</label>
                        <label class="switch-original" id="label_08c2_0">
                            <input type="checkbox" id="editor_user_input_isactive" name="editor_user_input_isactive">
                            <span class="check"></span>
                        </label>
                    </div>
                </div>

                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="user_editor_submit" type="submit" class="button primary">Create User</button>
                        <button id="user_editor_cancel" type="button" class="button link">Cancel</button>
                    </div>
                </div>

                <div class="row cell-auto-size" id="div_08c2_2">
                    <div id="user_form_errors">
                    </div>
                </div>
            </form>
        </div>
        <hr class="thin">
        <div class="grid" id="userdetail_board">
            <div class="row cells2">
                <div class="cell">
                    <ul>
                        <li>
                            <label>UserId</label>
                            <label id="user_id">1</label>
                        </li>
                        <li>
                            <label>Identifier</label>
                            <label id="identifier">2389EDEA888AEE2B83640B</label>
                        </li>
                        <li>
                            <label>DeviceId</label>
                            <label id="device_id">LAPTOP-P3BKMQcC</label>
                        </li>
                    </ul>
                </div>
                <div class="cell">
                    <ul>
                        <li>
                            <label>Date Created</label>
                            <label id="created_at">2017-10-19 04:26:28</label>
                        </li>
                        <li>
                            <label>Date Updated</label>
                            <label id="updated_at">2017-10-20 06:02:52</label>
                        </li>
                        <li>
                            <label>IPaddress</label>
                            <label id="ip_address">66.112.182.250</label>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>