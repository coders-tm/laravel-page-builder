@schema([
    'name' => 'Features Grid',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Section Badge', 'default' => 'Core Capabilities'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Designed for Developers Who Value Quality'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'Everything you need to build flexible, high-speed page composition systems in Laravel.'],
        ['id' => 'columns', 'type' => 'select', 'label' => 'Grid Columns', 'default' => '3',
         'options' => [
             ['value' => '2', 'label' => '2 Columns'],
             ['value' => '3', 'label' => '3 Columns'],
             ['value' => '4', 'label' => '4 Columns'],
         ]],
    ],
    'blocks' => [
        [
            'type' => 'feature-card',
            'name' => 'Feature Card',
            'settings' => [
                ['id' => 'icon', 'type' => 'text', 'label' => 'Icon (Emoji or SVG/Class)', 'default' => '🧩'],
                ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Schema Driven'],
                ['id' => 'description', 'type' => 'textarea', 'label' => 'Description', 'default' => 'Define section and block settings inside Blade files using @schema().'],
                ['id' => 'badge_text', 'type' => 'text', 'label' => 'Card Tag / Badge', 'default' => ''],
                ['id' => 'link_url', 'type' => 'url', 'label' => 'Link URL', 'default' => ''],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Developer Features',
            'settings' => [
                'badge' => 'Features',
                'title' => 'Designed for Developers Who Value Speed & Clean Code',
                'subtitle' => 'Uncompromising architecture built strictly following PSR-12, layer isolation, and Blade purity.',
                'columns' => '3',
            ],
            'blocks' => [
                [
                    'type' => 'feature-card',
                    'settings' => [
                        'icon' => '🧩',
                        'title' => 'Inline @schema() Directive',
                        'description' => 'Write your field schema inside the Blade template. No separate YAML files, database migrations, or registration boilerplate.',
                        'badge_text' => 'Zero Config',
                    ],
                ],
                [
                    'type' => 'feature-card',
                    'settings' => [
                        'icon' => '🎨',
                        'title' => 'Multi-Theme Shadowing',
                        'description' => 'Shadow built-in sections and blocks effortlessly. Register custom themes with total control over layouts and components.',
                        'badge_text' => 'Multi-Theme',
                    ],
                ],
                [
                    'type' => 'feature-card',
                    'settings' => [
                        'icon' => '📄',
                        'title' => 'Clean JSON Storage',
                        'description' => 'Pages are stored as clear, deterministic JSON files. Version control your content layouts directly in Git.',
                        'badge_text' => 'Git Friendly',
                    ],
                ],
                [
                    'type' => 'feature-card',
                    'settings' => [
                        'icon' => '⚡',
                        'title' => 'Sub-Millisecond Speed',
                        'description' => 'Hydrated runtime models render straight to Blade views with zero runtime query bloat or heavy client scripts.',
                        'badge_text' => '0.2ms Speed',
                    ],
                ],
                [
                    'type' => 'feature-card',
                    'settings' => [
                        'icon' => '🛠️',
                        'title' => '13+ Field Schema Types',
                        'description' => 'Rich text (TipTap), color pickers, range sliders, selects, image pickers, icons, URLs, and nested local blocks.',
                        'badge_text' => 'Rich Input',
                    ],
                ],
                [
                    'type' => 'feature-card',
                    'settings' => [
                        'icon' => '🔒',
                        'title' => 'Strictly Typed PHP 8.2+',
                        'description' => 'Readonly value objects, layered architecture (Schema -> Registry -> Components -> Renderer -> Services).',
                        'badge_text' => 'PHP 8.2+',
                    ],
                ],
            ],
        ],
    ],
])

@php
    $gridCols = match ($section->settings->columns) {
        '2' => 'md:grid-cols-2',
        '4' => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section {!! $section->editorAttributes() !!} id="features" class="py-20 lg:py-28 bg-gray-900 text-white border-t border-gray-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            @if ($section->settings->badge)
                <span class="inline-flex items-center rounded-full bg-indigo-500/10 border border-indigo-500/30 px-3.5 py-1 text-xs font-semibold text-indigo-400">
                    {{ $section->settings->badge }}
                </span>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                {{ $section->settings->title }}
            </h2>
            <p class="text-base sm:text-lg text-gray-400">
                {{ $section->settings->subtitle }}
            </p>
        </div>

        {{-- Feature Cards Grid --}}
        @if ($section->blocks->isNotEmpty())
            <div class="grid grid-cols-1 {{ $gridCols }} gap-8">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'feature-card')
                        <div {!! $block->editorAttributes() !!}
                            class="group relative rounded-2xl border border-gray-800 bg-gray-950/60 p-6 sm:p-8 transition-all duration-300 hover:-translate-y-1 hover:border-indigo-500/50 hover:shadow-xl hover:shadow-indigo-500/10">
                            
                            {{-- Top Icon & Badge --}}
                            <div class="flex items-center justify-between mb-5">
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-2xl group-hover:scale-110 transition-transform">
                                    {{ $block->settings->icon }}
                                </span>
                                @if ($block->settings->badge_text)
                                    <span class="rounded-full bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-300">
                                        {{ $block->settings->badge_text }}
                                    </span>
                                @endif
                            </div>

                            {{-- Title & Description --}}
                            <h3 class="text-xl font-bold text-white mb-2 group-hover:text-indigo-400 transition-colors">
                                {{ $block->settings->title }}
                            </h3>
                            <p class="text-sm text-gray-400 leading-relaxed">
                                {{ $block->settings->description }}
                            </p>

                            @if ($block->settings->link_url)
                                <a href="{{ $block->settings->link_url }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 mt-4">
                                    <span>Learn more</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
