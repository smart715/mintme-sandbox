import '../scss/main.sass';
import Vue from 'vue';
import VueBootstrap from 'bootstrap-vue';
import {library, dom} from '@fortawesome/fontawesome-svg-core';
import {fab} from '@fortawesome/free-brands-svg-icons';
import {far} from '@fortawesome/free-regular-svg-icons';
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
import Avatar from './components/Avatar';
import {directive as onClickaway} from 'vue-clickaway';
import Notification from './components/Notification';
import sanitizeHtml from './sanitize_html';
import InfoBar from './components/InfoBar';

/*
    To enable passive listeners,
    look https://developers.google.com/web/tools/lighthouse/audits/passive-event-listeners
    Temporary disabled due the chart conflicts
*/
// import 'default-passive-events';

VueClipboard.config.autoSetContainer = true;

library.add(fas, far, fab, faSearch, faCog);

dom.watch();

window.Vue = Vue;

Vue.component('font-awesome-icon', FontAwesomeIcon);
Vue.component('font-awesome-layers', FontAwesomeLayers);

Vue.use(Axios);
Vue.use(Routing);
Vue.use(VueBootstrap);
Vue.use(VueClipboard);
Vue.use(VueTippy, {
    directive: 'tippy',
    flipDuration: 0,
    popperOptions: {
        modifiers: {
            preventOverflow: {
                boundariesElement: 'window',
            },
        },
    },
});
Vue.use(Vuelidate);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
    className: 'toast',
    iconPack: 'custom-class',
});
Vue.use(sanitizeHtml);

Vue.options.delimiters = ['{[', ']}'];

const imagesContext = require.context(
    '../img',
    false,
    /\.(png|jpg|jpeg|gif|ico|svg)$/
);
imagesContext.keys().forEach(imagesContext);

new Vue({
    el: '#info-bar',
    components: {
        InfoBar,
    },
});

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
        Avatar,
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

if (document.getElementById('notifications')) {
    new Vue({
        el: '#notifications',
        components: {
            Notification,
        },
    });
}

new Vue({
    el: '#footer',
    components: {
        FontAwesomeIcon,
        FontAwesomeLayers,
    },
});
