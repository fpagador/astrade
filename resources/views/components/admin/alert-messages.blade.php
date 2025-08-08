{{-- AlertMessages component --}}
@if(session('success'))
    <div class="w-full bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6 text-base font-semibold">
        <strong>{{ session('success') }}</strong>
    </div>
@endif

@if(session('error'))
    <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
        <strong>{{ session('error') }}</strong>
    </div>
@endif

@if ($errors->has('general'))
    <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
        <strong>{{ $errors->first('general') }}</strong>
    </div>
@endif
