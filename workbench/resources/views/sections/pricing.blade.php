@schema([
    'name' => 'Pricing & Plans',
    'settings' => [
        ['id' => 'badge', 'type' => 'text', 'label' => 'Section Badge', 'default' => 'Flexible Licensing'],
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Choose the Right Edition for Your Project'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => 'From open-source side projects to high-scale enterprise applications.'],
    ],
    'blocks' => [
        [
            'type' => 'pricing-card',
            'name' => 'Pricing Plan',
            'settings' => [
                ['id' => 'name', 'type' => 'text', 'label' => 'Plan Name', 'default' => 'Community'],
                ['id' => 'badge', 'type' => 'text', 'label' => 'Plan Badge', 'default' => 'MIT License'],
                ['id' => 'price', 'type' => 'text', 'label' => 'Price Display', 'default' => 'Free'],
                ['id' => 'period', 'type' => 'text', 'label' => 'Billing Period', 'default' => 'forever'],
                ['id' => 'description', 'type' => 'textarea', 'label' => 'Short Description', 'default' => 'Everything you need for open-source and personal Laravel projects.'],
                ['id' => 'is_popular', 'type' => 'checkbox', 'label' => 'Highlight as Featured', 'default' => false],
                ['id' => 'button_label', 'type' => 'text', 'label' => 'Button Text', 'default' => 'Get Started'],
                ['id' => 'button_url', 'type' => 'url', 'label' => 'Button URL', 'default' => '#'],
                ['id' => 'features_list', 'type' => 'textarea', 'label' => 'Features List (1 per line)', 'default' => "Full Blade @schema() Engine\nMulti-Theme Shadowing\nJSON Page Storage\n13+ Built-in Field Types"],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Package Pricing Plans',
            'settings' => [
                'badge' => 'Licensing & Plans',
                'title' => 'Transparent Options for Every Project',
                'subtitle' => '100% open source core with powerful optional developer tooling.',
            ],
            'blocks' => [
                [
                    'type' => 'pricing-card',
                    'settings' => [
                        'name' => 'Community',
                        'badge' => 'Open Source',
                        'price' => '$0',
                        'period' => 'forever',
                        'description' => 'Perfect for individual developers and open-source Laravel applications.',
                        'is_popular' => false,
                        'button_label' => 'Install via Composer',
                        'button_url' => 'https://github.com/coderstm/laravel-page-builder',
                        'features_list' => "Full Blade @schema Engine\nMulti-Theme Shadowing\nJSON Page Storage\n13+ Schema Field Types\nZero Database Lock-in\nCommunity Discord Support",
                    ],
                ],
                [
                    'type' => 'pricing-card',
                    'settings' => [
                        'name' => 'Developer Pro',
                        'badge' => 'Most Popular',
                        'price' => '$99',
                        'period' => 'one-time',
                        'description' => 'Enhanced live preview editor UI with advanced TipTap rich text integrations.',
                        'is_popular' => true,
                        'button_label' => 'Get Pro License',
                        'button_url' => '#',
                        'features_list' => "Everything in Community\nLive Drag & Drop UI Editor\nTipTap Rich Text Blocks\nTheme Generator CLI\nPriority Email Support\nCommercial SaaS License",
                    ],
                ],
                [
                    'type' => 'pricing-card',
                    'settings' => [
                        'name' => 'Enterprise',
                        'badge' => 'Organizations',
                        'price' => 'Custom',
                        'period' => 'contact us',
                        'description' => 'For large organizations building multi-tenant white-label page builders.',
                        'is_popular' => false,
                        'button_label' => 'Contact Team',
                        'button_url' => '#',
                        'features_list' => "Everything in Pro\nMulti-Tenant Field Isolation\nCustom Schema Field Plugins\nWhite-label Editor Branding\nDedicated SLA & Support\nArchitecture Code Audit",
                    ],
                ],
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} id="pricing" class="py-20 lg:py-28 bg-gray-900 text-white border-t border-gray-800">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            @if ($section->settings->badge)
                <span class="inline-flex items-center rounded-full bg-red-500/10 border border-red-500/30 px-3.5 py-1 text-xs font-semibold text-red-400">
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

        {{-- Pricing Cards Grid --}}
        @if ($section->blocks->isNotEmpty())
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                @foreach ($section->blocks as $block)
                    @if ($block->type === 'pricing-card')
                        @php
                            $isPopular = $block->settings->is_popular;
                            $cardClasses = $isPopular
                                ? 'border-2 border-indigo-500 bg-gray-950 shadow-2xl shadow-indigo-500/10 lg:-translate-y-2'
                                : 'border border-gray-800 bg-gray-950/60';
                        @endphp

                        <div {!! $block->editorAttributes() !!} class="relative flex flex-col justify-between rounded-2xl p-6 sm:p-8 {{ $cardClasses }}">
                            @if ($isPopular)
                                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-red-600 to-indigo-600 px-3.5 py-0.5 text-xs font-bold uppercase tracking-wider text-white shadow-md">
                                    {{ $block->settings->badge }}
                                </div>
                            @endif

                            <div>
                                {{-- Header & Badge --}}
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-bold text-white">{{ $block->settings->name }}</h3>
                                    @if (!$isPopular && $block->settings->badge)
                                        <span class="rounded-full bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-300">
                                            {{ $block->settings->badge }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Price --}}
                                <div class="flex items-baseline gap-1 mb-3">
                                    <span class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white">{{ $block->settings->price }}</span>
                                    @if ($block->settings->period)
                                        <span class="text-sm font-medium text-gray-400">/ {{ $block->settings->period }}</span>
                                    @endif
                                </div>

                                <p class="text-sm text-gray-400 mb-6 leading-relaxed">
                                    {{ $block->settings->description }}
                                </p>

                                {{-- Features List --}}
                                @php
                                    $features = array_filter(explode("\n", str_replace("\r", "", $block->settings->features_list)));
                                @endphp
                                <ul class="space-y-3 border-t border-gray-800/80 pt-6 mb-8 text-sm">
                                    @foreach ($features as $feature)
                                        <li class="flex items-center gap-2.5 text-gray-300">
                                            <svg class="h-5 w-5 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ trim($feature) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- CTA Button --}}
                            <a href="{{ $block->settings->button_url }}"
                                class="w-full inline-flex items-center justify-center rounded-xl py-3 px-4 text-sm font-semibold transition-all hover:scale-[1.02] {{ $isPopular ? 'bg-gradient-to-r from-red-600 to-indigo-600 hover:from-red-500 hover:to-indigo-500 text-white shadow-lg shadow-indigo-600/30' : 'bg-gray-800 hover:bg-gray-700 text-white' }}">
                                {{ $block->settings->button_label }}
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
