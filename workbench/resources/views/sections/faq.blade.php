@schema([
    'name' => 'Frequently Asked Questions',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Section Badge', 'default' => 'FAQ'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Frequently Asked Questions'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'Everything you need to know about Laravel Page Builder architecture and usage.'],
    ],
    'blocks' => [
        [
            'type' => 'faq-item',
            'name' => 'FAQ Item',
            'settings' => [
                ['id' => 'question', 'type' => 'text', 'label' => 'Question', 'default' => 'What is Laravel Page Builder?'],
                ['id' => 'answer', 'type' => 'textarea', 'label' => 'Answer', 'default' => 'It is a multi-theme, JSON-driven page composition engine for Laravel.'],
                ['id' => 'is_open', 'type' => 'checkbox', 'label' => 'Open by Default', 'default' => false],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Package FAQ',
            'settings' => [
                'badge' => 'FAQ',
                'title' => 'Frequently Asked Questions',
                'subtitle' => 'Answers to common developer questions about schema extraction, theme shadowing, and performance.',
            ],
            'blocks' => [
                [
                    'type' => 'faq-item',
                    'settings' => [
                        'question' => 'How does the inline @schema() directive work?',
                        'answer' => '@schema() is extracted directly from your Blade view files by SchemaExtractor during scanner registration. It registers immutable SectionSchema and BlockSchema value objects without running or rendering the view.',
                        'is_open' => true,
                    ],
                ],
                [
                    'type' => 'faq-item',
                    'settings' => [
                        'question' => 'Do I need database migrations for page layouts?',
                        'answer' => 'No! Pages and templates are backed by clean JSON documents stored on disk in your resources directory. You can track layouts directly in Git version control.',
                        'is_open' => false,
                    ],
                ],
                [
                    'type' => 'faq-item',
                    'settings' => [
                        'question' => 'How does theme shadowing work?',
                        'answer' => 'When a theme is active, any Blade file located in your theme views directory automatically shadows built-in package sections and blocks. Last registration always wins.',
                        'is_open' => false,
                    ],
                ],
                [
                    'type' => 'faq-item',
                    'settings' => [
                        'question' => 'What setting types are supported in @schema()?',
                        'answer' => 'Supported types include text, textarea, richtext (TipTap), select, radio, checkbox, number, range slider, color, image_picker, alignment, icon_picker, and url.',
                        'is_open' => false,
                    ],
                ],
                [
                    'type' => 'faq-item',
                    'settings' => [
                        'question' => 'What is the performance overhead of rendering pages?',
                        'answer' => 'Hydrated section and block models render directly into native Blade views with sub-millisecond execution times (<0.2ms) and zero extra database queries.',
                        'is_open' => false,
                    ],
                ],
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} id="faq" class="py-20 lg:py-28 bg-slate-950 text-white border-t border-slate-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
        {{-- Section Header --}}
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-4">
            @if ($section->settings->badge)
                <span class="inline-flex items-center rounded-full bg-indigo-500/10 border border-indigo-500/30 px-3.5 py-1 text-xs font-semibold text-indigo-400">
                    {{ $section->settings->badge }}
                </span>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                {{ $section->settings->title }}
            </h2>
            <p class="text-base sm:text-lg text-slate-400">
                {{ $section->settings->subtitle }}
            </p>
        </div>

        {{-- Accordion List using native <details> elements --}}
        @if ($section->blocks->isNotEmpty())
            <div class="space-y-4">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'faq-item')
                        <details {!! $block->editorAttributes() !!}
                            class="group border border-slate-800 rounded-2xl bg-slate-900/60 transition-all duration-200 open:border-indigo-500/50 open:bg-slate-900/90 shadow-sm"
                            @if ($block->settings->is_open) open @endif>
                            
                            {{-- Accordion Summary Trigger --}}
                            <summary class="flex cursor-pointer items-center justify-between p-6 text-base font-bold text-white select-none hover:text-indigo-400 transition-colors">
                                <span>{{ $block->settings->question }}</span>
                                <span class="ml-4 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-800 text-slate-400 group-hover:bg-indigo-600/20 group-hover:text-indigo-400 transition-all">
                                    <svg class="h-4 w-4 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </summary>

                            {{-- Accordion Body --}}
                            <div class="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                                {!! nl2br(e($block->settings->answer)) !!}
                            </div>
                        </details>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
