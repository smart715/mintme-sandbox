import '../scss/main.sass';
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
import bNavbar from 'bootstrap-vue/es/components/navbar/navbar';
import bNavbarNav from 'bootstrap-vue/es/components/navbar/navbar-nav';
import bNavbarBrand from 'bootstrap-vue/es/components/navbar/navbar-brand';
import bNavbarToggle from 'bootstrap-vue/es/components/navbar/navbar-toggle';
import bNavItem from 'bootstrap-vue/es/components/nav/nav-item';
import bCollapse from 'bootstrap-vue/es/components/collapse/collapse';
window.Vue = require('vue');

Vue.options.delimiters = ['{[', ']}'];

new Vue({
    el: '#navbar',
    components: {
        bDropdown,
        bDropdownItem,
        bNavbar,
        bNavbarNav,
        bNavbarBrand,
        bNavbarToggle,
        bNavItem,
        bCollapse,
    },
});

