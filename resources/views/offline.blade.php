<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#222d60">
        <meta name="robots" content="noindex">
        <title>Saved trips · PaTH</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="manifest" href="/manifest.webmanifest">

        <script>
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/offline.ts'])
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        <div id="offline-app">
            <noscript>PaTH needs JavaScript to show your saved trips.</noscript>
        </div>
    </body>
</html>
