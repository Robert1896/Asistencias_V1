@props(['dismissable' => true])

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        @if($dismissable)<button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Cerrar"></button>@endif
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
        @if($dismissable)<button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Cerrar"></button>@endif
    </div>
@endif
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-info-circle me-1"></i>{{ session('warning') }}
        @if($dismissable)<button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Cerrar"></button>@endif
    </div>
@endif
