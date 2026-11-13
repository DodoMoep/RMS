<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0">{{ __('inventory.title') }}</h1>
        <a href="{{ route('inventory-items.create') }}" class="btn btn-primary btn-sm">{{ __('inventory.new') }}</a>
    </div>

    <x-flash />

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>{{ __('inventory.designation') }}</th>
                    <th>{{ __('inventory.sku') }}</th>
                    <th>{{ __('inventory.description') }}</th>
                    <th class="text-end">{{ __('common.common.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->sku }}</td>
                        <td class="text-truncate" style="max-width: 300px;">{{ $item->description }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('inventory-items.edit',$item) }}" class="btn btn-link btn-sm">{{ __('common.common.edit') }}</a>
                            <form action="{{ route('inventory-items.destroy',$item) }}" method="post" class="d-inline"
                                  onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm">{{ __('common.common.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-muted">{{ __('inventory.empty') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
