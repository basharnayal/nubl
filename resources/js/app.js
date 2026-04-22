import './bootstrap';
import { initFormSubmitGuard } from './form-submit-guard';
import '../css/app.css';

initFormSubmitGuard();

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

import SimpleBar from 'simplebar';
// import hljs from 'highlight.js/lib/core'; // Unused after full project scan — commented out to reduce bundle safely
// import xml from 'highlight.js/lib/languages/xml'; // Unused after full project scan — commented out to reduce bundle safely
import dayjs from 'dayjs';
// import Swiper from 'swiper/bundle'; // Unused after full project scan — commented out to reduce bundle safely
// import Sortable from 'sortablejs'; // Unused after full project scan — commented out to reduce bundle safely
// import ApexCharts from 'apexcharts'; // Moved to lazy loading — only used in 4 dashboard pages
// import * as Gridjs from 'gridjs'; // Unused after full project scan — commented out to reduce bundle safely
import '@caneara/iodine';
// import * as FilePond from 'filepond'; // Unused after full project scan — commented out to reduce bundle safely
// import FilePondPluginImagePreview from 'filepond-plugin-image-preview'; // Unused after full project scan — commented out to reduce bundle safely
// import Quill from 'quill'; // Unused after full project scan — commented out to reduce bundle safely
// import flatpickr from 'flatpickr'; // Unused after full project scan — commented out to reduce bundle safely
// import Tom from 'tom-select/dist/js/tom-select.complete.min'; // Moved to lazy loading — only used in 3 provider pages
import '@fortawesome/fontawesome-free/css/all.css';

import * as helpers from './utils/helpers';
import * as pages from './pages';
import store from './store';
import breakpoints from './utils/breakpoints';
import usePopper from './components/usePopper';
import accordionItem from './components/accordionItem';
import notificationPanel from './components/notificationPanel';
import tooltip from './directives/tooltip';
import inputMask from './directives/inputMask';
import notification from './magics/notification';
import clipboard from './magics/clipboard';

// hljs.registerLanguage('xml', xml); // Commented out — hljs unused
// hljs.configure({ ignoreUnescapedHTML: true }); // Commented out — hljs unused
// FilePond.registerPlugin(FilePondPluginImagePreview); // Commented out — FilePond unused

// window.hljs = hljs; // Commented out — unused
window.dayjs = dayjs;
window.SimpleBar = SimpleBar;
// window.Swiper = Swiper; // Commented out — unused
// window.Sortable = Sortable; // Commented out — unused
// window.ApexCharts = ApexCharts; // Replaced by lazy loader below
// window.Gridjs = Gridjs; // Commented out — unused
// window.FilePond = FilePond; // Commented out — unused
// window.flatpickr = flatpickr; // Commented out — unused
// window.Quill = Quill; // Commented out — unused
// window.Tom = Tom; // Replaced by lazy loader below

// ── Lazy loaders for page-specific libraries ────────────────────────
window.loadApexCharts = async () => {
    const module = await import('apexcharts');
    return module.default;
};

window.loadTomSelect = async () => {
    const module = await import('tom-select/dist/js/tom-select.complete.min');
    return module.default;
};
window.Alpine = Alpine;
window.helpers = helpers;
window.pages = pages;

Alpine.plugin(persist);
Alpine.plugin(collapse);
Alpine.plugin(intersect);

Alpine.directive('tooltip', tooltip);
Alpine.directive('input-mask', inputMask);
Alpine.magic('notification', () => notification);
Alpine.magic('clipboard', () => clipboard);

Alpine.store('breakpoints', breakpoints);
Alpine.store('global', store());
Alpine.store('sidebarAccordion', { expandedItem: null });

Alpine.data('usePopper', usePopper);
Alpine.data('accordionItem', accordionItem);
Alpine.data('notificationPanel', notificationPanel);

breakpoints.init();

// Page-specific modules: add data-module="key" to the page container
const PAGE_MODULES = {
    'provider-registration': () => import('./pages/provider-registration'),
    'recipient-providers': () => import('./pages/recipient-providers'),
};
const moduleName = document.querySelector('[data-module]')?.getAttribute('data-module');
const loadModule = moduleName && PAGE_MODULES[moduleName] ? PAGE_MODULES[moduleName]() : Promise.resolve();

loadModule.then(() => Alpine.start());
