<!-- Include Version Record Record Type JS. -->
<script src="{{ asset('js/admin/records/versionrecord.js') }}">
</script> 


<!-- System Version Overlay -->
<div class="bg-dark"  id="overlay_system_version">
    <div id="overlay_system_0101">
        <div class="flex-grid">
            <h1 id='overlay_system_title'>System Versions</h1>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="system_version_form" data-on-submit="submit">
                
                <div class="row cell-auto-size padding-left-100 margin-20">
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="platform_os_name">Platform:</label>
                            <select id='platform_os_name' name='platform_os_name' />
                                <option value=0>Loading...</option>
                            </select>
                        </div>
                    </div>
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_app_name">App Name:</label>
                            <input type="text"  name="system_version_input_app_name" id="system_version_input_app_name" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_file_name">File Name:</label>
                            <input type="text"  name="system_version_input_file_name" id="system_version_input_file_name" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>

                <div class="row cell-auto-size padding-left-100 margin-20">
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_alpha_version">Alpha Version:</label>
                            <input type="text"  name="system_version_input_alpha_version" id="system_version_input_alpha_version" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_beta_version">Beta Version:</label>
                            <input type="text"  name="system_version_input_beta_version" id="system_version_input_beta_version" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_stable_version">Stable Version:</label>
                            <input type="text"  name="system_version_input_stable_version" id="system_version_input_stable_version" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>

                <div class="row cell-auto-size padding-left-100 margin-20">
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_rdate">Release Date:</label>
                            <input type="text"  name="system_version_input_rdate" id="system_version_input_rdate" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    <div class="cell cell-auto-size">
                    <label class="option-label" for="system_version_default_version">Default Version:</label>                        
                        <label class="switch-original margin-left-add-30" >
                            <input type="checkbox" id="system_version_default_version" name="system_version_default_version">
                            <span class="check"></span>
                        </label>
                    </div>
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="system_version_input_changes">Changes:</label>
                            <input name="system_version_input_changes" id="system_version_input_changes">
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                </div>
                <hr class="thin margin-left-50">
                
                <div class="row cell-auto-size">
                    <div class="form-actions margin-left-50">
                        <button id='btn_add' class="button primary" type="submit">Save</button>
                        
                        <button id="system_version_close" type="button" class="button link">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>