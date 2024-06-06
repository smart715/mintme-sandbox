import {VBToggle, BCollapse} from 'bootstrap-vue';
import '../../scss/pages/coin_start.sass';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faAngleDown} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(
    faAngleDown,
);

new Vue({
    el: '#coin_start',
    directives: {
        'b-toggle': VBToggle,
    },
    components: {
        BCollapse,
        FontAwesomeIcon,
    },
});
