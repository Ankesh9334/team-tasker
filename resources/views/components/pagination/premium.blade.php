@php
    $isLivewire = isset($this) && method_exists($this, 'gotoPage');
    $activeFilters = 0;
    
    if ($isLivewire) {
        $skipProps = ['page', 'perPage', 'sortField', 'sortDirection', 'selectedTasks', 'selectedProjects', 'bulkStatus', 'bulkAssignee', 'bulkPriority', 'paginators'];
        $publicProps = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($publicProps as $prop) {
            $name = $prop->getName();
            if (!in_array($name, $skipProps) && $this->$name !== '' && $this->$name !== null && $this->$name !== []) {
                $activeFilters++;
            }
        }
    } else {
        $activeFilters = collect(request()->except(['page', 'per_page', 'sortField', 'sortDirection', 'ids', '_token', '_method']))
            ->filter(fn($val) => $val !== null && $val !== '')
            ->count();
    }
@endphp

    <style>
        .pagination-input::-webkit-outer-spin-button,
        .pagination-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .pagination-input {
            -moz-appearance: textfield;
        }
    </style>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <button type="button" class="data-grid-filter-btn position-relative {{ $activeFilters > 0 ? 'active' : '' }}" onclick="document.dispatchEvent(new Event('openFilterModal'))">
            <i class="fas fa-filter"></i>
            <span class="fw-medium">Filter</span>
            @if ($activeFilters > 0)
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle" style="width: 10px; height: 10px;">
                    <span class="visually-hidden">Filters active</span>
                </span>
            @endif
        </button>
        
        <div class="d-flex align-items-center gap-2">
            @if($isLivewire)
                <select wire:model.live="perPage" class="form-select fw-medium cursor-pointer py-1 px-2" style="width: auto; border-radius: 6px; background: var(--bg-input) !important; color: var(--text-high) !important; border-color: var(--border-subtle) !important;">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            @else
                <select class="form-select fw-medium cursor-pointer py-1 px-2" style="width: auto; border-radius: 6px; background: var(--bg-input) !important; color: var(--text-high) !important; border-color: var(--border-subtle) !important;" onchange="window.location.href=this.value">
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 15]) }}" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 25]) }}" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 50]) }}" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                </select>
            @endif
            <span class="fw-medium small" style="color: var(--text-medium) !important;">Per Page</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($isLivewire)
                <input type="number" class="form-control text-center fw-medium py-1 px-2 pagination-input" value="{{ $paginator->currentPage() }}" wire:keydown.enter="gotoPage($event.target.value)" wire:blur="gotoPage($event.target.value)" style="width: 50px; border-radius: 6px; background: var(--bg-input) !important; color: var(--text-high) !important; border-color: var(--border-subtle) !important;" min="1" max="{{ $paginator->lastPage() }}">
            @else
                <input type="number" class="form-control text-center fw-medium py-1 px-2 pagination-input" value="{{ $paginator->currentPage() }}" onchange="window.location.href='{{ request()->fullUrlWithQuery(['page' => '']) }}' + this.value" style="width: 50px; border-radius: 6px; background: var(--bg-input) !important; color: var(--text-high) !important; border-color: var(--border-subtle) !important;" min="1" max="{{ $paginator->lastPage() }}">
            @endif
            <span class="fw-medium small text-nowrap" style="color: var(--text-medium) !important;">of {{ $paginator->lastPage() }}</span>
        </div>

        <div class="d-flex align-items-center gap-1">
            <!-- First Page -->
            @if ($paginator->onFirstPage())
                <span class="btn btn-link p-1 text-decoration-none disabled" style="color: var(--text-low); font-size: 1.1rem; opacity: 0.5;">
                    <i class="fas fa-angle-double-left"></i>
                </span>
            @else
                <a href="{{ $paginator->url(1) }}" class="btn btn-link p-1 text-decoration-none" style="color: var(--text-high); font-size: 1.1rem;" {!! $isLivewire ? 'wire:click.prevent="gotoPage(1)"' : '' !!}>
                    <i class="fas fa-angle-double-left"></i>
                </a>
            @endif
            
            <!-- Prev Page -->
            @if ($paginator->onFirstPage())
                <span class="btn btn-link p-1 text-decoration-none disabled" style="color: var(--text-low); font-size: 1.1rem; opacity: 0.5;">
                    <i class="fas fa-angle-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-link p-1 text-decoration-none" style="color: var(--text-high); font-size: 1.1rem;" {!! $isLivewire ? 'wire:click.prevent="previousPage"' : '' !!}>
                    <i class="fas fa-angle-left"></i>
                </a>
            @endif

            <!-- Next Page -->
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-link p-1 text-decoration-none" style="color: var(--text-high); font-size: 1.1rem;" {!! $isLivewire ? 'wire:click.prevent="nextPage"' : '' !!}>
                    <i class="fas fa-angle-right"></i>
                </a>
            @else
                <span class="btn btn-link p-1 text-decoration-none disabled" style="color: var(--text-low); font-size: 1.1rem; opacity: 0.5;">
                    <i class="fas fa-angle-right"></i>
                </span>
            @endif

            <!-- Last Page -->
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="btn btn-link p-1 text-decoration-none" style="color: var(--text-high); font-size: 1.1rem;" {!! $isLivewire ? 'wire:click.prevent="gotoPage('.$paginator->lastPage().')"' : '' !!}>
                    <i class="fas fa-angle-double-right"></i>
                </a>
            @else
                <span class="btn btn-link p-1 text-decoration-none disabled" style="color: var(--text-low); font-size: 1.1rem; opacity: 0.5;">
                    <i class="fas fa-angle-double-right"></i>
                </span>
            @endif
        </div>
    </div>
    
    <script>
        if (!window.filterModalListenerAdded) {
            document.addEventListener('openFilterModal', () => {
                const slideover = document.querySelector('.filter-slideover');
                if (slideover) slideover.classList.add('show');
            });
            window.filterModalListenerAdded = true;
        }
    </script>
