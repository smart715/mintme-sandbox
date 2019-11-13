import '../scss/main.sass';
import Vue from 'vue';
import VueBootstrap from 'bootstrap-vue';
import fontawesome from '@fortawesome/fontawesome';
import fab from '@fortawesome/fontawesome-free-brands';
import far from '@fortawesome/fontawesome-free-regular';
import {faSearch, faCog, fas} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import VueClipboard from 'vue-clipboard2';
import VueTippy from 'vue-tippy';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import Axios from './axios';
import Routing from './routing';
import TokenSearcher from './components/token/TokenSearcher';
import AdminMenu from './components/AdminMenu';
import {directive as onClickaway} from 'vue-clickaway';

/*
    To enable passive listeners,
    look https://developers.google.com/web/tools/lighthouse/audits/passive-event-listeners
    Temporary disabled due the chart conflicts
*/
// import 'default-passive-events';

VueClipboard.config.autoSetContainer = true;

fontawesome.library.add(fas, far, fab, faSearch, faCog);

window.Vue = Vue;

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
    directives: {
        onClickaway,
    },
    data() {
        return {
            items: [],
            showNavbarMenu: false,
            showProfileMenu: false,
        };
    },
    components: {
        TokenSearcher,
        AdminMenu,
    },
    methods: {
        toggleNavbarMenu: function() {
            this.showNavbarMenu = !this.showNavbarMenu;
        },
        toggleProfileMenu: function() {
            this.showProfileMenu = !this.showProfileMenu;
        },
        hideProfileMenu: function() {
            this.showProfileMenu = false;
        },
    },
});

new Vue({
    el: '#footer',
    components: {
        FontAwesomeIcon,
        FontAwesomeLayers,
    },
});
