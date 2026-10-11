@props([
    'id' => null,
    'placeholder' => 'Select an option...',
    'options' => [],
    'selected' => null,
    'class' => '',
    'clearable' => false,
])

<div class="premium-select-wrapper {{ $class }}" wire:ignore x-data="{
    value: @if ($attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else null @endif,
    selected: {{ json_encode($selected) }},
    instance: null,
    init() {
        this.instance = new TomSelect(this.$refs.select, {
            create: false,
            plugins: {{ $clearable ? "['clear_button']" : '[]' }},
            allowEmptyOption: true,
            placeholder: '{{ $placeholder }}',
            dropdownParent: 'body',
            maxOptions: 50,
            onInitialize: function() {
                const self = this;
                self.dropdown_content.addEventListener('scroll', function() {
                    if (this.scrollTop + this.clientHeight >= this.scrollHeight - 10) {
                        self.settings.maxOptions += 20;
                        self.refreshOptions(false);
                    }
                });
            },
            onFocus: function() {
                if (!this.getValue()) {
                    this.setTextboxValue('');
                }
            },
            onDropdownOpen: function() {
                if (!this.getValue()) {
                    this.setTextboxValue('');
                }
            },
            onItemAdd: () => this.instance.setTextboxValue(''),
            onChange: (val) => {
                this.value = val;
                this.$refs.select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        if (this.value !== null) {
            this.$watch('value', (val) => {
                if (this.instance.getValue() !== val) {
                    this.instance.setValue(val, true);
                }
            });
        }

        // Always set initial value if present, but priority to Livewire
        const initialValue = this.value || this.selected;
        if (initialValue !== null && initialValue !== undefined) {
            this.instance.setValue(initialValue, true);
        }
    }
}">
    <select x-ref="select" id="{{ $id }}"
        {{ $attributes->whereDoesntStartWith('wire:model')->merge(['placeholder' => $placeholder, 'autocomplete' => 'off']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $val => $label)
            <option value="{{ $val }}" {{ $selected == $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
        {{ $slot }}
    </select>
</div>
