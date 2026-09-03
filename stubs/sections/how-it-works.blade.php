@schema([
    'name' => 'How It Works',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Section Badge', 'default' => 'Simple 3-Step Workflow'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'How Laravel Page Builder Works'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'From Blade views to interactive page composition in 3 simple steps.'],
    ],
    'blocks' => [
        [
            'type' => 'step-card',
            'name' => 'Workflow Step',
            'settings' => [
                ['id' => 'step_number', 'type' => 'text', 'label' => 'Step Number', 'default' => '01'],
                ['id' => 'title', 'type' => 'text', 'label' => 'Step Title', 'default' => 'Define Schema in Blade'],
                ['id' => 'description', 'type' => 'textarea', 'label' => 'Description', 'default' => 'Add @schema([...]) at the top of your Blade file.'],
                ['id' => 'code_snippet', 'type' => 'textarea', 'label' => 'Code Snippet', 'default' => "@schema([\n    'name' => 'Banner',\n    'settings' => [...]\n])"],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => '3-Step Developer Workflow',
            'settings' => [
                'badge' => 'Workflow',
                'title' => 'How It Works in 3 Simple Steps',
                'subtitle' => 'No database setup needed. Start creating modular, editable landing pages in seconds.',
            ],
            'blocks' => [
                [
                    'type' => 'step-card',
                    'settings' => [
                        'step_number' => '01',
                        'title' => 'Write @schema() in Blade',
                        'description' => 'Create a Blade view inside resources/views/sections/. Define your settings, local blocks, and default presets in pure PHP arrays.',
                        'code_snippet' => "@schema([\n    'name' => 'Hero',\n    'settings' => [\n        ['id' => 'title', 'type' => 'text']\n    ]\n])",
                    ],
                ],
                [
                    'type' => 'step-card',
                    'settings' => [
                        'step_number' => '02',
                        'title' => 'Compose Layout in JSON',
                        'description' => 'Assemble pages with JSON documents or use the drag-and-drop live preview editor to tweak settings in real-time.',
                        'code_snippet' => "{\n  \"sections\": {\n    \"hero\": {\n      \"type\": \"hero\",\n      \"settings\": { \"title\": \"Hello\" }\n    }\n  },\n  \"order\": [\"hero\"]\n}",
                    ],
                ],
                [
                    'type' => 'step-card',
                    'settings' => [
                        'step_number' => '03',
                        'title' => 'Render Effortlessly',
                        'description' => 'Render the entire page using Page::render("home") or @blocks($section). Enjoy native Blade rendering speeds.',
                        'code_snippet' => "// Controller\nreturn Page::render('home');\n\n// Or Blade Directive\n@blocks(\$section)",
                    ],
                ],
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} id="how-it-works" class="py-20 lg:py-28 bg-slate-950 text-white relative">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            @if ($section->settings->badge)
                <span class="inline-flex items-center rounded-full bg-red-500/10 border border-red-500/30 px-3.5 py-1 text-xs font-semibold text-red-400">
                    {{ $section->settings->badge }}
                </span>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-100">
                {{ $section->settings->title }}
            </h2>
            <p class="text-base sm:text-lg text-slate-400">
                {{ $section->settings->subtitle }}
            </p>
        </div>

        {{-- Steps Grid --}}
        @if ($section->blocks->isNotEmpty())
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 relative">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'step-card')
                        <div {!! $block->editorAttributes() !!}
                            class="flex flex-col justify-between rounded-2xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8 relative">
                            
                            {{-- Step Number & Title --}}
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-3xl font-extrabold font-mono text-red-500/90">
                                        {{ $block->settings->step_number }}
                                    </span>
                                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-100 mb-2">
                                    {{ $block->settings->title }}
                                </h3>
                                <p class="text-sm text-slate-400 leading-relaxed mb-6">
                                    {{ $block->settings->description }}
                                </p>
                            </div>

                            {{-- Code Snippet Preview --}}
                            @if ($block->settings->code_snippet)
                                <div class="rounded-xl border border-slate-800 bg-slate-950 p-4 font-mono text-xs text-indigo-300 overflow-x-auto">
                                    <pre><code>{!! e($block->settings->code_snippet) !!}</code></pre>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
