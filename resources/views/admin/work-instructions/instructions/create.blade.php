<x-app-layout>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">
            {{ __('work_instructions.new_instruction') }}
            <span class="text-muted fw-normal fs-5 ms-2">{{ $category->name }}</span>
        </h1>
        <a href="{{ route('work-instructions.categories.edit', $category) }}" class="btn btn-outline-secondary btn-sm">
            {{ __('common.common.back') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('work-instructions.instructions.store', $category) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <label for="title" class="col-sm-3 col-form-label">{{ __('work_instructions.title') }}</label>
                    <div class="col-sm-9">
                        <input type="text" id="title" name="title" value="{{ old('title') }}"
                               class="form-control @error('title') is-invalid @enderror" required autofocus>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="pdf" class="col-sm-3 col-form-label">{{ __('work_instructions.pdf') }}</label>
                    <div class="col-sm-9">
                        <input type="file" id="pdf" name="pdf" accept="application/pdf"
                               class="form-control @error('pdf') is-invalid @enderror" required>
                        @error('pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text text-muted">Max. 20 MB, nur PDF.</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="sort_order" class="col-sm-3 col-form-label">{{ __('work_instructions.sort_order') }}</label>
                    <div class="col-sm-3">
                        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-9 offset-sm-3">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">{{ __('work_instructions.is_active') }}</label>
                        </div>
                    </div>
                </div>

                <x-primary-button>{{ __('common.common.upload') }}</x-primary-button>
                <a href="{{ route('work-instructions.categories.edit', $category) }}" class="btn btn-danger ms-2">
                    {{ __('common.common.cancel') }}
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
