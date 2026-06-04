import { createInertiaApp } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Calendar,
    Check,
    ChevronDown,
    ChevronFirst,
    ChevronLast,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    ChevronUp,
    Circle,
    CircleCheck,
    CircleDot,
    CircleX,
    Info,
    LoaderCircle,
    Menu,
    Minus,
    Paperclip,
    Pencil,
    Pipette,
    Plus,
    Square,
    SquareCheck,
    SquareMinus,
    Star,
    StarHalf,
    TriangleAlert,
    Upload,
    X,
} from '@lucide/vue';

import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
// Vuetify
import { createVuetify } from 'vuetify';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import '../css/app.css';

import './bootstrap';
import 'vuetify/styles';

// Vuetify renders its own internal icons (select chevrons, clearable ✕,
// checkboxes, pagination arrows, alerts, …) through these aliases. Map them to
// lucide components so we can drop the 1.3 MB @mdi/font webfont entirely; the
// `.v-icon svg { width:1em }` rule in app.css scales them to the icon size.
const aliases = {
    complete: Check,
    cancel: CircleX,
    close: X,
    delete: X,
    clear: CircleX,
    success: CircleCheck,
    info: Info,
    warning: TriangleAlert,
    error: CircleX,
    prev: ChevronLeft,
    next: ChevronRight,
    checkboxOn: SquareCheck,
    checkboxOff: Square,
    checkboxIndeterminate: SquareMinus,
    delimiter: Circle,
    sortAsc: ArrowUp,
    sortDesc: ArrowDown,
    expand: ChevronDown,
    menu: Menu,
    subgroup: ChevronDown,
    dropdown: ChevronDown,
    radioOn: CircleDot,
    radioOff: Circle,
    edit: Pencil,
    ratingEmpty: Star,
    ratingFull: Star,
    ratingHalf: StarHalf,
    loading: LoaderCircle,
    first: ChevronFirst,
    last: ChevronLast,
    unfold: ChevronsUpDown,
    file: Paperclip,
    plus: Plus,
    minus: Minus,
    calendar: Calendar,
    collapse: ChevronUp,
    eyeDropper: Pipette,
    upload: Upload,
    color: Circle,
};

const lucideIcons = {
    component: props => h(props.icon, { width: '1em', height: '1em' }),
};

const vuetify = createVuetify({
    icons: {
        defaultSet: 'lucide',
        aliases,
        sets: { lucide: lucideIcons },
    },
    theme: {
        defaultTheme: 'light',
        themes: {
            light: {
                colors: {
                    'primary': '#009cf1',
                    'secondary': '#009cf1',
                    'background': '#F8FAFC',
                    'surface': '#FFFFFF',
                    'success': '#16a34a',
                    'error': '#dc2626',
                    'warning': '#f59e0b',
                    'info': '#009cf1',
                    'indigo': '#009cf1',
                    'indigo-lighten-5': '#e0f2fe',
                    'indigo-darken-3': '#0284c7',
                    'indigo-darken-4': '#009cf1',
                    'indigo-accent-2': '#009cf1',
                },
            },
        },
    },
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: title => `${title} - ${appName}`,
    resolve: name =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
