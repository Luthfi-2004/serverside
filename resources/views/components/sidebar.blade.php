<div class="vertical-menu">
    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

            <li>
                <a href="{{ route('dashboard') }}" class="waves-effect {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="{{ request()->routeIs('serverside.index') ? 'mm-active' : '' }}">
                <a href="{{ route('serverside.index') }}"
                    class="waves-effect {{ request()->routeIs('serverside.index') ? 'active' : '' }}">
                    <i class="ri-flask-line"></i>
                    <span>Server Side</span>
                </a>
            </li>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>