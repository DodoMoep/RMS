<x-app-layout>
    <div class="row g-4">
        @can('rentals.view')
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('dashboard.today_rentals') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>{{ __('rentals.tenant') }}</th><th>{{ __('rentals.hall') }}</th><th>{{ __('rentals.start') }}</th><th>{{ __('rentals.end') }}</th><th>{{ __('rentals.status') }}</th><th>{{ __('protocols.list.action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($todayRentals as $r)
                                    <tr>
                                        <td>{{ $r->contact->name }}</td>
                                        <td>{{ $r->hall->name }}</td>
                                        <td>{{ $r->start->format('d.m. H:i') }}</td>
                                        <td>{{ $r->end->format('d.m. H:i') }}</td>
                                        <td><span class="badge text-bg-secondary">{{ __('rentals.statuses.'.$r->status) }}</span></td>
                                        <td class="text-nowrap">
                                            @can('rentals.handover')
                                                <form method="post" action="{{ route('rentals.handover',$r) }}" class="d-inline">@csrf
                                                    <button class="btn btn-link btn-sm">{{ __('protocols.types.handover') }}</button>
                                                </form>
                                            @endcan
                                            @can('rentals.return')
                                                <form method="post" action="{{ route('rentals.return',$r) }}" class="d-inline">@csrf
                                                    <button class="btn btn-link btn-sm">{{ __('protocols.types.return') }}</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">{{ __('common.messages.no_entries') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('protocols.view')
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">{{ __('dashboard.open_protocols') }}</h5>
                        <ul class="mb-0">
                            @forelse($openProtocols as $p)
                                <li class="mb-1">
                                    {{ __('protocols.types.'.$p->type) }} – {{ $p->rental->contact->name }} / {{ $p->rental->hall->name }}
                                    @can('protocols.create')
                                        <a class="ms-2" href="{{ route('protocol.form',$p) }}">{{ __('protocols.list.open') }}</a>
                                    @endcan
                                </li>
                            @empty
                                <li class="text-muted">{{ __('dashboard.no_open_protocols') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endcan

        @cannot('rentals.view')
            @cannot('protocols.view')
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-home fa-3x text-muted mb-3"></i>
                            <h5>{{ __('dashboard.welcome') }}</h5>
                            <p class="text-muted mb-0">{{ __('dashboard.welcome_hint') }}</p>
                        </div>
                    </div>
                </div>
            @endcannot
        @endcannot
    </div>
</x-app-layout>
