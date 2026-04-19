@schema([
    'name' => 'Nav Link',
    'settings' => [
        [
            'id' => 'label',
            'type' => 'text',
            'label' => 'Label',
            'default' => 'Link',
        ],
        [
            'id' => 'url',
            'type' => 'url',
            'label' => 'URL',
            'default' => '#',
        ],
        [
            'id' => 'text_color',
            'type' => 'color',
            'label' => 'Text Color (Overrides Parent)',
            'default' => '',
        ],
        [
            'id' => 'text_decoration',
            'type' => 'select',
            'label' => 'Text Decoration (Overrides Parent)',
            'default' => '',
            'options' => [['value' => '', 'label' => 'Inherit from Parent'], ['value' => 'none', 'label' => 'None'], ['value' => 'underline', 'label' => 'Underline']],
        ],
    ],
])

@php
    $textColor = $block->settings->text_color ?? $parent->settings->get('text_color', '#4b5563');
    $textDecoration = $block->settings->text_decoration ?? $parent->settings->get('text_decoration', 'none');
@endphp

<a href="{{ $block->settings->url }}"
    style="color: {{ $textColor }}; text-decoration: {{ $textDecoration }}; transition: color 0.2s;"
    {!! $block->editorAttributes() !!}>
    {{ $block->settings->label }}
</a>
