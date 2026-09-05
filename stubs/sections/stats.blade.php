@schema([
    'name' => 'Stats & Impact',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Section Badge', 'default' => 'Proven Benchmarks'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Built for Speed, Engineered for Scale'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'Key performance indicators and architecture highlights.'],
    ],
    'blocks' => [
        [
            'type' => 'stat-item',
            'name' => 'Metric Badge',
            'settings' => [
                ['id' => 'value', 'type' => 'text', 'label' => 'Stat Value', 'default' => '0.2ms'],
                ['id' => 'label', 'type' => 'text', 'label' => 'Stat Label', 'default' => 'Render Speed'],
                ['id' => 'description', 'type' => 'text', 'label' => 'Short Description', 'default' => 'Average section hydration & render time.'],
                ['id' => 'icon', 'type' => 'icon_md', 'label' => 'Icon', 'default' => 'bolt'],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Performance Metrics',
            'settings' => [
                'badge' => 'Benchmarks',
                'title' => 'Built for Speed, Engineered for Precision',
                'subtitle' => 'Zero database overhead, strict layer encapsulation, and sub-millisecond Blade hydration.',
            ],
            'blocks' => [
                [
                    'type' => 'stat-item',
                    'settings' => [
                        'value' => '< 0.2ms',
                        'label' => 'Render Overhead',
                        'description' => 'Fast Blade view hydration with zero extra SQL queries.',
                        'icon' => 'bolt',
                    ],
                ],
                [
                    'type' => 'stat-item',
                    'settings' => [
                        'value' => '13+',
                        'label' => 'Setting Types',
                        'description' => 'Rich text, image pickers, colors, sliders, icons, URLs.',
                        'icon' => 'tune',
                    ],
                ],
                [
                    'type' => 'stat-item',
                    'settings' => [
                        'value' => '100%',
                        'label' => 'PSR-12 Typed',
                        'description' => 'Readonly value objects & PHP 8.2 strict typing.',
                        'icon' => 'verified',
                    ],
                ],
                [
                    'type' => 'stat-item',
                    'settings' => [
                        'value' => '0',
                        'label' => 'DB Overhead',
                        'description' => 'Pure JSON document persistence & theme shadowing.',
                        'icon' => 'inventory_2',
                    ],
                ],
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} id="stats" class="py-16 lg:py-24 bg-gradient-to-b from-gray-900 to-slate-950 text-white border-t border-gray-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-12 space-y-3">
            @if ($section->settings->badge)
                <span class="inline-flex items-center rounded-full bg-indigo-500/10 border border-indigo-500/30 px-3.5 py-1 text-xs font-semibold text-indigo-400">
                    {{ $section->settings->badge }}
                </span>
            @endif
            <h2 class="text-3xl font-extrabold tracking-tight text-white">
                {{ $section->settings->title }}
            </h2>
            <p class="text-gray-400 text-sm sm:text-base">
                {{ $section->settings->subtitle }}
            </p>
        </div>

        {{-- Metrics Grid --}}
        @if ($section->blocks->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'stat-item')
                        <div {!! $block->editorAttributes() !!}
                            class="rounded-2xl border border-gray-800 bg-gray-950/70 p-6 text-center shadow-lg hover:border-indigo-500/40 transition-colors">
                            <div class="mb-2 flex justify-center">
                                @if ($block->settings->icon)
                                    <span class="material-icons text-3xl text-indigo-400">{{ $block->settings->icon }}</span>
                                @endif
                            </div>
                            <div class="text-4xl font-extrabold tracking-tight text-white bg-gradient-to-r from-red-400 to-indigo-400 bg-clip-text text-transparent mb-1">
                                {{ $block->settings->value }}
                            </div>
                            <div class="text-base font-bold text-gray-200 mb-1">
                                {{ $block->settings->label }}
                            </div>
                            <p class="text-xs text-gray-400">
                                {{ $block->settings->description }}
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
