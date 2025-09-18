@extends('layouts.app')

@section('title', 'Ver Usuario')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white p-10 rounded shadow">
            <div class="flex justify-end">
                <a href="{{ route('admin.users.edit', [
                        'user' => $user->id,
                        'role' => $user->role->role_name ?? null,
                        'type' => request('type', \App\Enums\UserTypeEnum::MANAGEMENT->value)
                    ]) }}"
                   class="inline-flex items-center gap-x-2 mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800"
                   title="Editar">
                    <span>Editar Usuario</span>
                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-100 hover:text-indigo-100 transition"></i>
                </a>
            </div>

            {{-- Foto y nombre --}}
            <div class="text-center mb-12">
                @if ($user->photo)
                    <img src="{{ asset('storage/' . $user->photo) }}"
                         alt="Foto de perfil"
                         class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-indigo-200 shadow">
                @else
                    <div class="w-32 h-32 rounded-full mx-auto bg-gray-200 flex items-center justify-center text-gray-500 text-2xl shadow">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif

                <h2 class="text-3xl font-bold mt-6">{{ $user->name }} {{ $user->surname }}</h2>
                <p class="text-base text-gray-500">{{ $user->email }}</p>
            </div>

            {{-- Datos personales --}}
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-indigo-800 mb-8 border-b border-indigo-200 pb-2">
                    📌 Datos personales
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @include('web.admin.users.partials.readonly-field', ['label' => 'DNI', 'value' => $user->dni])
                    @include('web.admin.users.partials.readonly-field', ['label' => 'Teléfono', 'value' => $user->phone])
                    @include('web.admin.users.partials.readonly-field', ['label' => 'Username', 'value' => $user->username])
                    @include('web.admin.users.partials.readonly-field', ['label' => 'Rol', 'value' => $user->role ? \App\Enums\RoleEnum::from($user->role->role_name)->label() : '—'])
                </div>
            </div>

            {{-- Mostrar solo si es un usuario "user" --}}
            @if(optional($user->role)->role_name === 'user')

                {{-- Datos laborales --}}
                <div class="mb-16">
                    <h3 class="text-2xl font-bold text-indigo-800 mb-8 border-b border-indigo-200 pb-2">
                        💼 Datos laborales
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Horario de trabajo', 'value' => $user->work_schedule ?? '—'])
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Tipo de contrato', 'value' => $user->contract_type ? \App\Enums\ContractType::label(\App\Enums\ContractType::from($user->contract_type)) : '—'])
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Inicio de contrato', 'value' => $user->contract_start_date ?? '—'])
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Tipo de notificación', 'value' => $user->notification_type ? \App\Enums\NotificationType::label(\App\Enums\NotificationType::from($user->notification_type)) : '—'])
                    </div>
                </div>

                {{-- Company --}}
                <div class="mb-4">
                    <h3 class="text-2xl font-bold text-indigo-800 mb-8 border-b border-indigo-200 pb-2">
                        🏢 Empresa
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Nombre', 'value' => optional($user->company)->name ?? '—'])
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Dirección', 'value' => optional($user->company)->address ?? '—'])
                        @include('web.admin.users.partials.readonly-field', ['label' => 'Descripción', 'value' => optional($user->company)->description ?? '—'])
                    </div>

                    {{-- Phones --}}
                    @if(optional($user->company)->phones && $user->company->phones->count())
                        <h4 class="text-lg font-semibold text-indigo-700 mt-10 mb-4">📞 Teléfonos de la empresa</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($user->company->phones as $phone)
                                @include('web.admin.users.partials.readonly-field', [
                                    'label' => $phone->name ?? 'Otro',
                                    'value' => $phone->phone_number
                                ])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- BACK BUTTON --}}
        @php
            $type = request('type', '\App\Enums\UserTypeEnum::management->value');
        @endphp
        <x-admin.back-to-users-button :type="$type" :back_url="$backUrl" />
    </div>
@endsection
