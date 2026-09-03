@schema([
    'name' => 'Testimonials',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Section Badge', 'default' => 'Developer Feedback'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Loved by Laravel Engineers Worldwide'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'See what developers and tech leads are saying about our architecture.'],
    ],
    'blocks' => [
        [
            'type' => 'testimonial-card',
            'name' => 'Testimonial Card',
            'settings' => [
                ['id' => 'quote', 'type' => 'textarea', 'label' => 'Quote', 'default' => 'This package revolutionized how we compose dynamic landing pages.'],
                ['id' => 'author_name', 'type' => 'text', 'label' => 'Author Name', 'default' => 'Jane Doe'],
                ['id' => 'author_role', 'type' => 'text', 'label' => 'Role / Title', 'default' => 'Lead Developer'],
                ['id' => 'company', 'type' => 'text', 'label' => 'Company', 'default' => 'Tech Corp'],
                ['id' => 'rating', 'type' => 'select', 'label' => 'Star Rating', 'default' => '5',
                 'options' => [
                     ['value' => '5', 'label' => '5 Stars ⭐⭐⭐⭐⭐'],
                     ['value' => '4', 'label' => '4 Stars ⭐⭐⭐⭐'],
                 ]],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Developer Reviews',
            'settings' => [
                'badge' => 'Social Proof',
                'title' => 'Trusted by Laravel Developers & Architects',
                'subtitle' => 'See why engineers choose our schema-driven Blade builder over traditional bloated alternatives.',
            ],
            'blocks' => [
                [
                    'type' => 'testimonial-card',
                    'settings' => [
                        'quote' => 'Laravel Page Builder allowed us to give content creators full layout control without sacrificing clean Blade code or performance.',
                        'author_name' => 'Alex Taylor',
                        'author_role' => 'Lead Architect',
                        'company' => 'Acme SaaS',
                        'rating' => '5',
                    ],
                ],
                [
                    'type' => 'testimonial-card',
                    'settings' => [
                        'quote' => 'The inline @schema() directive is pure genius. Defining settings right next to HTML elements eliminated hours of context switching.',
                        'author_name' => 'Elena Rostova',
                        'author_role' => 'Senior Fullstack Dev',
                        'company' => 'DevPulse',
                        'rating' => '5',
                    ],
                ],
                [
                    'type' => 'testimonial-card',
                    'settings' => [
                        'quote' => 'Multi-theme shadowing works like a charm. We built a multi-tenant SaaS with custom theme layouts in under a week.',
                        'author_name' => 'Marcus Vance',
                        'author_role' => 'CTO',
                        'company' => 'CloudStack',
                        'rating' => '5',
                    ],
                ],
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} class="py-20 lg:py-28 bg-slate-950 text-white border-t border-slate-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
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

        {{-- Testimonials Grid --}}
        @if ($section->blocks->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'testimonial-card')
                        <div {!! $block->editorAttributes() !!}
                            class="flex flex-col justify-between rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 relative hover:border-indigo-500/40 transition-colors">
                            
                            {{-- Stars --}}
                            <div class="flex items-center gap-1 text-amber-400 mb-4 text-sm">
                                @for ($i = 0; $i < (int) $block->settings->rating; $i++)
                                    ★
                                @endfor
                            </div>

                            {{-- Quote --}}
                            <blockquote class="text-sm sm:text-base text-slate-300 italic mb-6 leading-relaxed">
                                "{{ $block->settings->quote }}"
                            </blockquote>

                            {{-- Author Info --}}
                            <div class="flex items-center gap-3 pt-4 border-t border-slate-800">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-500 to-red-500 flex items-center justify-center font-bold text-white text-sm">
                                    {{ substr($block->settings->author_name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-white">{{ $block->settings->author_name }}</div>
                                    <div class="text-xs text-slate-400">{{ $block->settings->author_role }} · <span class="text-indigo-400">{{ $block->settings->company }}</span></div>
                                </div>
                            </div>

                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
