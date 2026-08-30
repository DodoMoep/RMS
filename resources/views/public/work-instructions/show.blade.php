<x-public-layout :title="$category->name . ' – ' . config('app.name')">
    <div class="card shadow-sm">
        <div class="card-header">
            <h1 class="h4 mb-0">{{ $category->name }}</h1>
        </div>

        @if($instructions->isEmpty())
            <div class="card-body text-muted">
                {{ __('work_instructions.no_instructions') }}
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach($instructions as $instruction)
                    <li class="list-group-item d-flex align-items-center justify-content-between py-3">
                        <span>
                            <i class="fas fa-file-pdf text-danger me-2"></i>
                            {{ $instruction->title }}
                        </span>
                        <a href="{{ route('work-instructions.serve', [$category, $instruction]) }}"
                           target="_blank"
                           rel="noopener"
                           class="btn btn-outline-primary btn-sm">
                            {{ __('work_instructions.open') }}
                            <i class="fas fa-external-link-alt ms-1 small"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-public-layout>
