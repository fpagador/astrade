{{-- AlertMessages component --}}
<div
    id="successMessageContainer"
    class="hidden w-full bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6 text-base font-semibold">
</div>

@if(session('success'))
    <div class="w-full bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6 text-base font-semibold">
        <strong>{{ session('success') }}</strong>
    </div>
@endif

@if ($errors->any())
    <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

