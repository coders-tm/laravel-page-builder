@schema([
    'name' => 'Footer',
    'settings' => [['id' => 'logo_image', 'type' => 'image_picker', 'label' => 'Logo Image', 'default' => '/statics/logo-dark.svg'], ['id' => 'brand_name', 'type' => 'text', 'label' => 'Brand Name', 'default' => 'Laravel Page Builder'], ['id' => 'brand_tagline', 'type' => 'textarea', 'label' => 'Tagline', 'default' => 'A multi-theme, JSON-driven page composition system for Laravel developers.'], ['id' => 'copyright_text', 'type' => 'text', 'label' => 'Copyright Text', 'default' => ''], ['id' => 'github_url', 'type' => 'url', 'label' => 'GitHub URL', 'default' => 'https://github.com/coders-tm/laravel-page-builder'], ['id' => 'twitter_url', 'type' => 'url', 'label' => 'X (Twitter) URL', 'default' => 'https://x.com'], ['id' => 'discord_url', 'type' => 'url', 'label' => 'Discord URL', 'default' => 'https://discord.gg']],
    'blocks' => [
        [
            'type' => 'footer-column',
            'name' => 'Column',
            'limit' => 4,
            'settings' => [['id' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Column']],
            'blocks' => [
                [
                    'type' => 'footer-link',
                    'name' => 'Link',
                    'settings' => [['id' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Link'], ['id' => 'url', 'type' => 'url', 'label' => 'URL', 'default' => '#'], ['id' => 'target_blank', 'type' => 'checkbox', 'label' => 'Open in new tab', 'default' => false]],
                ],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Default Footer',
            'settings' => [
                'logo_image' => '/statics/logo-dark.svg',
                'brand_name' => 'Laravel Page Builder',
                'brand_tagline' => 'A multi-theme, schema-driven page composition engine built specifically for Laravel.',
                'github_url' => 'https://github.com/coders-tm/laravel-page-builder',
            ],
            'blocks' => [
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'Quick Links'],
                    'blocks' => [['type' => 'footer-link', 'settings' => ['label' => 'Features', 'url' => '#features']], ['type' => 'footer-link', 'settings' => ['label' => 'How It Works', 'url' => '#how-it-works']], ['type' => 'footer-link', 'settings' => ['label' => 'Stats & Speed', 'url' => '#stats']], ['type' => 'footer-link', 'settings' => ['label' => 'Pricing Plans', 'url' => '#pricing']]],
                ],
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'Architecture'],
                    'blocks' => [['type' => 'footer-link', 'settings' => ['label' => 'Inline @schema Directive', 'url' => '#features']], ['type' => 'footer-link', 'settings' => ['label' => 'Multi-Theme Engine', 'url' => '#features']], ['type' => 'footer-link', 'settings' => ['label' => 'JSON Page Storage', 'url' => '#features']], ['type' => 'footer-link', 'settings' => ['label' => '5-Layer Isolation', 'url' => '#features']]],
                ],
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'Resources'],
                    'blocks' => [['type' => 'footer-link', 'settings' => ['label' => 'GitHub Repository', 'url' => 'https://github.com/coders-tm/laravel-page-builder', 'target_blank' => true]], ['type' => 'footer-link', 'settings' => ['label' => 'Documentation', 'url' => '#', 'target_blank' => true]], ['type' => 'footer-link', 'settings' => ['label' => 'Release Notes', 'url' => '#']], ['type' => 'footer-link', 'settings' => ['label' => 'MIT License', 'url' => '#']]],
                ],
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'FAQ & Support'],
                    'blocks' => [['type' => 'footer-link', 'settings' => ['label' => 'Frequently Asked Questions', 'url' => '#faq']], ['type' => 'footer-link', 'settings' => ['label' => 'Community Discussions', 'url' => 'https://github.com/coders-tm/laravel-page-builder/discussions', 'target_blank' => true]], ['type' => 'footer-link', 'settings' => ['label' => 'Report an Issue', 'url' => 'https://github.com/coders-tm/laravel-page-builder/issues', 'target_blank' => true]]],
                ],
            ],
        ],
    ],
])

@php
    $copyright =
        $section->settings->copyright_text ?:
        '&copy; ' . date('Y') . ' ' . $section->settings->brand_name . '. All rights reserved.';
@endphp

<footer {!! $section->editorAttributes() !!} class="bg-slate-950 text-slate-400 border-t border-slate-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 mb-12">

            {{-- Brand Left Column --}}
            <div class="lg:col-span-4 space-y-4">
                <a href="/" class="flex items-center gap-2 text-xl font-extrabold text-white">
                    @if ($section->settings->logo_image)
                        <img src="{{ asset($section->settings->logo_image) }}" alt="{{ $section->settings->brand_name }}"
                            class="h-8 w-auto" />
                    @else
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-red-600 to-indigo-600 text-white shadow-md">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </span>
                        <span>{{ $section->settings->brand_name }}</span>
                    @endif
                </a>
                <p class="text-sm text-slate-400 max-w-sm leading-relaxed">
                    {{ $section->settings->brand_tagline }}
                </p>

                <div class="flex items-center gap-4 pt-2">
                    @if ($section->settings->github_url)
                        <a href="{{ $section->settings->github_url }}" target="_blank" rel="noopener"
                            aria-label="GitHub" class="text-slate-400 hover:text-white transition-colors">
                            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                <path
                                    d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Link Columns --}}
            @if ($section->blocks->isNotEmpty())
                <div class="lg:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-8">
                    @foreach ($section->blocks as $block)
                        @if ($block->type === 'footer-column')
                            <div {!! $block->editorAttributes() !!}>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">
                                    {{ $block->settings->title }}
                                </h4>
                                <ul class="space-y-2.5">
                                    @foreach ($block->blocks as $child)
                                        @if ($child->type === 'footer-link')
                                            <li {!! $child->editorAttributes() !!}>
                                                <a href="{{ $child->settings->url }}"
                                                    @if ($child->settings->target_blank) target="_blank" rel="noopener" @endif
                                                    class="text-sm text-slate-400 hover:text-white transition-colors">
                                                    {{ $child->settings->label }}
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

        </div>

        {{-- Bottom Copyright Row --}}
        <div
            class="border-t border-slate-800/80 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
            <p>{!! $copyright !!}</p>
            <div class="flex items-center gap-6">
                <span>Made with Laravel & Blade</span>
            </div>
        </div>
    </div>
</footer>
