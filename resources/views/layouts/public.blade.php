<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('memo.brand'))</title>
<link rel="icon" href="{{ asset('assets/memo-mark.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;900&family=Tajawal:wght@400;500;700&family=JetBrains+Mono:wght@400;500&family=Chakra+Petch:wght@500;600;700&family=Inter+Tight:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/memo.css') }}">
@include('partials.brand')
@stack('head')
</head>
<body @yield('body_attr')>
@yield('content')
@stack('scripts')
</body>
</html>
