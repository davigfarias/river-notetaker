{{-- Indicador "role para baixo": colocar como primeiro filho de um container que rola e marcá-lo com data-scroll-hint-host. --}}
<div class="sticky bottom-0 z-10 m-0! h-0 overflow-visible" aria-hidden="true">
    <div data-modal-scroll-hint hidden class="modal-scroll-hint">
        <span>{{ __('Role para baixo') }}</span>
        <flux:icon.chevron-down variant="mini" class="size-4 animate-bounce motion-reduce:animate-none" />
    </div>
</div>
