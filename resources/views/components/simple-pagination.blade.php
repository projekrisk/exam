@props(['paginator'])

@if($paginator->total() > 0)
<div class="p-4 border-t border-notion-border bg-notion-sidebar/30 flex items-center justify-between gap-4 text-sm">
  <p class="text-notion-gray font-medium tnum">
    {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ number_format($paginator->total()) }} data
  </p>

  @if($paginator->hasPages())
  <div class="flex items-center gap-1.5">
    @if($paginator->onFirstPage())
      <span class="p-2 border border-notion-border bg-white rounded-md text-notion-gray/40 cursor-not-allowed">
        <i data-lucide="chevron-left" class="w-4 h-4"></i>
      </span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" class="p-2 border border-notion-border bg-white rounded-md text-notion-text hover:bg-notion-hover transition-colors shadow-sm" title="Sebelumnya">
        <i data-lucide="chevron-left" class="w-4 h-4"></i>
      </a>
    @endif

    @if($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" class="p-2 border border-notion-border bg-white rounded-md text-notion-text hover:bg-notion-hover transition-colors shadow-sm" title="Berikutnya">
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
      </a>
    @else
      <span class="p-2 border border-notion-border bg-white rounded-md text-notion-gray/40 cursor-not-allowed">
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
      </span>
    @endif
  </div>
  @endif
</div>
@endif
