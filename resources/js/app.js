import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp, Link, Head } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "ziggy-js";
import { createPinia } from "pinia";
import { registerSW } from "virtual:pwa-register";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/**/*.vue"),
    ),
  setup({ el, App, props, plugin }) {
    const pinia = createPinia();
    const vueApp = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia);

    if (typeof Ziggy !== "undefined") {
      vueApp.use(ZiggyVue, Ziggy);
    } else {
      vueApp.use(ZiggyVue);
    }

    vueApp.config.globalProperties.__ = (key) => key;

    vueApp.component("Link", Link).component("Head", Head).mount(el);
  },
  progress: {
    color: "#16a34a",
  },
});

registerSW({ immediate: true });
