import './bootstrap';
import '../css/app.css';

import Alpine from 'alpinejs';
window.Alpine = Alpine;


// في حال اردت وضع ملف js لصفحة معينة فقط يمكنك استخدام data-module="key" في ال Blade
// Page-specific modules: add data-module="key" to the page container
// To add a new page: add entry here + data-module="key" in the Blade
const PAGE_MODULES = {
    // فقط اضف الملف هنا واستخدم data-module="key" في ال Blade
    'provider-registration': () => import('./provider-registration'),
};
const moduleName = document.querySelector('[data-module]')?.getAttribute('data-module');
const loadModule = moduleName && PAGE_MODULES[moduleName] ? PAGE_MODULES[moduleName]() : Promise.resolve();
// End Page-specific modules 
loadModule.then(() => Alpine.start());

// Flowbite initialization
import { initFlowbite } from 'flowbite';
initFlowbite();
