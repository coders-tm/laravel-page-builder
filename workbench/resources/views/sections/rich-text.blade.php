@schema([
    'name' => 'Rich Text',
    'settings' => [
        ['id' => 'heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'About Us'],
        ['id' => 'content', 'type' => 'richtext', 'label' => 'Content', 'default' => '<p>Share your story, values, or any information you want to highlight.</p>'],
        [
            'id' => 'alignment',
            'type' => 'select',
            'label' => 'Text Alignment',
            'default' => 'left',
            'options' => [
                ['value' => 'left', 'label' => 'Left'],
                ['value' => 'center', 'label' => 'Center'],
                ['value' => 'right', 'label' => 'Right'],
            ],
        ],
    ],
    'presets' => [
        ['name' => 'Rich Text'],
    ],
])

@php
    $alignClass = match((string) ($section->settings->alignment ?? 'left')) {
        'center' => 'text-center mx-auto',
        'right'  => 'text-right ml-auto',
        default  => 'text-left',
    };
@endphp

<section {!! $section->editorAttributes() !!} class="py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl {{ $alignClass }}">
            @if ($section->settings->heading)
                <h2 class="text-3xl font-bold text-gray-900 mb-6">
                    {{ $section->settings->heading }}
                </h2>
            @endif
            <div class="prose prose-gray max-w-none">
                {!! $section->settings->content !!}
            </div>
        </div>
    </div>
</section>
