import '../scss/main.sass';
import VueBootstrap from 'bootstrap-vue';
import fontawesome from '@fortawesome/fontawesome';
import fas from '@fortawesome/fontawesome-free-solid';
import fab from '@fortawesome/fontawesome-free-brands';
import far from '@fortawesome/fontawesome-free-regular';
import {faSearch, faCog} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import VueClipboard from 'vue-clipboard2';
import VueTippy from 'vue-tippy';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import Axios from './axios';
import Routing from './routing';
import TokenSearcher from '../components/token/TokenSearcher';

VueClipboard.config.autoSetContainer = true;

fontawesome.library.add(fas, far, fab, faSearch, faCog);

window.Vue = require('vue');

Vue.component('font-awesome-icon', FontAwesomeIcon);
Vue.component('font-awesome-layers', FontAwesomeLayers);

Vue.use(Axios);
Vue.use(Routing);
Vue.use(VueBootstrap);
Vue.use(VueClipboard);
Vue.use(VueTippy);
Vue.use(Vuelidate);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

Vue.options.delimiters = ['{[', ']}'];

const imagesContext = require.context(
    '../img',
    false,
    /\.(png|jpg|jpeg|gif|ico|svg)$/
);
imagesContext.keys().forEach(imagesContext);

new Vue({
    el: '#navbar',
    data() {
        return {
            items: [],
        };
    },
    components: {
        TokenSearcher,
    },
});

new Vue({
    el: '#footer',
});
