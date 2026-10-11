<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Welcome To Team Tasker' }}</title>

    @php
        $appSettings = \App\Models\Setting::whereIn('key', ['app_name', 'app_logo'])->pluck('value', 'key');
        $appLogo = $appSettings['app_logo'] ?? null;
    @endphp

    @if ($appLogo)
        <link rel="icon" href="{{ asset('storage/' . $appLogo) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">

    <style>
        body {
            font-family: 'Nunito';
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a href="{{ route('index') }}"><span class="navbar-brand mb-0 h1">Todo</span></a>
            <a href="{{ route('create') }}"><span class="btn btn-primary">Create Todo</span></a>
            <a href="{{ route('logout') }}"><span class="btn btn-primary">Logout</span></a>
        </div>
    </nav>

    <div class="container">
        @if (session()->has('success'))
            <x-alert.alert type="success" message="{{ session()->get('success') }}" />
        @endif
        {{ $slot }}
    </div>

</body>

</html>
