<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Page Builder Editor - {{ config('app.name') }}</title>

    <!-- Page Builder Assets -->
    {{ PageBuilder\PageBuilder::css() }}
</head>

<body class="bg-primary-50">
    <div id="editor"></div>

    <!-- Page Builder Scripts -->
    {{ PageBuilder\PageBuilder::js() }}

    <script type="module">
        const config = @json($config);

        const editor = PageBuilder.init({
            container: '#editor',
            ...config,
        });

        // Listen for editor exit event, when using the editor in an iframe
        editor.onExit(() => {
            window.parent.postMessage({
                type: 'pagebuilder:exit'
            }, '*');

            // remove all query params except preserved_params
            const url = new URL(window.location.href);
            const preserved = config.preservedParams || [];
            const newParams = new URLSearchParams();

            url.searchParams.forEach((value, key) => {
                if (preserved.includes(key)) {
                    newParams.set(key, value);
                }
            });

            url.search = newParams.toString();
            window.location.href = url.toString();
        });

        // Listen for page change event, when using the editor in an iframe
        editor.onPageChange(({
            slug
        }) => {
            window.parent.postMessage({
                type: 'pagebuilder:page-change',
                slug
            }, '*');
        });
    </script>
</body>

</html>
