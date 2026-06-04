import { config } from '@vue/test-utils';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

// jsdom lacks these APIs that Vuetify touches at mount time.
globalThis.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
};

if (!window.matchMedia) {
    window.matchMedia = () => ({
        matches: false,
        addEventListener() {},
        removeEventListener() {},
        addListener() {},
        removeListener() {},
        dispatchEvent() {},
    });
}

// Vuetify's VOverlay location strategy reads the global visualViewport.
if (!window.visualViewport) {
    window.visualViewport = {
        width: 1024,
        height: 768,
        offsetLeft: 0,
        offsetTop: 0,
        scale: 1,
        addEventListener() {},
        removeEventListener() {},
    };
}

// Ziggy exposes a global route() helper that components call directly.
globalThis.route = (name, params) => {
    const id = params && typeof params === 'object' ? Object.values(params)[0] : params;
    return id === undefined || id === null ? `/${name}` : `/${name}/${id}`;
};

const vuetify = createVuetify({ components, directives });

config.global.plugins = [vuetify];

// Stub VDialog to render its content inline (no overlay/teleport/viewport),
// so dialog buttons are queryable in the wrapper during component tests.
config.global.stubs = {
    VDialog: {
        props: { modelValue: { type: Boolean, default: false } },
        template: '<div v-if="modelValue"><slot /></div>',
    },
};
