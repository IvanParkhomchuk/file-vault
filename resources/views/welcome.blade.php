<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container py-5">
        <h1 class="mb-3">File Lifecycle Manager</h1>
        <p class="text-secondary mb-0">Laravel 13 project setup is complete.</p>
    </main>
</body>
</html>
