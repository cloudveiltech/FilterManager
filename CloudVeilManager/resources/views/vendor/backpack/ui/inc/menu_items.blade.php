{{-- This file is used for menu items by any Backpack v6 theme
{{-- <li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>--}}

<x-backpack::menu-item title="Users" :link="backpack_url('user')" />
<x-backpack::menu-item title="Groups" :link="backpack_url('group')" />
<x-backpack::menu-item title="Filter lists" :link="backpack_url('filter-list')" />
<x-backpack::menu-item title="Deactivation requests" :link="backpack_url('deactivation-request')" />
<x-backpack::menu-item title="Apps" :link="backpack_url('app')" />
<x-backpack::menu-item title="App groups" :link="backpack_url('app-group')" />
<x-backpack::menu-item title="Activations" :link="backpack_url('app-user-activation')" />
<x-backpack::menu-item title="System versions" :link="backpack_url('system-version')" />
