import '../css/fonts.css';
import '../css/app.css';
import './bootstrap';
import { ThemeProvider } from "@/Components/theme-provider"
import { Toaster } from "@/Components/ui/sonner"

import { TooltipProvider } from "@/Components/ui/tooltip"

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <ThemeProvider defaultTheme="dark" storageKey="vite-ui-theme">
                <TooltipProvider>
                    <App {...props} />
                    <Toaster />
                </TooltipProvider>
            </ThemeProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
