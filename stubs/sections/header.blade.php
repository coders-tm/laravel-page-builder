@schema([
    'name' => 'Header',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Default Header Title'],
        ['id' => 'logo_image', 'type' => 'image_picker', 'label' => 'Logo Image', 'default' => '/statics/logo-dark.svg'],
        ['id' => 'logo_text', 'type' => 'text', 'label' => 'Logo Text', 'default' => 'PageBuilder'],
        ['id' => 'logo_badge', 'type' => 'text', 'label' => 'Logo Badge', 'default' => 'v1.0'],
        ['id' => 'sticky', 'type' => 'checkbox', 'label' => 'Sticky Header', 'default' => true],
        ['id' => 'github_url', 'type' => 'url', 'label' => 'GitHub URL', 'default' => 'https://github.com/coderstm/laravel-page-builder'],
        ['id' => 'cta_label', 'type' => 'text', 'label' => 'CTA Label', 'default' => 'Get Started'],
        ['id' => 'cta_url', 'type' => 'url', 'label' => 'CTA URL', 'default' => '#pricing'],
    ],
    'blocks' => [
        [
            'type' => 'nav-link',
            'name' => 'Navigation Link',
            'settings' => [
                ['id' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Link'],
                ['id' => 'url', 'type' => 'url', 'label' => 'URL', 'default' => '#'],
                ['id' => 'target_blank', 'type' => 'checkbox', 'label' => 'Open in new tab', 'default' => false],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'My Site Header',
            'settings' => [
                'title' => 'My Site Header',
                'logo_image' => '/statics/logo-dark.svg',
                'logo_text' => 'PageBuilder',
                'logo_badge' => 'v1.0',
                'sticky' => true,
                'github_url' => 'https://github.com/coderstm/laravel-page-builder',
                'cta_label' => 'Get Started',
                'cta_url' => '#pricing',
            ],
            'blocks' => [
                ['type' => 'nav-link', 'settings' => ['label' => 'Features', 'url' => '#features']],
                ['type' => 'nav-link', 'settings' => ['label' => 'How It Works', 'url' => '#how-it-works']],
                ['type' => 'nav-link', 'settings' => ['label' => 'Stats', 'url' => '#stats']],
                ['type' => 'nav-link', 'settings' => ['label' => 'Pricing', 'url' => '#pricing']],
                ['type' => 'nav-link', 'settings' => ['label' => 'FAQ', 'url' => '#faq']],
            ],
        ],
    ],
])

@php
    $stickyClass = $section->settings->sticky ? 'sticky top-0 z-50' : '';
    $brandTitle = $section->settings->title && $section->settings->title !== 'Default Header Title' ? $section->settings->title : $section->settings->logo_text;
@endphp

<header {!! $section->editorAttributes() !!}
    class="{{ $stickyClass }} w-full border-b border-gray-200/80 dark:border-gray-800/80 bg-white/90 dark:bg-gray-950/90 backdrop-blur-md transition-all">
    <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        {{-- Logo Brand --}}
        <div class="flex items-center gap-3">
            <a href="/" class="group flex items-center gap-2 text-xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                @if ($section->settings->logo_image)
                    <img src="{{ asset($section->settings->logo_image) }}" alt="{{ $brandTitle }}" class="h-8 w-auto group-hover:scale-105 transition-transform" />
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-red-600 to-indigo-600 text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </span>
                    <span>{{ $brandTitle }}</span>
                @endif
            </a>
            @if ($section->settings->logo_badge)
                <span class="hidden sm:inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-0.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                    {{ $section->settings->logo_badge }}
                </span>
            @endif
        </div>

        {{-- Navigation Links --}}
        @if ($section->blocks->isNotEmpty())
            <nav class="hidden md:flex items-center gap-1 lg:gap-2 text-sm font-medium">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'nav-link')
                        <a {!! $block->editorAttributes() !!}
                            href="{{ $block->settings->url }}"
                            @if ($block->settings->target_blank) target="_blank" rel="noopener noreferrer" @endif
                            class="px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-colors">
                            {{ $block->settings->label }}
                        </a>
                    @endif
                @endforeach
            </nav>
        @endif

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            @if ($section->settings->github_url)
                <a href="{{ $section->settings->github_url }}" target="_blank" rel="noopener noreferrer"
                    aria-label="GitHub Repository"
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                </a>
            @endif
            @if ($section->settings->cta_label)
                <a href="{{ $section->settings->cta_url }}"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 hover:from-red-500 hover:to-indigo-500 transition-all hover:scale-[1.02]">
                    {{ $section->settings->cta_label }}
                </a>
            @endif
        </div>
    </div>
</header>
