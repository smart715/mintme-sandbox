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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faSearch, faCog} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faSearch, faCog);
window.Vue = require('vue');

Vue.options.delimiters = ['{[', ']}'];

/**
 * Set description width equal to title.
 */
function setHomepageDescriptionWidth() {
    let homeShowcaseTitle =
        document.querySelector('.homepage .top-showcase .title');
    if (homeShowcaseTitle) {
        let titleWidth = homeShowcaseTitle.offsetWidth;
        document
            .querySelector('.homepage .top-showcase .description')
            .style.maxWidth = titleWidth + 'px';
    }
}

setHomepageDescriptionWidth();
window.onresize = function() {
    setHomepageDescriptionWidth();
};

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
        FontAwesomeIcon,
    },
});
