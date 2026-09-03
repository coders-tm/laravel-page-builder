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
                            class="flex flex-col justify-between h-full rounded-2xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8 relative group hover:border-slate-700/80 transition-all duration-300 shadow-xl">
                            
                            {{-- Step Number & Title --}}
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-3xl font-extrabold font-mono text-red-500/90">
                                        {{ $block->settings->step_number }}
                                    </span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/50"></span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-100 mb-2">
                                    {{ $block->settings->title }}
                                </h3>
                                <p class="text-sm text-slate-400 leading-relaxed">
                                    {{ $block->settings->description }}
                                </p>
                            </div>

                            {{-- IDE Code Window UI --}}
                            @if ($block->settings->code_snippet)
                                <div class="mt-auto h-52 w-full flex flex-col rounded-xl border border-slate-800/90 bg-slate-950/90 shadow-inner overflow-hidden">
                                    {{-- Window Header --}}
                                    <div class="flex items-center justify-between border-b border-slate-800/80 bg-slate-900/60 px-3.5 py-2 shrink-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-2.5 w-2.5 rounded-full bg-red-500/80"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-500/80"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-green-500/80"></span>
                                        </div>
                                        <span class="text-[10px] font-mono text-slate-400 font-medium">
                                            @if ($loop->index === 0)
                                                hero.blade.php
                                            @elseif ($loop->index === 1)
                                                home.json
                                            @else
                                                PageController.php
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Window Code Content --}}
                                    <div class="p-4 flex-1 flex items-center font-mono text-[11px] sm:text-xs leading-relaxed text-indigo-300 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                        <pre class="m-0 p-0 font-mono text-[11px] sm:text-xs leading-relaxed text-slate-200 whitespace-pre w-full"><code>{!! e($block->settings->code_snippet) !!}</code></pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
