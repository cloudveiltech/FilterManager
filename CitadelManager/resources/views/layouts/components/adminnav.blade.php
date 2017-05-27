<!-- Dashboard primary menu. -->
<div class="app-bar fixed-top darcula row" data-role="appbar" style="background: #261E45;">

    <!-- Show configured site name. -->
    <a class="app-bar-element branding">{{ config('app.name', 'Citadel') }}</a>
    <span class="app-bar-divider"></span>

    <!-- Right hand side menu container. -->
    <div class="app-bar-element place-right">

        <!-- Drop down with primary title has logged user's username. -->
        <span class="dropdown-toggle"><span class="mif-cog"></span>
            {{ Auth::user()->name }}
        </span>
        <div class="app-bar-drop-container padding10 place-right no-margin-top block-shadow fg-dark" data-role="dropdown" data-no-close="true" style="width: 220px">
            <h2 class="text-light">Your Account</h2>
            <ul class="unstyled-list fg-dark">

                <!-- Sign out button. -->
                <li id='btn_sign_out'><a href="#" class="fg-white3 fg-hover-yellow">Sign Out</a></li>
            </ul>
        </div>
    </div>

    <!-- Responsive menu for mobile. -->
    <div class="app-bar-pullbutton automatic" style="display: none;"></div>
    <div class="clearfix" style="width: 0;"></div>
    <nav class="app-bar-pullmenu hidden flexstyle-app-bar-menu" style="display: none;">
        <ul class="app-bar-pullmenubar hidden app-bar-menu"></ul>
    </nav>
</div>