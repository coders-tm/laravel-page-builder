@schema([
    'name' => 'Section',
    'settings' => [['id' => 'heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Default']],
    'blocks' => [['type' => '@theme']],
    'presets' => [['name' => 'Section']],
])

<section {!! $section->editorAttributes() !!}>
    @blocks($section)
</section>
