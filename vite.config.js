import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import purgecss from '@fullhuman/postcss-purgecss';

export default defineConfig(({ command }) => {
    const isProduction = command === 'build';

    return {
        plugins: [
            laravel({
                input: [
                    'resources/scss/app.scss',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
        ],
        resolve: {
            alias: {
                '~bootstrap': 'bootstrap',
            }
        },
        css: {
            postcss: {
                plugins: [
                    ...(isProduction ? [
                        purgecss({
                            content: [
                                './resources/views/**/*.blade.php',
                                './app/Livewire/**/*.php',
                                './*.php',
                            ],
                            defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
                            safelist: {
                                standard: [
                                    /fade/, /show/, /modal/, /collaps/, /navbar/, /dropdown/, /active/, 
                                    /text-/, /bg-/, /border-/, /p-/, /m-/, /col-/, /d-/, /align-/, /justify-/
                                ]
                            }
                        })
                    ] : [])
                ]
            }
        }
    };
});
