@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-4 rounded border border-red-400 bg-red-100 p-4 text-red-700']) }}>
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
