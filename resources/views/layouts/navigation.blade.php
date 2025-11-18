<ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">{{ __('common.navigation.dashboard') }}</x-nav-link>
    
    @canany(['tenants.view', 'halls.view', 'inventory.view', 'rentals.view', 'protocols.view'])
        <li class="nav-divider"></li>
        <li class="nav-group {{ request()->routeIs(['tenants.*', 'halls.*', 'inventory-items.*', 'rentals.*', 'protocols.*']) ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <i class="nav-icon fas fa-building"></i> {{ __('common.navigation.rental_system') }}
            </a>
            <ul class="nav-group-items">
                @can('tenants.view')
                    <x-nav-link :href="route('tenants.index')" :active="request()->routeIs('tenants.*')" icon="user">{{ __('common.navigation.tenants') }}</x-nav-link>
                @endcan
                @can('halls.view')
                    <x-nav-link :href="route('halls.index')" :active="request()->routeIs('halls.*')" icon="warehouse">{{ __('common.navigation.halls') }}</x-nav-link>
                @endcan
                @can('inventory.view')
                    <x-nav-link :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')" icon="clipboard-list">{{ __('common.navigation.inventory') }}</x-nav-link>
                @endcan
                @can('rentals.view')
                    <x-nav-link :href="route('rentals.index')" :active="request()->routeIs('rentals.*')" icon="calendar-check">{{ __('common.navigation.rentals') }}</x-nav-link>
                @endcan
                @can('protocols.view')
                    <x-nav-link :href="route('protocols.index')" :active="request()->routeIs('protocols.*')" icon="file-signature">{{ __('common.navigation.protocols') }}</x-nav-link>
                @endcan
            </ul>
        </li>
    @endcanany
    
    @canany(['orders.view', 'orders.create', 'orders.pack', 'analytics.view'])
        <li class="nav-divider"></li>
        <li class="nav-group {{ request()->routeIs(['articles.*', 'orders.*', 'packing.*', 'analytics.*']) ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <i class="nav-icon fas fa-shopping-cart"></i> {{ __('common.navigation.order_management') }}
            </a>
            <ul class="nav-group-items">
                @can('orders.create')
                    <x-nav-link :href="route('articles.index')" :active="request()->routeIs('articles.*')" icon="tags">{{ __('common.navigation.articles') }}</x-nav-link>
                @endcan
                @can('orders.view')
                    <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')" icon="shopping-cart">{{ __('common.navigation.orders') }}</x-nav-link>
                @endcan
                @can('orders.pack')
                    <x-nav-link :href="route('packing.index')" :active="request()->routeIs('packing.*')" icon="box">{{ __('common.navigation.packing') }}</x-nav-link>
                @endcan
                @can('analytics.view')
                    <x-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')" icon="chart-line">{{ __('common.navigation.analytics') }}</x-nav-link>
                @endcan
            </ul>
        </li>
    @endcanany
    
    @canany(['user.list', 'role.list', 'perm.list'])
        <li class="nav-divider"></li>
        <li class="nav-group {{ request()->routeIs(['users.*', 'roles.*', 'permissions.*']) ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <i class="nav-icon fas fa-cog"></i> {{ __('common.navigation.administration') }}
            </a>
            <ul class="nav-group-items">
                @can('user.list')
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">{{ __('common.navigation.users') }}</x-nav-link>
                @endcan
                @can('role.list')
                    <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="user-gear">{{ __('common.navigation.roles') }}</x-nav-link>
                @endcan
                {{-- @can('perm.list')
                    <x-nav-link :href="route('permissions.index')" :active="request()->routeIs('permissions.*')" icon="shield-halved">{{ __('common.navigation.permissions') }}</x-nav-link>
                @endcan --}}
            </ul>
        </li>
    @endcanany
</ul>
