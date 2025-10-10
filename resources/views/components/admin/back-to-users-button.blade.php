@props(['type', 'back_url' => null])

@php
    $label = $type === \App\Enums\UserTypeEnum::MANAGEMENT->value
        ? 'usuarios de gestión'
        : 'usuarios móviles';
@endphp

<div class="mt-8">
    <a href="{!! $back_url ?? route('admin.users.index', ['type' => $type]) !!}"
       class="inline-flex items-center px-4 py-2 button-extra rounded-md transition shadow">
        ← Volver a {{ $label }}
    </a>
</div>
