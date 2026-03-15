@schema([
    'name' => 'Footer',
    'settings' => [
        [
            'id' => 'style',
            'type' => 'select',
            'label' => 'Footer style',
            'default' => 'default',
            'options' => [['value' => 'default', 'label' => 'Default (4-column)'], ['value' => 'centered', 'label' => 'Centered (3-column centered)'], ['value' => 'modern', 'label' => 'Modern (gradient with animations)']],
        ],
        [
            'id' => 'logo_height',
            'type' => 'range',
            'label' => 'Logo height',
            'min' => 20,
            'max' => 80,
            'step' => 2,
            'unit' => 'px',
            'default' => 32,
        ],
        ['id' => 'facebook_url', 'type' => 'url', 'label' => 'Facebook URL', 'default' => ''],
        ['id' => 'instagram_url', 'type' => 'url', 'label' => 'Instagram URL', 'default' => ''],
        ['id' => 'twitter_url', 'type' => 'url', 'label' => 'X (Twitter) URL', 'default' => ''],
        ['id' => 'youtube_url', 'type' => 'url', 'label' => 'YouTube URL', 'default' => ''],
        ['id' => 'tiktok_url', 'type' => 'url', 'label' => 'TikTok URL', 'default' => ''],
        ['id' => 'privacy_url', 'type' => 'url', 'label' => 'Privacy Policy URL', 'default' => '/privacy'],
        ['id' => 'terms_url', 'type' => 'url', 'label' => 'Terms of Service URL', 'default' => '/terms'],
    ],
    'blocks' => [
        [
            'type' => 'brand',
            'name' => 'Brand Info',
            'limit' => 1,
            'settings' => [
                [
                    'id' => 'logo',
                    'type' => 'image_picker',
                    'label' => 'Logo image',
                    'info' => 'Falls back to the app default logo.',
                ],
                ['id' => 'tagline', 'type' => 'text', 'label' => 'Tagline', 'default' => 'Your ultimate fitness destination.'],
                ['id' => 'show_socials', 'type' => 'checkbox', 'label' => 'Show social links', 'default' => true],
            ],
        ],
        [
            'type' => 'footer-column',
            'name' => 'Column',
            'settings' => [
                ['id' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Column Heading'],
            ],
            'blocks' => [
                [
                    'type' => 'footer-link',
                    'name' => 'Link',
                    'settings' => [
                        ['id' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Link Label'],
                        ['id' => 'url', 'type' => 'url', 'label' => 'URL', 'default' => '#'],
                    ],
                ],
                [
                    'type' => 'footer-hours-item',
                    'name' => 'Hours Item',
                    'settings' => [
                        ['id' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Monday – Friday'],
                        ['id' => 'value', 'type' => 'text', 'label' => 'Value', 'default' => '5:00 AM – 11:00 PM'],
                        ['id' => 'is_accent', 'type' => 'checkbox', 'label' => 'Highlight (Accent Color)', 'default' => false],
                    ],
                ],
                [
                    'type' => 'footer-contact-item',
                    'name' => 'Contact Item',
                    'settings' => [
                        ['id' => 'icon', 'type' => 'icon_fa', 'label' => 'Icon', 'default' => 'fas fa-location-dot'],
                        ['id' => 'text', 'type' => 'text', 'label' => 'Text', 'default' => '123 Fitness Street, Muscle City'],
                        ['id' => 'url', 'type' => 'url', 'label' => 'URL (Optional)'],
                    ],
                ],
            ],
        ],
    ],
    'presets' => [
        [
            'name' => 'Default Footer',
            'settings' => ['style' => 'default'],
            'blocks' => [
                [
                    'type' => 'brand',
                    'settings' => [
                        'tagline' => 'Your ultimate fitness destination. We provide the best equipment, expert coaching, and a supportive community to help you reach your goals.',
                    ],
                ],
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'Quick Links'],
                    'blocks' => [['type' => 'footer-link', 'settings' => ['label' => 'About Us', 'url' => '#about']], ['type' => 'footer-link', 'settings' => ['label' => 'Services', 'url' => '#services']], ['type' => 'footer-link', 'settings' => ['label' => 'Pricing', 'url' => '#pricing']], ['type' => 'footer-link', 'settings' => ['label' => 'Contact', 'url' => '#contact']]],
                ],
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'Working Hours'],
                    'blocks' => [['type' => 'footer-hours-item', 'settings' => ['label' => 'Mon – Fri', 'value' => '5:00 AM – 10:00 PM']], ['type' => 'footer-hours-item', 'settings' => ['label' => 'Saturday', 'value' => '7:00 AM – 9:00 PM']], ['type' => 'footer-hours-item', 'settings' => ['label' => 'Sunday', 'value' => '7:00 AM – 9:00 PM', 'is_accent' => true]]],
                ],
                [
                    'type' => 'footer-column',
                    'settings' => ['title' => 'Contact Info'],
                    'blocks' => [['type' => 'footer-contact-item', 'settings' => ['icon' => 'fas fa-location-dot', 'text' => '123 Fitness Street, Muscle City']], ['type' => 'footer-contact-item', 'settings' => ['icon' => 'fas fa-phone', 'text' => '+1 (555) 123-4567']], ['type' => 'footer-contact-item', 'settings' => ['icon' => 'fas fa-envelope', 'text' => 'info@yourgym.com']]],
                ],
            ],
        ],
    ],
])

@php
    $style = $section->settings->style ?? 'default';
    $isCentered = $style === 'centered';
    $isModern = $style === 'modern';

    $logoHeight = (int) ($section->settings->logo_height ?? 32);

    $socials = [
        ['icon' => 'fa-brands fa-facebook-f', 'url' => $section->settings->facebook_url],
        ['icon' => 'fa-brands fa-instagram', 'url' => $section->settings->instagram_url],
        ['icon' => 'fa-brands fa-twitter', 'url' => $section->settings->twitter_url],
        ['icon' => 'fa-brands fa-youtube', 'url' => $section->settings->youtube_url],
        ['icon' => 'fa-brands fa-tiktok', 'url' => $section->settings->tiktok_url],
    ];

    $privacyUrl = $section->settings->privacy_url ?: '/privacy';
    $termsUrl = $section->settings->terms_url ?: '/terms';

    $blocks = $section->blocks;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     DEFAULT style
     ═══════════════════════════════════════════════════════════════════ --}}
@if ($style === 'default')
    <footer class="bg-bg-darker border-t border-border-dark" {!! $section->editorAttributes() !!}>
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                @foreach ($blocks as $block)
                    @if ($block->type === 'brand')
                        <div {!! $block->editorAttributes() !!}>
                            @php
                                $logoSrc = $block->settings->logo ?: config('app.logo-alt', asset('images/logo-alt.png'));
                            @endphp
                            <div class="flex items-center gap-2 text-white mb-4">
                                <a class="home-link flex items-center gap-3" href="/"
                                    title="{{ config('app.name') }}" rel="home">
                                    <img style="height: {{ $logoHeight }}px;" src="{{ $logoSrc }}"
                                        alt="{{ config('app.name') }}">
                                </a>
                            </div>
                            @if ($block->settings->tagline)
                                <p class="text-text-body text-sm leading-relaxed mb-5">{{ $block->settings->tagline }}</p>
                            @endif
                            @if ($block->settings->show_socials)
                                <div class="flex items-center gap-3">
                                    @foreach ($socials as $social)
                                        @if ($social['url'])
                                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                                class="text-text-body hover:text-accent transition-colors">
                                                <i class="{{ $social['icon'] }}"></i>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @elseif ($block->type === 'footer-column')
                        <div {!! $block->editorAttributes() !!}>
                            <h6 class="text-sm font-semibold text-white uppercase tracking-wider mb-5 md:mb-6 flex items-center gap-2">
                                {{ $block->settings->title }}
                            </h6>
                            <ul class="space-y-3 md:space-y-4">
                                @foreach ($block->blocks as $child)
                                    @if ($child->type === 'footer-link')
                                        <li {!! $child->editorAttributes() !!}>
                                            <a href="{{ $child->settings->url }}"
                                                class="text-sm text-text-body hover:text-accent transition-colors relative group/link">
                                                <span class="relative">{{ $child->settings->label }}</span>
                                            </a>
                                        </li>
                                    @elseif ($child->type === 'footer-hours-item')
                                        @php $isAccent = $child->settings->is_accent; @endphp
                                        <li {!! $child->editorAttributes() !!}
                                            class="flex justify-between gap-x-2 text-sm {{ $isAccent ? 'text-accent' : 'text-text-body' }}">
                                            <span>{{ $child->settings->label }}</span>
                                            <span>{{ $child->settings->value }}</span>
                                        </li>
                                    @elseif ($child->type === 'footer-contact-item')
                                        <li {!! $child->editorAttributes() !!}
                                            class="flex items-start gap-3 text-sm text-text-body group/contact">
                                            @if ($child->settings->icon)
                                                <i class="{{ $child->settings->icon }} text-accent mt-0.5 group-hover/contact:scale-125 transition-transform flex-shrink-0"></i>
                                            @endif
                                            @if ($child->settings->url)
                                                <a href="{{ $child->settings->url }}"
                                                    class="hover:text-accent transition-colors">
                                                    {{ $child->settings->text }}
                                                </a>
                                            @else
                                                <span class="group-hover/contact:text-accent transition-colors">
                                                    {{ $child->settings->text }}
                                                </span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>

            <div
                class="border-t border-border-dark mt-16 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-text-body/60 text-sm">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                </p>
                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ $privacyUrl }}" class="text-text-body/60 hover:text-accent transition-colors">
                        {{ __('Privacy Policy') }}
                    </a>
                    <a href="{{ $termsUrl }}" class="text-text-body/60 hover:text-accent transition-colors">
                        {{ __('Terms of Service') }}
                    </a>
                </div>
            </div>
        </div>
    </footer>

    {{-- ═══════════════════════════════════════════════════════════════════
     CENTERED style
     ═══════════════════════════════════════════════════════════════════ --}}
@elseif ($style === 'centered')
    <footer class="bg-bg-darker border-t border-border-dark" {!! $section->editorAttributes() !!}>
        <div class="container mx-auto px-4 py-20">
            <div class="max-w-4xl mx-auto text-center mb-12">
                @php $brandBlock = $blocks->where('type', 'brand')->first(); @endphp
                @if ($brandBlock = $blocks->where('type', 'brand')->first())
                    <div {!! $brandBlock->editorAttributes() !!} class="mb-8">
                        @php
                            $logoSrc = $brandBlock->settings->logo ?: config('app.logo-alt', asset('images/logo-alt.png'));
                        @endphp
                        <a class="home-link inline-block mb-6" href="/" title="{{ config('app.name') }}"
                            rel="home">
                            <img style="height: {{ $logoHeight }}px;" src="{{ $logoSrc }}"
                                alt="{{ config('app.name') }}">
                        </a>
                        @if ($brandBlock->settings->tagline)
                            <p class="text-text-body text-base max-w-2xl mx-auto">
                                {{ $brandBlock->settings->tagline }}
                            </p>
                        @endif
                        @if ($brandBlock->settings->show_socials)
                            <div class="flex items-center justify-center gap-6 mt-8">
                                @foreach ($socials as $social)
                                    @if ($social['url'])
                                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                            class="text-text-body hover:text-accent transition-all hover:scale-110">
                                            <i class="{{ $social['icon'] }} text-xl"></i>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
                    @foreach ($blocks->where('type', 'footer-column') as $block)
                        <div {!! $block->editorAttributes() !!}>
                            <h6 class="text-sm font-semibold text-white uppercase tracking-wider mb-5 md:mb-6 flex items-center gap-2">
                                {{ $block->settings->title }}
                            </h6>
                            <ul class="space-y-3 md:space-y-4">
                                @foreach ($block->blocks as $child)
                                    @if ($child->type === 'footer-link')
                                        <li {!! $child->editorAttributes() !!}>
                                            <a href="{{ $child->settings->url }}"
                                                class="text-sm text-text-body hover:text-accent transition-colors relative group/link">
                                                <span class="relative">{{ $child->settings->label }}</span>
                                            </a>
                                        </li>
                                    @elseif ($child->type === 'footer-hours-item')
                                        @php $isAccent = $child->settings->is_accent; @endphp
                                        <li {!! $child->editorAttributes() !!}
                                            class="flex flex-col sm:flex-row justify-center gap-x-2 text-sm {{ $isAccent ? 'text-accent' : 'text-text-body' }}">
                                            <span class="font-medium">{{ $child->settings->label }}</span>
                                            <span
                                                class="text-xs {{ $isAccent ? 'text-accent' : 'text-text-body/70' }}">{{ $child->settings->value }}</span>
                                        </li>
                                    @elseif ($child->type === 'footer-contact-item')
                                        <li {!! $child->editorAttributes() !!}
                                            class="flex items-start justify-center gap-3 text-sm text-text-body group/contact">
                                            @if ($child->settings->icon)
                                                <i class="{{ $child->settings->icon }} text-accent mt-0.5 group-hover/contact:scale-125 transition-transform flex-shrink-0"></i>
                                            @endif
                                            @if ($child->settings->url)
                                                <a href="{{ $child->settings->url }}"
                                                    class="hover:text-accent transition-colors text-xs">
                                                    {{ $child->settings->text }}
                                                </a>
                                            @else
                                                <span
                                                    class="text-xs group-hover/contact:text-accent transition-colors">
                                                    {{ $child->settings->text }}
                                                </span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col items-center gap-4 py-8 border-t border-border-dark/50">
                <div class="flex items-center gap-8 text-sm uppercase tracking-widest mb-2">
                    <a href="{{ $privacyUrl }}" class="text-text-body hover:text-accent transition-colors">
                        {{ __('Privacy Policy') }}
                    </a>
                    <a href="{{ $termsUrl }}" class="text-text-body hover:text-accent transition-colors">
                        {{ __('Terms of Service') }}
                    </a>
                </div>
                <p class="text-text-body/40 text-xs tracking-widest uppercase">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </div>
    </footer>

    {{-- ═══════════════════════════════════════════════════════════════════
     MODERN style
     ═══════════════════════════════════════════════════════════════════ --}}
@else
    <footer class="bg-gradient-to-b from-bg-dark to-bg-darker relative overflow-hidden" {!! $section->editorAttributes() !!}>

        {{-- Decorative blobs --}}
        <div
            class="absolute top-0 right-0 w-96 h-96 bg-accent/5 rounded-full -mr-48 -mt-48 blur-3xl pointer-events-none">
        </div>
        <div
            class="absolute bottom-0 left-0 w-80 h-80 bg-accent/5 rounded-full -ml-40 -mb-40 blur-3xl pointer-events-none">
        </div>

        <div class="container mx-auto px-4 py-20 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">

                @foreach ($blocks as $block)
                    @if ($block->type === 'brand')
                        <div {!! $block->editorAttributes() !!}>
                            @php
                                $logoSrc = $block->settings->logo ?: config('app.logo-alt', asset('images/logo-alt.png'));
                            @endphp
                            <div class="flex items-center gap-2 text-white mb-6">
                                <a class="home-link flex items-center gap-3 group" href="/"
                                    title="{{ config('app.name') }}" rel="home">
                                    <div class="relative">
                                        <div
                                            class="absolute inset-0 bg-accent/20 rounded-lg blur group-hover:blur-md transition-all">
                                        </div>
                                        <img class="relative" style="height: {{ $logoHeight }}px;"
                                            src="{{ $logoSrc }}" alt="{{ config('app.name') }}">
                                    </div>
                                </a>
                            </div>
                            @if ($block->settings->tagline)
                                <p class="text-text-body text-sm leading-relaxed mb-6">{{ $block->settings->tagline }}</p>
                            @endif
                            @if ($block->settings->show_socials)
                                <div class="flex items-center gap-3">
                                    @foreach ($socials as $social)
                                        @if ($social['url'])
                                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                                class="w-10 h-10 flex items-center justify-center rounded-lg bg-accent/10 text-accent hover:bg-accent hover:text-white transition-all duration-300 hover:scale-110">
                                                <i class="{{ $social['icon'] }}"></i>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @elseif ($block->type === 'footer-column')
                        <div {!! $block->editorAttributes() !!}>
                            <h6 class="text-sm font-semibold text-white uppercase tracking-wider mb-5 md:mb-6 flex items-center gap-2">
                                <span class="w-1 h-5 bg-accent rounded-full"></span>
                                {{ $block->settings->title }}
                            </h6>
                            <ul class="space-y-3 md:space-y-4">
                                @foreach ($block->blocks as $child)
                                    @if ($child->type === 'footer-link')
                                        <li {!! $child->editorAttributes() !!}>
                                            <a href="{{ $child->settings->url }}"
                                                class="text-sm text-text-body hover:text-accent transition-colors relative group/link">
                                                <span class="relative">{{ $child->settings->label }}
                                                    <span
                                                        class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover/link:w-full transition-all duration-300"></span>
                                                </span>
                                            </a>
                                        </li>
                                    @elseif ($child->type === 'footer-hours-item')
                                        @php $isAccent = $child->settings->is_accent; @endphp
                                        <li {!! $child->editorAttributes() !!}
                                            class="flex justify-between gap-x-2 text-sm {{ $isAccent ? 'text-accent' : 'text-text-body' }}">
                                            <span>{{ $child->settings->label }}</span>
                                            <span>{{ $child->settings->value }}</span>
                                        </li>
                                    @elseif ($child->type === 'footer-contact-item')
                                        <li {!! $child->editorAttributes() !!}
                                            class="flex items-start gap-3 text-sm text-text-body group/contact">
                                            @if ($child->settings->icon)
                                                <i class="{{ $child->settings->icon }} text-accent mt-0.5 group-hover/contact:scale-125 transition-transform flex-shrink-0"></i>
                                            @endif
                                            @if ($child->settings->url)
                                                <a href="{{ $child->settings->url }}"
                                                    class="hover:text-accent transition-colors">
                                                    {{ $child->settings->text }}
                                                </a>
                                            @else
                                                <span class="group-hover/contact:text-accent transition-colors">
                                                    {{ $child->settings->text }}
                                                </span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach

            </div>

            {{-- Divider --}}
            <div class="h-px bg-gradient-to-r from-transparent via-border-dark to-transparent mb-8"></div>

            {{-- Bottom bar --}}
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-text-body">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
                <div class="flex items-center gap-8">
                    <a href="{{ $privacyUrl }}" class="hover:text-accent transition-colors relative group/legal">
                        <span class="relative">{{ __('Privacy Policy') }}
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover/legal:w-full transition-all duration-300"></span>
                        </span>
                    </a>
                    <a href="{{ $termsUrl }}" class="hover:text-accent transition-colors relative group/legal">
                        <span class="relative">{{ __('Terms of Service') }}
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-accent group-hover/legal:w-full transition-all duration-300"></span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </footer>
@endif
