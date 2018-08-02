import '../scss/main.sass';
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
window.Vue = require('vue');

Vue.options.delimiters = ['{[', ']}'];

new Vue({
    el: '#navbar',
    components: {
        bDropdown,
        bDropdownItem,
    },
});

