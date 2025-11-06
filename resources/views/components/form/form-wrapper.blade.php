@props([
'class' => '',
'action' => '',
'method' => 'POST',
])

<form action="{!! $action !!}" method="{{ strtolower($method) === 'get' ? 'GET' : 'POST' }}" enctype="multipart/form-data" {{ $attributes->merge(['class' => $class]) }}>
    @if (strtoupper($method) !== 'GET' && strtoupper($method) !== 'POST')
        @method($method)
    @endif
    @csrf

    {{ $slot }}
</form>
