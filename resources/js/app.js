import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { ScrollManager } from './scroll-manager';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const scrollManager = new ScrollManager();

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mixin({
                mounted() {
                    this.$nextTick(() => {
                        const element = this.$el;
                        if (element && element instanceof Element) {
                            scrollManager.scrollRegionsToTop(element);
                        }
                    });
                }
            })
            .mount(el);

    },
    progress: {
        // Progress bar configuration
        color: '#ffffff',
        showSpinner: true,
        delay: 0,
    },
});
