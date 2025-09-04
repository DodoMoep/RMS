
<!DOCTYPE html><!--
* CoreUI - Free Bootstrap Admin Template
* @version v5.0.0
* @link https://coreui.io/product/free-bootstrap-admin-template/
* Copyright (c) 2024 creativeLabs Łukasz Holeczek
* Licensed under MIT (https://github.com/coreui/coreui-free-bootstrap-admin-template/blob/main/LICENSE)
-->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <base href="./">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
            <div class="sidebar-header border-bottom">
                <div class="sidebar-brand">
                    <img src="{{ asset('assets/images/rms.png') }}" class="sidebar-brand-full" width="121" height="32" title="{{ config('app.name', 'Laravel') }}" alt="{{ config('app.name', 'Laravel') }}" />
                    <img src="{{ asset('assets/images/rms_icon.png') }}" class="sidebar-brand-narrow" width="32" height="32" title="{{ config('app.name', 'Laravel') }}" alt="{{ config('app.name', 'Laravel') }}" />
                </div>
                <button class="btn-close d-lg-none" type="button" data-coreui-dismiss="offcanvas" data-coreui-theme="dark" aria-label="Close" onclick="coreui.Sidebar.getInstance(document.querySelector(&quot;#sidebar&quot;)).toggle()"></button>
            </div>
            @include('layouts.navigation')
            <div class="sidebar-footer border-top d-none d-md-flex">
                <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
            </div>
        </div>
        <div class="wrapper d-flex flex-column min-vh-100">
            <header class="header header-sticky p-0 mb-4">
                <div class="container-fluid border-bottom px-4">
                    <button class="header-toggler" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()" style="margin-inline-start: -14px;">
                        <i class="fas fa-list"></i>
                    </button>
                    <ul class="header-nav">
                        <li class="nav-item py-1">
                            <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                <div class="avatar avatar-md"><img class="avatar-img" src="{{ auth()->user()->avatar_url }}" alt="{{ Auth::user()->email }}"></div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end pt-0">
                                <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold my-2">
                                    <div class="fw-semibold">Settings</div>
                                </div>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="icon me-2 fas fa-id-badge"></i> Profile
                                </a>

                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                                        <i class="icon me-2 fas fa-right-from-bracket"></i> {{ __('Log Out') }}
                                    </a>
                                </form>
                            </div>
                        </li>
                    </ul>
                </div>
            </header>
            <div class="body flex-grow-1">
                <div class="container-lg px-4">
                    {{ $slot }}
                </div>
            </div>
            <footer class="footer px-4">
                <div><a href="https://coreui.io">CoreUI </a><a href="https://coreui.io/product/free-bootstrap-admin-template/">Bootstrap Admin Template</a> © 2024 creativeLabs.</div>
                <div class="ms-auto">Powered by&nbsp;<a href="https://coreui.io/docs/">CoreUI UI Components</a></div>
            </footer>
        </div>
        @stack('scripts')
    </body>
</html>
