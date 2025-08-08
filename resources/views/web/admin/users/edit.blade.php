@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Edit User</h1>

    {{-- ALERTS --}}
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

    <x-form.form-wrapper action="{{ route('admin.users.update', $user->id) }}" method="PUT" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $user->id }}">

        <x-form.input label="First Name" name="name" required value="{{ old('name', $user->name) }}" />
        <x-form.input label="Last Name" name="surname" required value="{{ old('surname', $user->surname) }}" />
        <x-form.input label="DNI" name="dni" required value="{{ old('dni', $user->dni) }}" />
        <x-form.input label="Email" name="email" required type="email" value="{{ old('email', $user->email) }}" />
        <x-form.input label="Phone" name="phone" required value="{{ old('phone', $user->phone) }}" />
        <x-form.input label="Username" name="username" required value="{{ old('username', $user->username) }}" />

        <!-- Role -->
        <div class="mb-4">
            <label for="role_id" class="block font-medium mb-1">Rol <span class="text-red-600">*</span></label>
            <select name="role_id" id="role_id" required {{ request('role') ? 'disabled' : '' }} class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @foreach($assignableRoles as $role)
                    <option value="{{ $role->id }}"
                            data-role-name="{{ $role->role_name }}"
                        {{ old('role_id', $defaultRole ?? ($user->role_id ?? '')) == $role->id ? 'selected' : '' }}>
                        {{ \App\Enums\RoleEnum::from($role->role_name)->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Photo -->
        <div class="gap-4">
            <x-form.file label="Photo" name="photo" accept="image/*" />
            @if ($user->photo)
                <div class="mb-4">
                    <img src="{{ asset('storage/' . $user->photo) }}"
                         @click="$dispatch('open-image', '{{ asset('storage/' . $user->photo) }}')"
                         class="h-20 w-20 object-contain rounded cursor-pointer transition hover:brightness-110"
                         title="Ver foto actual">
                </div>
            @endif
        </div>
        {{-- Fields that are only displayed when creating a user of type "user" --}}
        <!-- Company -->
        <x-form.select
            label="Company"
            name="company_id"
            class="user-only"
            :options="$companies->pluck('name', 'id')->prepend('-- Select a company --', '')->toArray()"
            :selected="old('company_id', $user->company_id)"
        />

        <!-- Work schedule -->
        <x-form.textarea
            label="Work Schedule"
            name="work_schedule"
            class="user-only"
        >{{ old('work_schedule', $user->work_schedule) }}</x-form.textarea>

        <!-- Contract Type -->
        <x-form.select
            label="Contract Type"
            name="contract_type"
            class="user-only"
            :options="[
                '' => '-- Select a type --',
                'Temporal' => 'Temporal',
                'Indefinido' => 'Indefinite',
            ]"
            :selected="old('contract_type', $user->contract_type)"
        />

        <!-- Contract Start Date -->
        <x-form.input
            label="Contract Start Date"
            name="contract_start_date"
            type="date"
            class="user-only"
            value="{{ old('contract_start_date', optional($user?->contract_start_date)->format('Y-m-d')) }}"
        />

        <!-- Checkbox: Can receive notifications -->
        <x-form.checkbox
            name="can_receive_notifications"
            label="Can receive notifications"
            class="user-only"
            :checked="old('can_receive_notifications', $user->can_receive_notifications)"
        />

        <!-- Notification type -->
        <x-form.select
            label="Notification Type"
            name="notification_type"
            class="user-only"
            :options="[
                'none' => 'None',
                'visual' => 'Visual',
                'visual_audio' => 'Visual and Audio',
            ]"
            :selected="old('notification_type', $user->notification_type)"
        />

        {{-- Buttons --}}
        <x-form.button-group submit-text="Update" />
    </x-form.form-wrapper>

    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection
