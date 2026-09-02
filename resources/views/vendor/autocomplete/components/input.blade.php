<input
    type="text"
    autocomplete="off"
    {{ $attributes->class('w-full pl-4 py-2 rounded border border-surface-variant bg-surface-container-lowest shadow-none leading-5 text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary disabled:opacity-60') }}
    x-bind:class="[selected ? 'pr-9' : 'pr-4']"
    x-bind:disabled="selected" />