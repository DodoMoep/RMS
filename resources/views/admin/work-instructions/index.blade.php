<x-app-layout>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">{{ __('work_instructions.categories') }}</h1>
        <a href="{{ route('work-instructions.categories.create') }}" class="btn btn-outline-primary btn-sm">
            {{ __('work_instructions.new_category') }}
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('work_instructions.name') }}</th>
                        <th>{{ __('work_instructions.public_url') }}</th>
                        <th class="text-center" style="width:110px;">{{ __('work_instructions.instructions') }}</th>
                        <th class="text-center" style="width:90px;">{{ __('work_instructions.sort_order') }}</th>
                        <th class="text-center" style="width:70px;">{{ __('work_instructions.is_active') }}</th>
                        <th style="width:160px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td class="small">
                                <a href="{{ route('work-instructions.show', $category) }}" target="_blank"
                                   class="text-muted text-decoration-none">
                                    /anweisungen/{{ $category->slug }}
                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $category->instructions_count }}</span>
                            </td>
                            <td class="text-center text-muted">{{ $category->sort_order }}</td>
                            <td class="text-center">
                                @if($category->is_active)
                                    <span class="badge bg-success">{{ __('common.common.yes') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('common.common.no') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('work-instructions.categories.edit', $category) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    {{ __('common.common.edit') }}
                                </a>
                                <form action="{{ route('work-instructions.categories.destroy', $category) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('common.common.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">{{ __('work_instructions.no_instructions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
