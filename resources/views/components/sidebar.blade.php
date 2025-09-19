<div class="vertical-menu">
  <div data-simplebar class="h-100">

    <!--- Sidemenu -->
    <div id="sidebar-menu">
      <ul class="metismenu list-unstyled" id="side-menu">
        <li class="menu-title">Menu</li>

        <!-- Dashboard -->
        <li class="{{ request()->routeIs('dashboard') ? 'mm-active' : '' }}">
          <a href="{{ route('dashboard') }}"
             class="waves-effect {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="ri-dashboard-line"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <!-- Greensand Parent -->
        <li class="{{ request()->routeIs('greensand.*') || request()->routeIs('jshgfn.*') ? 'mm-active' : '' }}">
          <a href="javascript:void(0);" class="has-arrow waves-effect">
            <i class="ri-flask-line"></i>
            <span>Greensand</span>
          </a>
          <ul class="sub-menu" aria-expanded="false">
            <li class="{{ request()->routeIs('greensand.index') ? 'mm-active' : '' }}">
              <a href="{{ route('greensand.index') }}"
                 class="{{ request()->routeIs('greensand.index') ? 'active' : '' }}">
                Green Sand Daily Check
              </a>
            </li>
            <li class="{{ request()->routeIs('jshgfn.*') ? 'mm-active' : '' }}">
              <a href="{{ route('jshgfn.index') }}"
                 class="{{ request()->routeIs('jshgfn.*') ? 'active' : '' }}">
                Green Sand GFN 
              </a>
            </li>
          </ul>
        </li>

        <!-- ====== MENU BARU (UI aja) ====== -->
        <li>
          <a href="javascript:void(0);" class="has-arrow waves-effect">
            <i class="ri-apps-line"></i>
            <span>Menu Baru</span>
          </a>
          <ul class="sub-menu" aria-expanded="false">
            <li><a href="javascript:void(0);">Submenu 1</a></li>
            <li><a href="javascript:void(0);">Submenu 2</a></li>
            <li><a href="javascript:void(0);">Submenu 3</a></li>
          </ul>
        </li>
        <!-- ====== END MENU BARU ====== -->

      </ul>
    </div>
    <!-- Sidebar -->
  </div>
</div>
