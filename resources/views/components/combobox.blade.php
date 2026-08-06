@props([
    'name',
    'id' => null,
    'label' => null,
    'required' => false,
    'placeholder' => 'Search…',
    'options' => [],
    'selected' => null,
    'emptyOption' => '-- Select --',
    'disabled' => false,
])
@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

<div>
    @if($label)
        <label for="{{ $id }}_search" class="block text-sm font-semibold text-gray-900 mb-2">
            {{ $label }} @if($required)<span class="text-red-600">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <input type="text" id="{{ $id }}_search" autocomplete="off" placeholder="{{ $placeholder }}"
            {{ $disabled ? 'disabled' : '' }}
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 {{ $hasError ? 'border-red-600' : '' }}">

        <select name="{{ $name }}" id="{{ $id }}" data-combobox class="sr-only"
            {{ $required ? 'required' : '' }} {{ $disabled ? 'disabled' : '' }} {{ $attributes }}>
            <option value="">{{ $emptyOption }}</option>
            @foreach($options as $opt)
                <option value="{{ $opt['value'] }}"
                    @foreach(($opt['data'] ?? []) as $k => $v)
                        data-{{ $k }}="{{ $v }}"
                    @endforeach
                    {{ (string) $selected === (string) $opt['value'] ? 'selected' : '' }}>
                    {{ $opt['label'] }}
                </option>
            @endforeach
        </select>

        <div id="{{ $id }}_menu" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto hidden"></div>
    </div>

    @error($name)
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>
