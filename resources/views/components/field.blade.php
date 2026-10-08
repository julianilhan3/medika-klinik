@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'model' => null])
@php
    $isPass = $type === 'password';
    $cls = 'input '.($errors->has($name) ? 'input-error' : '').($isPass ? ' pr-10' : '');
@endphp
<div {{ $attributes->only('class') }} @if ($isPass) x-data="{ show: false }" @endif>
    <label for="f-{{ $name }}" class="label">{{ $label }}</label>
    <div class="relative">
        @if ($isPass)
            <input id="f-{{ $name }}" name="{{ $name }}" :type="show ? 'text' : 'password'" {{ $attributes->except('class')->merge(['class' => $cls]) }}>
            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600" aria-label="Tampilkan password"><x-icon name="lucide:eye" /></button>
        @elseif ($model)
            <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" x-model="{{ $model }}" {{ $attributes->except('class')->merge(['class' => $cls]) }}>
        @else
            <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->except('class')->merge(['class' => $cls]) }}>
        @endif
    </div>
    @error($name)<p class="err"><x-icon name="lucide:alert-circle" class="text-sm" />{{ $message }}</p>@enderror
    @if ($hint && ! $errors->has($name))<p class="hint">{{ $hint }}</p>@endif
</div>
