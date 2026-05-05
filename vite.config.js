import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        // Expose the server to the Docker network
        host: '0.0.0.0', 
        port: 5173,
        // Strict port prevents Vite from jumping to 5174 if 5173 is "busy"
        strictPort: true, 
        hmr: {
            // This is the address your BROWSER uses to connect to the HMR websocket
            host: 'localhost', 
        },
        watch: {
            // Essential for Docker on Windows/WSL: 
            // Standard file system events (Inotify) often don't propagate 
            // through the Docker volume mount. Polling fixes this.
            usePolling: true,
            interval: 100,
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});