@extends('layouts.page', [
    'title' => 'Custom Blade',
])

@layout([
    'header' => [
        'sections' => [
            'header' => [
                'settings' => [
                    'sticky' => false,
                ],
            ],
        ],
    ],
])

@section('content')
    <main class="h-100">
        <p>Custom blade page body</p>
    </main>
@endsection
