@props([
    'id' => null,
    'placeholder' => 'Select options...',
    'options' => [],
    'selected' => [],
    'class' => '',
])

<div class="premium-multiselect-wrapper {{ $class }}" wire:ignore x-data="{
    value: @if ($attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else null @endif,
    selected: {{ json_encode($selected) }},
    instance: null,
    init() {
        this.instance = new TomSelect(this.$refs.select, {
            plugins: ['remove_button'],
            create: false,
            dropdownParent: 'body',
            maxOptions: 20,
            onInitialize: function() {
                const self = this;
                self.dropdown_content.addEventListener('scroll', function() {
                    if (this.scrollTop + this.clientHeight >= this.scrollHeight - 10) {
                        self.settings.maxOptions += 20;
                        self.refreshOptions(false);
                    }
                });
            },
            onItemAdd: () => {
                this.instance.setTextboxValue('');
                this.instance.refreshOptions();
            },
            onChange: (val) => {
                this.value = val;
                this.$refs.select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        if (this.value !== null) {
            this.$watch('value', (val) => {
                if (JSON.stringify(this.instance.getValue()) !== JSON.stringify(val)) {
                    this.instance.setValue(val, true);
                }
            });
        } else if (this.selected && this.selected.length > 0) {
            this.instance.setValue(this.selected, true);
        }
    }
}">
    <select x-ref="select" id="{{ $id }}" multiple
        {{ $attributes->whereDoesntStartWith('wire:model')->merge(['placeholder' => $placeholder, 'autocomplete' => 'off']) }}>
        @foreach ($options as $val => $label)
            @php
                $isSelected = is_array($selected) ? in_array($val, $selected) : $selected == $val;
            @endphp
            <option value="{{ $val }}" {{ $isSelected ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
        {{ $slot }}
    </select>
</div>
