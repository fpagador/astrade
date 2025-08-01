<x-guest-layout>
    <!--<x-auth-card>-->
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <!-- Show errors -->
    <!--<x-auth-validation-errors class="mb-4" :errors="$errors" />-->

        <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <!-- Hidden token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Hidden email (Laravel needs it for the reset system) -->
            <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

            <!-- New read-only field showing the email -->
            <div>
                <x-input-label for="email" :value="__('Email (informativo)')" />
                <x-text-input id="email" class="block mt-1 w-full bg-gray-100 text-gray-600" type="text" value="{{ $request->email }}" disabled />
            </div>

            <!-- New password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Contraseña')" />
                <x-text-input id="password" class="block mt-1 w-full"
                              type="password"
                              name="password"
                              required autocomplete="new-password" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                              type="password"
                              name="password_confirmation" required />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button>
                    {{ __('Restablecer contraseña') }}
                </x-primary-button>
            </div>
        </form>
    <!--</x-auth-card>-->
</x-guest-layout>
