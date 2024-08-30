import { fileURLToPath, URL } from "url";
import { defineConfig } from "vite"
import symfony from "vite-plugin-symfony"
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        symfony(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            input: {
                app: "./assets/js/app.js",
                css: "./assets/css/app.css"
            }
        }
    },
    resolve: {
        alias: [
            { find: '@', replacement: fileURLToPath(new URL('./assets/js', import.meta.url)) },
        ]
    }
});
