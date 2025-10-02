<div class="vertical-menu">
  <div data-simplebar class="h-100">
    <div id="sidebar-menu">
      <ul class="metismenu list-unstyled" id="side-menu">
        <li class="menu-title">Menu</li>

        <!-- Dashboard -->
        <li class="{{ request()->routeIs('dashboard') ? 'mm-active' : '' }}">
          <a href="{{ route('dashboard') }}" class="waves-effect {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="ri-dashboard-line"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <!-- Green Sand -->
        <li class="menu-title">Green Sand</li>

        <!-- JSH LINE -->
        <li class="{{ request()->routeIs('greensand.*') || request()->routeIs('jshgfn.*') ? 'mm-active' : '' }}">
          <a href="javascript:void(0);" class="has-arrow waves-effect">
            <i class="ri-flask-line"></i>
            <span>JSH LINE</span>
          </a>
          <ul class="sub-menu" aria-expanded="false">
            <li class="{{ request()->routeIs('greensand.index') ? 'mm-active' : '' }}">
              <a href="{{ route('greensand.index') }}" class="{{ request()->routeIs('greensand.index') ? 'active' : '' }}">
                Daily Check
              </a>
            </li>
            <li class="{{ request()->routeIs('jshgfn.*') ? 'mm-active' : '' }}">
              <a href="{{ route('jshgfn.index') }}" class="{{ request()->routeIs('jshgfn.*') ? 'active' : '' }}">
                GFN
              </a>
            </li>
            <li class="{{ request()->routeIs('greensand.standards') ? 'mm-active' : '' }}">
              <a href="{{ route('greensand.standards') }}" class="{{ request()->routeIs('greensand.standards') ? 'active' : '' }}">
                Standards
              </a>
            </li>
          </ul>
        </li>
        <!-- END JSH LINE -->

        <!-- ACE LINE -->
        <li class="{{ request()->routeIs('ace.*') || request()->routeIs('acelinegfn.*') ? 'mm-active' : '' }}">
          <a href="javascript:void(0);" class="has-arrow waves-effect">
            <i class="ri-flask-line"></i>
            <span>ACE LINE</span>
          </a>
          <ul class="sub-menu" aria-expanded="false">
            <li class="{{ request()->routeIs('ace.index') ? 'mm-active' : '' }}">
              <a href="{{ route('ace.index') }}" class="{{ request()->routeIs('ace.index') ? 'active' : '' }}">
                Daily Check
              </a>
            </li>
            <li class="{{ request()->routeIs('acelinegfn.*') ? 'mm-active' : '' }}">
              <a href="{{ route('acelinegfn.index') }}" class="{{ request()->routeIs('acelinegfn.*') ? 'active' : '' }}">
                GFN
              </a>
            </li>
            <li class="{{ request()->routeIs('ace.standards') ? 'mm-active' : '' }}">
              <a href="{{ route('ace.standards') }}" class="{{ request()->routeIs('ace.standards') ? 'active' : '' }}">
                Standards
              </a>
            </li>
          </ul>
        </li>
        <!-- END ACE LINE -->

      </ul>
    </div>
  </div>
</div>
