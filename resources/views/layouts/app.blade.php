<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'eStudent Institutes')</title>
<meta name="description" content="@yield('description', 'Educational institutes directory by eStudent.pk')">
@hasSection('canonical')
<link rel="canonical" href="@yield('canonical')">
@endif
<meta property="og:title" content="@yield('title', 'eStudent Institutes')">
<meta property="og:description" content="@yield('description', 'Educational institutes directory by eStudent.pk')">
<meta property="og:type" content="website">
@hasSection('canonical')
<meta property="og:url" content="@yield('canonical')">
@endif
@stack('head')
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header>
<nav aria-label="Primary">
<a href="{{ url('/institutes/') }}">Institutes</a>
</nav>
</header>
<main>@yield('content')</main>
</body>
</html>
