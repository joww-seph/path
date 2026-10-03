import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import { VitePWA } from 'vite-plugin-pwa';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
                'resources/css/home.css',
                'resources/js/home.js',
                'resources/js/offline.ts',
            ],
            refresh: true,
            fonts: [
                google('Figtree', {
                    weights: [400, 500, 600, 700],
                }),
                google('Young Serif', {
                    weights: [400],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        // Installable PWA. The service worker and manifest are written to public/ so they cover
        // the whole site; the precache lists the built assets under /build.
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.ts',
            outDir: 'public',
            scope: '/',
            base: '/',
            buildBase: '/build/',
            injectRegister: false,
            includeAssets: [],
            injectManifest: {
                globDirectory: 'public/build',
                globPatterns: ['**/*.{js,css,woff2,svg,png,webp}'],
                modifyURLPrefix: { '': '/build/' },
                maximumFileSizeToCacheInBytes: 3 * 1024 * 1024,
                additionalManifestEntries: [
                    { url: '/offline', revision: String(Date.now()) },
                    { url: '/icons/icon-192.png', revision: '1' },
                    { url: '/manifest.webmanifest', revision: '1' },
                ],
            },
            // The web manifest is a static file: public/manifest.webmanifest.
            manifest: false,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            '.claude/**',
            'public/**',
            'resources/data/**',
            'CLAUDE.md',
            'AGENTS.md',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
