<html @pbEditorClass('dark') lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="{{ $meta_keywords ?? '' }}" />
    <meta name="description" content="{{ $meta_description ?? '' }}" />
    <meta name="author" content="{{ $url ?? config('app.url') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>
        {{ $meta_title ?? ($title ?? '') . ' | ' . config('app.name') }}
    </title>

    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" />


    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Roboto', 'sans-serif'],
                        serif: ['Roboto', 'serif'],
                        body: ['Roboto', 'sans-serif'],
                    },
                    colors: {
                        accent: {
                            DEFAULT: '#3b82f6',
                            hover: '#2563eb',
                        },
                        'bg-dark': '#0f172a',
                        'bg-darker': '#0a0f1d',
                        'bg-card': '#1e293b',
                        'text-body': '#94a3b8',
                        'border-dark': '#1e293b',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply bg-bg-dark text-white;
            }
        }
    </style>

    @stack('content_for_head')
</head>

<body class="simple-layout bg-slate-50">

    @sections('announcement')
    @sections('header')

    <main class="min-h-screen">
        @yield('content')
    </main>

    @sections('footer')
</body>

</html>
