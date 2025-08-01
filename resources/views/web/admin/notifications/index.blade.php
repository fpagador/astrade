{{--@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Notificaciones</h1>

    @if($notifications->isEmpty())
        <p class="text-gray-600">No hay notificaciones nuevas.</p>
    @else
        <ul class="space-y-3">
            @foreach($notifications as $notification)
                <li class="p-3 bg-white border border-gray-300 rounded shadow">
                    {{ $notification->message }}
                    <div class="text-sm text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                </li>
            @endforeach
        </ul>
    @endif

@endsection
