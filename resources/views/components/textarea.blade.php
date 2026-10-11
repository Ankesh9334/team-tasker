@props([
    'id' => null,
    'name' => null,
    'placeholder' => '',
    'required' => false,
    'rows' => 4,
    'texteditor' => false,
    'class' => '',
])

{{-- When texteditor=true, TinyMCE hides this textarea. Browser cannot focus hidden required fields,
     so we strip `required` from the element and handle validation via JS before form submit. --}}
<textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
    @if($required && !$texteditor) required @endif
    @if($required && $texteditor) data-required="true" @endif
    {{ $attributes->merge(['class' => 'form-control ' . ($texteditor ? 'rich-editor ' : '') . $class]) }}>{{ $slot }}</textarea>
