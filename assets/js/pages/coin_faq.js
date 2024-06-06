import {VBToggle, BCollapse} from 'bootstrap-vue';
import '../../scss/pages/coin_faq.sass';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faAngleDown} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(
    faAngleDown,
);

new Vue({
    el: '#coin_faq',
    directives: {
        'b-toggle': VBToggle,
    },
    components: {
        BCollapse,
        FontAwesomeIcon,
    },
});
