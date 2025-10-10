<button {{ $attributes->merge(['type' => 'submit',
 'class' => 'px-4 py-2 button-success border border-transparent rounded-md font-semibold ms-3']) }}>
    {{ $slot }}
</button>
