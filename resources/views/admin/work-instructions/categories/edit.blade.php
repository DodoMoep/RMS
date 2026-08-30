<x-app-layout>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">{{ __('work_instructions.edit_category') }}: {{ $category->name }}</h1>
        <a href="{{ route('work-instructions.categories.index') }}" class="btn btn-outline-secondary btn-sm">
            {{ __('common.common.back') }}
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Kategorie bearbeiten --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('work-instructions.categories.update', $category) }}">
                @csrf @method('PUT')

                <div class="row mb-3">
                    <label for="name" class="col-sm-3 col-form-label">{{ __('work_instructions.name') }}</label>
                    <div class="col-sm-9">
                        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">{{ __('work_instructions.slug') }}</label>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-text text-muted small">/anweisungen/</span>
                            <input type="text" class="form-control text-muted" value="{{ $category->slug }}" disabled>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="sort_order" class="col-sm-3 col-form-label">{{ __('work_instructions.sort_order') }}</label>
                    <div class="col-sm-3">
                        <input type="number" id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $category->sort_order) }}" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-9 offset-sm-3">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ $category->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">{{ __('work_instructions.is_active') }}</label>
                        </div>
                    </div>
                </div>

                <x-primary-button>{{ __('common.common.save') }}</x-primary-button>
            </form>
        </div>
    </div>

    {{-- Anweisungen dieser Tätigkeit --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="h5 mb-0">{{ __('work_instructions.instructions') }}</h2>
        <a href="{{ route('work-instructions.instructions.create', $category) }}" class="btn btn-outline-primary btn-sm">
            {{ __('work_instructions.add_instruction') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('work_instructions.title') }}</th>
                        <th class="text-center" style="width:90px;">{{ __('work_instructions.sort_order') }}</th>
                        <th class="text-center" style="width:70px;">{{ __('work_instructions.is_active') }}</th>
                        <th style="width:130px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($category->instructions as $instruction)
                        <tr>
                            <td>{{ $instruction->title }}</td>
                            <td class="text-center text-muted">{{ $instruction->sort_order }}</td>
                            <td class="text-center">
                                @if($instruction->is_active)
                                    <span class="badge bg-success">{{ __('common.common.yes') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('common.common.no') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('work-instructions.instructions.edit', [$category, $instruction]) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    {{ __('common.common.edit') }}
                                </a>
                                <form action="{{ route('work-instructions.instructions.destroy', [$category, $instruction]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('common.common.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-3">{{ __('work_instructions.no_instructions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
