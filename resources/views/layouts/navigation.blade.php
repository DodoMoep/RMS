<ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">{{ __('Dashboard') }}</x-nav-link>
    <li class="nav-divider"></li>
    <li class="nav-title">Hallenvermietung</li>
    <x-nav-link :href="route('tenants.index')" :active="request()->routeIs('tenants.*')" icon="user">Mieter</x-nav-link>
    <x-nav-link :href="route('halls.index')" :active="request()->routeIs('halls.*')" icon="warehouse">Hallen</x-nav-link>
    <x-nav-link :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')" icon="clipboard-list">Inventar</x-nav-link>
    <x-nav-link :href="route('rentals.index')" :active="request()->routeIs('rentals.*')" icon="calendar-check">Vermietungen</x-nav-link>
    <x-nav-link :href="route('protocols.index')" :active="request()->routeIs('protocols.*')" icon="file-signature">Protokolle</x-nav-link>
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
</ul>
