<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error' }} — {{ config('app.name') }}</title>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; }
        body {
            height: 100%;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #fafafa;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark body { background: #0a0a0a; color: #f5f5f5; }
        .container { text-align: center; padding: 2rem; max-width: 480px; }
        .code {
            font-size: 6rem;
            font-weight: 700;
            line-height: 1;
            color: #d1d5db;
            letter-spacing: -0.05em;
        }
        .dark .code { color: #374151; }
        h1 { font-size: 1.5rem; font-weight: 600; margin: 1rem 0 0.5rem; }
        p { color: #6b7280; font-size: 0.95rem; line-height: 1.6; }
        .dark p { color: #9ca3af; }
        .actions { margin-top: 2rem; display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        a {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        a:hover { opacity: 0.8; }
        .btn-primary { background: #111; color: #fff; }
        .dark .btn-primary { background: #f5f5f5; color: #111; }
        .btn-ghost { border: 1px solid #e5e7eb; color: #374151; }
        .dark .btn-ghost { border-color: #374151; color: #d1d5db; }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
