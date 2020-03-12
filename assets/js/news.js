import Vue from 'vue';
import fontawesome from '@fortawesome/fontawesome';
import fab from '@fortawesome/fontawesome-free-brands';
import far from '@fortawesome/fontawesome-free-regular';
import {faSearch, faCog, fas} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';

fontawesome.library.add(fas, far, fab, faSearch, faCog);

Vue.component('font-awesome-icon', FontAwesomeIcon);
Vue.component('font-awesome-layers', FontAwesomeLayers);

new Vue({
    el: '#news',
    components: {
        FontAwesomeIcon,
        FontAwesomeLayers,
    },
});
