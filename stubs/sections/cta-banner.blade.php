@schema([
    'name' => 'CTA Banner',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Banner Badge', 'default' => 'Ready to Build?'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Start Building Dynamic Blade Pages Today'],
        ['id' => 'description', 'type' => 'textarea', 'label' => 'Description', 'default' => 'Install via Composer and publish your first schema-driven section in less than 5 minutes.'],
        ['id' => 'command_text', 'type' => 'text', 'label' => 'Composer Command', 'default' => 'composer require coderstm/laravel-page-builder'],
        ['id' => 'button_label', 'type' => 'text', 'label' => 'Primary Button', 'default' => 'Read Documentation'],
        ['id' => 'button_url', 'type' => 'url', 'label' => 'Primary URL', 'default' => 'https://github.com/coderstm/laravel-page-builder'],
        ['id' => 'secondary_label', 'type' => 'text', 'label' => 'Secondary Button', 'default' => 'View Source on GitHub'],
        ['id' => 'secondary_url', 'type' => 'url', 'label' => 'Secondary URL', 'default' => 'https://github.com/coderstm/laravel-page-builder'],
    ],
    'presets' => [
        [
            'name' => 'Install Call-to-Action Banner',
            'settings' => [
                'badge' => '⚡ Get Started in Seconds',
                'title' => 'Ready to Supercharge Your Laravel Pages?',
                'description' => 'Install the package with Composer and enjoy pure Blade section composition.',
                'command_text' => 'composer require coderstm/laravel-page-builder',
                'button_label' => 'Read Documentation',
                'button_url' => 'https://github.com/coderstm/laravel-page-builder',
                'secondary_label' => 'Star on GitHub ⭐',
                'secondary_url' => 'https://github.com/coderstm/laravel-page-builder',
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} class="relative py-20 lg:py-28 bg-slate-950 text-white overflow-hidden border-t border-slate-800">
    {{-- Background glowing circles --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[500px] w-[500px] rounded-full bg-gradient-to-tr from-red-600/20 to-indigo-600/20 blur-3xl"></div>
    </div>

    <div class="container relative mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
        <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-br from-slate-900/90 via-slate-900/80 to-indigo-950/40 p-8 sm:p-12 lg:p-16 text-center shadow-2xl backdrop-blur-xl">
            
            @if ($section->settings->badge)
                <span class="inline-flex items-center rounded-full bg-indigo-500/10 border border-indigo-500/30 px-4 py-1 text-xs font-bold text-indigo-300 mb-6">
                    {{ $section->settings->badge }}
                </span>
            @endif

            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-4 leading-tight">
                {{ $section->settings->title }}
            </h2>

            <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto mb-8 leading-relaxed">
                {{ $section->settings->description }}
            </p>

            {{-- Terminal Command Box --}}
            @if ($section->settings->command_text)
                <div class="inline-flex items-center justify-between gap-4 rounded-xl border border-slate-700 bg-slate-950/90 px-5 py-3 font-mono text-sm text-indigo-300 shadow-inner max-w-xl w-full mx-auto mb-8">
                    <span class="truncate">$ {{ $section->settings->command_text }}</span>
                    <span class="text-xs text-slate-400 font-sans shrink-0">📋 copy</span>
                </div>
            @endif

            {{-- CTA Buttons --}}
            <div class="flex flex-wrap items-center justify-center gap-4">
                @if ($section->settings->button_label)
                    <a href="{{ $section->settings->button_url }}"
                        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-indigo-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-indigo-600/30 hover:from-red-500 hover:to-indigo-500 transition-all hover:scale-[1.02]">
                        {{ $section->settings->button_label }}
                    </a>
                @endif
                @if ($section->settings->secondary_label)
                    <a href="{{ $section->settings->secondary_url }}"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-800/90 border border-slate-700 px-6 py-3.5 text-base font-semibold text-white hover:bg-slate-700 transition-all hover:scale-[1.02]">
                        {{ $section->settings->secondary_label }}
                    </a>
                @endif
            </div>

        </div>
    </div>
</section>
