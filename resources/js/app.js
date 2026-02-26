import './bootstrap';
import '../css/app.css';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

import SimpleBar from 'simplebar';
import hljs from 'highlight.js/lib/core';
import xml from 'highlight.js/lib/languages/xml';
import dayjs from 'dayjs';
import Swiper from 'swiper/bundle';
import Sortable from 'sortablejs';
import ApexCharts from 'apexcharts';
import * as Gridjs from 'gridjs';
import '@caneara/iodine';
import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import Quill from 'quill';
import flatpickr from 'flatpickr';
import Tom from 'tom-select/dist/js/tom-select.complete.min';
import '@fortawesome/fontawesome-free/css/all.css';

import * as helpers from './utils/helpers';
import * as pages from './pages';
import store from './store';
import breakpoints from './utils/breakpoints';
import usePopper from './components/usePopper';
import accordionItem from './components/accordionItem';
import tooltip from './directives/tooltip';
import inputMask from './directives/inputMask';
import notification from './magics/notification';
import clipboard from './magics/clipboard';

hljs.registerLanguage('xml', xml);
hljs.configure({ ignoreUnescapedHTML: true });
FilePond.registerPlugin(FilePondPluginImagePreview);

window.hljs = hljs;
window.dayjs = dayjs;
window.SimpleBar = SimpleBar;
window.Swiper = Swiper;
window.Sortable = Sortable;
window.ApexCharts = ApexCharts;
window.Gridjs = Gridjs;
window.FilePond = FilePond;
window.flatpickr = flatpickr;
window.Quill = Quill;
window.Tom = Tom;
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

breakpoints.init();

// Page-specific modules: add data-module="key" to the page container
const PAGE_MODULES = {
    'provider-registration': () => import('./pages/provider-registration'),
    'recipient-providers': () => import('./pages/recipient-providers'),
};
const moduleName = document.querySelector('[data-module]')?.getAttribute('data-module');
const loadModule = moduleName && PAGE_MODULES[moduleName] ? PAGE_MODULES[moduleName]() : Promise.resolve();

loadModule.then(() => Alpine.start());
