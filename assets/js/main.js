import '../scss/main.sass';
import bNavItemDropdown
from 'bootstrap-vue/es/components/nav/nav-item-dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
import bNavbar from 'bootstrap-vue/es/components/navbar/navbar';
import bNavbarNav from 'bootstrap-vue/es/components/navbar/navbar-nav';
import bNavbarBrand from 'bootstrap-vue/es/components/navbar/navbar-brand';
import bNavbarToggle from 'bootstrap-vue/es/components/navbar/navbar-toggle';
import bNavItem from 'bootstrap-vue/es/components/nav/nav-item';
import bCollapse from 'bootstrap-vue/es/components/collapse/collapse';
import fontawesome from '@fortawesome/fontawesome';
import fas from '@fortawesome/fontawesome-free-solid';
import fab from '@fortawesome/fontawesome-free-brands';
import far from '@fortawesome/fontawesome-free-regular';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';

fontawesome.library.add(fas, far, fab);

window.Vue = require('vue');
Vue.component('font-awesome-icon', FontAwesomeIcon);
Vue.component('font-awesome-layers', FontAwesomeLayers);

Vue.options.delimiters = ['{[', ']}'];

const imagesContext = require.context(
    '../img',
    false,
    /\.(png|jpg|jpeg|gif|ico|svg)$/
);
imagesContext.keys().forEach(imagesContext);

new Vue({
    el: '#navbar',
    components: {
        bNavItemDropdown,
        bDropdownItem,
        bNavbar,
        bNavbarNav,
        bNavbarBrand,
        bNavbarToggle,
        bNavItem,
        bCollapse,
    },
});

new Vue({
    el: '#footer',
});
