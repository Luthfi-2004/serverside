@php
  $isDash = request()->routeIs('dashboard');

  // JSH group
  $isJshIndex = request()->routeIs('greensand.index');
  $isJshGfn = request()->routeIs('jshgfn.*');
  $isJshStd = request()->routeIs('greensand.standards');
  $isJshOpen = $isJshIndex || $isJshGfn || $isJshStd;

  // ACE group
  $isAceIndex = request()->routeIs('ace.index');
  $isAceGfn = request()->routeIs('acelinegfn.*');
  $isAceStd = request()->routeIs('ace.standards');
  $isAceOpen = $isAceIndex || $isAceGfn || $isAceStd;

  // Admin group
  $isAdminUser = request()->routeIs('admin.users.*');

  $isAdmin = auth()->check() && auth()->user()->role === 'admin';
@endphp

<div class="vertical-menu">
  <div data-simplebar class="h-100">
    <div id="sidebar-menu">
      <ul class="metismenu list-unstyled" id="side-menu">
        <li class="menu-title">Menu</li>

        {{-- Dashboard --}}
        <li class="{{ $isDash ? 'mm-active' : '' }}">
          <a href="{{ route('dashboard') }}" class="waves-effect {{ $isDash ? 'active' : '' }}">
            <i class="ri-dashboard-line"></i>
            <span>Dashboard</span>
          </a>
        </li>
        {{-- Admin: Kelola User --}}
        @if($isAdmin)
          <li class="{{ $isAdminUser ? 'mm-active' : '' }}">
            <a href="{{ route('admin.users.index') }}" class="waves-effect {{ $isAdminUser ? 'active' : '' }}">
              <i class="ri-user-settings-line"></i>
              <span>Kelola User</span>
            </a>
          </li>
        @endif

        {{-- Green Sand --}}
        <li class="menu-title">Green Sand</li>

        {{-- JSH LINE --}}
        <li class="{{ $isJshOpen ? 'mm-active' : '' }}">
          <a href="javascript:void(0);" class="has-arrow waves-effect {{ $isJshOpen ? 'aria-expanded' : '' }}"
            aria-expanded="{{ $isJshOpen ? 'true' : 'false' }}">
            <i class="ri-flask-line"></i>
            <span>JSH LINE</span>
          </a>
          <ul class="sub-menu" aria-expanded="{{ $isJshOpen ? 'true' : 'false' }}">
            <li class="{{ $isJshIndex ? 'mm-active' : '' }}">
              <a href="{{ route('greensand.index') }}" class="{{ $isJshIndex ? 'active' : '' }}">
                Daily Check
              </a>
            </li>
            <li class="{{ $isJshGfn ? 'mm-active' : '' }}">
              <a href="{{ route('jshgfn.index') }}" class="{{ $isJshGfn ? 'active' : '' }}">
                GFN
              </a>
            </li>
            @if($isAdmin)
              <li class="{{ $isJshStd ? 'mm-active' : '' }}">
                <a href="{{ route('greensand.standards') }}" class="{{ $isJshStd ? 'active' : '' }}">
                  Standards
                </a>
              </li>
            @endif
          </ul>
        </li>
        {{-- END JSH LINE --}}

        {{-- ACE LINE --}}
        <li class="{{ $isAceOpen ? 'mm-active' : '' }}">
          <a href="javascript:void(0);" class="has-arrow waves-effect {{ $isAceOpen ? 'aria-expanded' : '' }}"
            aria-expanded="{{ $isAceOpen ? 'true' : 'false' }}">
            <i class="ri-flask-line"></i>
            <span>ACE LINE</span>
          </a>
          <ul class="sub-menu" aria-expanded="{{ $isAceOpen ? 'true' : 'false' }}">
            <li class="{{ $isAceIndex ? 'mm-active' : '' }}">
              <a href="{{ route('ace.index') }}" class="{{ $isAceIndex ? 'active' : '' }}">
                Daily Check
              </a>
            </li>
            <li class="{{ $isAceGfn ? 'mm-active' : '' }}">
              <a href="{{ route('acelinegfn.index') }}" class="{{ $isAceGfn ? 'active' : '' }}">
                GFN
              </a>
            </li>
            @if($isAdmin)
              <li class="{{ $isAceStd ? 'mm-active' : '' }}">
                <a href="{{ route('ace.standards') }}" class="{{ $isAceStd ? 'active' : '' }}">
                  Standards
                </a>
              </li>
            @endif
          </ul>
        </li>
        {{-- END ACE LINE --}}



      </ul>
    </div>
  </div>
</div>