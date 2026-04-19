@schema([
    'name' => 'Navigation',
    'settings' => [
        [
            'id' => 'align',
            'type' => 'select',
            'label' => 'Alignment',
            'default' => 'center',
            'options' => [['value' => 'left', 'label' => 'Left'], ['value' => 'center', 'label' => 'Center'], ['value' => 'right', 'label' => 'Right']],
        ],
        [
            'id' => 'gap',
            'type' => 'select',
            'label' => 'Gap',
            'default' => '6',
            'options' => [['value' => '4', 'label' => 'Small'], ['value' => '6', 'label' => 'Medium'], ['value' => '8', 'label' => 'Large']],
        ],
        [
            'id' => 'text_color',
            'type' => 'color',
            'label' => 'Text Color',
            'default' => '#4b5563',
        ],
        [
            'id' => 'text_decoration',
            'type' => 'select',
            'label' => 'Text Decoration',
            'default' => 'none',
            'options' => [['value' => 'none', 'label' => 'None'], ['value' => 'underline', 'label' => 'Underline']],
        ],
    ],
    'blocks' => [['type' => 'nav-link']],
    'presets' => [
        [
            'name' => 'Main Navigation',
            'settings' => ['align' => 'center', 'gap' => '6'],
            'blocks' => [['type' => 'nav-link', 'settings' => ['label' => 'Home', 'url' => '#']], ['type' => 'nav-link', 'settings' => ['label' => 'About', 'url' => '#']], ['type' => 'nav-link', 'settings' => ['label' => 'Contact', 'url' => '#']]],
        ],
    ],
])

@php
    $justify = match ((string) $block->settings->align) {
        'left' => 'justify-start',
        'right' => 'justify-end',
        default => 'justify-center',
    };

    $gap = match ((string) $block->settings->gap) {
        '4' => 'gap-4',
        '8' => 'gap-8',
        default => 'gap-6',
    };
@endphp

<nav class="flex flex-wrap items-center {{ $justify }} {{ $gap }} w-full" {!! $block->editorAttributes() !!}>
    @blocks($block)
</nav>
