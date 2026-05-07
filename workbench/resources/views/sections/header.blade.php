@schema([
    'name' => 'Header',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Default Header Title'],
    ],
    'presets' => [
        [
            'name' => 'My Site Header',
            'settings' => [
                'title' => 'My Site Header',
            ],
        ],
    ],
])
<header {!! $section->editorAttributes() !!} class="sticky top-0 z-50 w-full border-b border-border-dark bg-bg-dark/80 backdrop-blur-md">
    <div class="container mx-auto flex h-16 items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-xl font-bold text-white">{{ $section->settings->title }}</span>
        </div>
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-text-body">
            <a href="/" class="hover:text-accent transition-colors">Home</a>
            <a href="/features" class="hover:text-accent transition-colors">Features</a>
            <a href="javascript:void(0)" class="hover:text-accent transition-colors">About</a>
        </nav>
        <div class="flex items-center gap-4">
            <button class="bg-accent hover:bg-accent-hover text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Get Started</button>
        </div>
    </div>
</header>
