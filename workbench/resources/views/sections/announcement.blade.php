@schema([
    'name' => 'Announcement Bar',
    'settings' => [
        ['id' => 'text', 'type' => 'text', 'label' => 'Text', 'default' => 'Welcome to our store!'],
        ['id' => 'background_color', 'type' => 'color', 'label' => 'Background Color', 'default' => '#000000'],
        ['id' => 'text_color', 'type' => 'color', 'label' => 'Text Color', 'default' => '#ffffff'],
    ],
    'presets' => [
        ['name' => 'Announcement Bar'],
    ],
])

<div {!! $section->editorAttributes() !!}
    style="background-color: {{ $section->settings->background_color }}; color: {{ $section->settings->text_color }};"
    class="py-2 text-center text-sm font-medium">
    {{ $section->settings->text }}
</div>
