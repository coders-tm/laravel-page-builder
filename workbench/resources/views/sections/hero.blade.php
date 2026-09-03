@schema([
    'name' => 'Hero',
    'settings' => [['id' => 'badge_text', 'type' => 'text', 'label' => 'Header Badge', 'default' => 'Welcome Home — Next-Gen Package'], ['id' => 'hero_image', 'type' => 'image_picker', 'label' => 'Hero Image', 'default' => '/statics/hero.png'], ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Build High-Performance Page Builders in Minutes with'], ['id' => 'title_highlight', 'type' => 'text', 'label' => 'Title Highlight', 'default' => 'Laravel & Blade'], ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'Hello World']],
    'blocks' => [
        [
            'type' => 'hero-badge-pill',
            'name' => 'Pill Badge',
            'settings' => [['id' => 'text', 'type' => 'text', 'label' => 'Pill Text', 'default' => 'Blade Native'], ['id' => 'icon', 'type' => 'text', 'label' => 'Icon Class / Emoji', 'default' => '⚡']],
        ],
        [
            'type' => 'hero-action',
            'name' => 'Action Button',
            'settings' => [['id' => 'label', 'type' => 'text', 'label' => 'Button Label', 'default' => 'Get Started'], ['id' => 'url', 'type' => 'url', 'label' => 'Button URL', 'default' => '#pricing'], ['id' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'primary', 'options' => [['value' => 'primary', 'label' => 'Primary Gradient'], ['value' => 'secondary', 'label' => 'Dark Glass'], ['value' => 'outline', 'label' => 'Outline']]]],
        ],
    ],
    'presets' => [
        [
            'name' => 'Developer SaaS Hero',
            'settings' => [
                'badge_text' => '⚡ Next-Gen Laravel Package',
                'hero_image' => '/statics/hero.png',
                'title' => 'Build High-Performance Page Builders in Minutes with',
                'title_highlight' => 'Laravel & Blade',
                'subtitle' => 'A multi-theme, JSON-driven page builder. Define block schemas directly in your Blade views with zero extra database queries or Javascript bloat.',
            ],
            'blocks' => [['type' => 'hero-badge-pill', 'settings' => ['text' => 'Blade Native', 'icon' => '🔥']], ['type' => 'hero-badge-pill', 'settings' => ['text' => 'JSON Driven', 'icon' => '📦']], ['type' => 'hero-badge-pill', 'settings' => ['text' => 'Zero Dependencies', 'icon' => '⚡']], ['type' => 'hero-action', 'settings' => ['label' => 'Explore Documentation', 'url' => '#features', 'style' => 'primary']], ['type' => 'hero-action', 'settings' => ['label' => 'View Github', 'url' => 'https://github.com/coders-tm/laravel-page-builder', 'style' => 'secondary']]],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} class="relative overflow-hidden bg-slate-950 py-20 lg:py-28 text-white">
    {{-- Background glowing gradients --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
        <div class="absolute top-1/2 -left-40 h-96 w-96 rounded-full bg-red-600/20 blur-3xl"></div>
    </div>

    <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-8">

            {{-- Left Content Column --}}
            <div class="lg:col-span-7 space-y-6">

                {{-- Top Badge / Pills --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if ($section->settings->badge_text)
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/30 px-3.5 py-1 text-xs font-semibold text-indigo-300">
                            {{ $section->settings->badge_text }}
                        </span>
                    @endif

                    @foreach ($section->blocks as $block)
                        @if ($block->type === 'hero-badge-pill')
                            <span {!! $block->editorAttributes() !!}
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-800/80 border border-slate-700/80 px-3 py-1 text-xs font-medium text-slate-300">
                                <span>{{ $block->settings->icon }}</span>
                                <span>{{ $block->settings->text }}</span>
                            </span>
                        @endif
                    @endforeach
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-slate-100 leading-tight">
                    {{ $section->settings->title }}
                    <span
                        class="block bg-gradient-to-r from-red-500 via-indigo-400 to-sky-400 bg-clip-text text-transparent">
                        {{ $section->settings->title_highlight }}
                    </span>
                </h1>

                {{-- Subtitle --}}
                <p class="text-lg text-slate-300 max-w-2xl leading-relaxed">
                    {{ $section->settings->subtitle }}
                </p>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-4 pt-4">
                    @foreach ($section->blocks as $block)
                        @if ($block->type === 'hero-action')
                            @php
                                $styleClasses = match ($block->settings->style) {
                                    'secondary'
                                        => 'bg-slate-800/90 hover:bg-slate-700 text-white border border-slate-700 shadow-sm',
                                    'outline'
                                        => 'bg-transparent hover:bg-slate-800/50 text-slate-300 border border-slate-600',
                                    default
                                        => 'bg-gradient-to-r from-red-600 to-indigo-600 hover:from-red-500 hover:to-indigo-500 text-white shadow-lg shadow-indigo-600/30',
                                };
                            @endphp
                            <a {!! $block->editorAttributes() !!} href="{{ $block->settings->url }}"
                                class="inline-flex items-center justify-center rounded-xl px-6 py-3.5 text-base font-semibold transition-all hover:scale-[1.02] {{ $styleClasses }}">
                                {{ $block->settings->label }}
                            </a>
                        @endif
                    @endforeach
                </div>

            </div>

            {{-- Right Column: Image --}}
            <div class="lg:col-span-5">
                @if (!empty($section->settings->hero_image))
                    <div class="relative shadow-2xl backdrop-blur-xl overflow-hidden p-2 group">
                        <img src="{{ asset($section->settings->hero_image) }}" alt="Hero Image"
                            class="w-full h-auto object-cover rounded-xl transition-transform duration-500 group-hover:scale-105" />
                    </div>
                @endif
            </div>

        </div>
    </div>
</section>
