<!-- Include User Record Record Type JS. -->
<script src="{{ asset('js/admin/platform.js') }}">
</script>


<!-- System Version Overlay -->
<div class="bg-dark"  id="overlay_platform">
    <div id="overlay_platform_0101">
        <div class="flex-grid">
            <h1 id='platform_title'>Platforms</h1>
            <hr class="thin">
            <br/>
            <form method="post" action="javascript:void(0)" id="platform_form" data-on-submit="submit">
                <input type='hidden' value='0' id='platform_id' />
                <div class="row cell-auto-size padding-left-100 margin-20">
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="platform_type">Platform Type:</label>
                            <select id='platform_type' name='platform_type' />
                                <option value="WIN">Windows</option>
                                <option value="LINUX">Linux</option>
                                <option value="OSX">MacOS</option>
                            </select>
                        </div>
                    </div>
                    <div class="cell cell-auto-size">
                        <div class="input-control text" data-role="input">
                            <label for="platform_input_os_name">OS Name:</label>
                            <input type="text"  name="platform_input_os_name" id="platform_input_os_name" >
                            <button class="button helper-button clear" tabindex="-1" type="button"><span class="mif-cross"></span></button>
                        </div>
                    </div>
                    
                    <div class="cell cell-auto-size">
                        <button id='btn_platform_add' class="button primary" type="button">Add</button>
                        <button id='btn_platform_cancel' class="button primary" type="button">Cancel</button>
                    </div>
                </div>
                <hr class="thin margin-left-50">
                <div class="row cell-auto-size padding-left-100 margin-20">
                    <div class="cell cell-auto-size">
                        <span class='sub-title'>Platforms</span> &nbsp;
                        <img id='spin_platforms' class='spin_loading' src="{{asset('img/loading7_navy_blue.gif')}}" width = "24px" />
                        <div class='version-div' id='platforms'>
                            
                        </div>
                    </div>
                </div>
                <br/>
                <br/>
                <div class="row cell-auto-size">
                    <div class="form-actions">
                        <button id="btn_platform_close" type="button" class="button link">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>