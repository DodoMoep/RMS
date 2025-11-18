<ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">{{ __('Dashboard') }}</x-nav-link>
    @canany(['tenants.view', 'halls.view', 'inventory.view', 'rentals.view', 'protocols.view'])
        <li class="nav-divider"></li>
        <li class="nav-title">Hallenvermietung</li>
        @can('tenants.view')
            <x-nav-link :href="route('tenants.index')" :active="request()->routeIs('tenants.*')" icon="user">Mieter</x-nav-link>
        @endcan
        @can('halls.view')
            <x-nav-link :href="route('halls.index')" :active="request()->routeIs('halls.*')" icon="warehouse">Hallen</x-nav-link>
        @endcan
        @can('inventory.view')
            <x-nav-link :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')" icon="clipboard-list">Inventar</x-nav-link>
        @endcan
        @can('rentals.view')
            <x-nav-link :href="route('rentals.index')" :active="request()->routeIs('rentals.*')" icon="calendar-check">Vermietungen</x-nav-link>
        @endcan
        @can('protocols.view')
            <x-nav-link :href="route('protocols.index')" :active="request()->routeIs('protocols.*')" icon="file-signature">Protokolle</x-nav-link>
        @endcan
    @endcanany
    @canany(['orders.view', 'orders.create', 'orders.pack', 'analytics.view'])
        <li class="nav-divider"></li>
        <li class="nav-title">Bestellverwaltung</li>
        @can('orders.create')
            <x-nav-link :href="route('articles.index')" :active="request()->routeIs('articles.*')" icon="tags">Artikel</x-nav-link>
        @endcan
        @can('orders.view')
            <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')" icon="shopping-cart">Bestellungen</x-nav-link>
        @endcan
        @can('orders.pack')
            <x-nav-link :href="route('packing.index')" :active="request()->routeIs('packing.*')" icon="box">Verpackung</x-nav-link>
        @endcan
        @can('analytics.view')
            <x-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')" icon="chart-line">Analytik</x-nav-link>
        @endcan
    @endcanany
    @canany(['user.list', 'role.list', 'perm.list'])
        <li class="nav-divider"></li>
        <li class="nav-title">Stammdatenverwaltung</li>
        @can('user.list')
            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">Benutzer</x-nav-link>
        @endcan
        @can('role.list')
            <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="user-gear">Rollen</x-nav-link>
        @endcan
        {{-- @can('perm.list')
            <x-nav-link :href="route('permissions.index')" :active="request()->routeIs('permissions.*')" icon="shield-halved">Berechtigungen</x-nav-link>
        @endcan --}}
    @endcanany
</ul>
