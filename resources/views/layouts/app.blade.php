<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title', $title ?? 'SandLab')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">


    {{-- Head --}}
    @include('components.head')
</head>

<body data-sidebar="dark">
    <div id="layout-wrapper">
        {{-- Topbar --}}
        @include('components.topbar')

        {{-- Sidebar --}}
        @include('components.sidebar')

        {{-- Content --}}
        <div class="main-content">
            @yield('content') 
        </div>
        
        {{-- Rightbar --}}
        @include('components.rightbar')
        
        {{-- Overlay --}}
        <div class="rightbar-overlay"></div>
        
        {{-- Scripts --}}
        @include('components.footer') </div>
        @include('components.scripts')
        @stack('scripts')
        @stack('styles')
</body>

</html>