import '../scss/main.sass';
import Vue from 'vue';
import VueBootstrap from 'bootstrap-vue';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import {faCog, fas, faSearch, faEnvelope} from '@fortawesome/free-solid-svg-icons';
import {library, dom} from '@fortawesome/fontawesome-svg-core';
import {far} from '@fortawesome/free-regular-svg-icons';
import {fab} from '@fortawesome/free-brands-svg-icons';
import TokenSearcher from './components/token/TokenSearcher';
import {directive as onClickaway} from 'vue-clickaway';
import Toasted from 'vue-toasted';
import Notification from './components/Notification';
import AdminMenu from './components/AdminMenu';
import InfoBar from './components/InfoBar';
import Routing from './routing';
import Axios from './axios';
import Avatar from './components/Avatar';
import VueI18n from 'vue-i18n';
import CustomFormatter from './utils/i18n/custom-formatter';
import UserNotification from './components/UserNotification';
import NavEnvelope from './components/chat/NavEnvelope';
import Helpers from './helpers';

window.Vue = Vue;
Vue.use(VueBootstrap);
Vue.use(Routing);
Vue.use(Axios);
Vue.use(VueI18n);
Vue.use(Helpers);

Vue.component('font-awesome-icon', FontAwesomeIcon);
Vue.component('font-awesome-layers', FontAwesomeLayers);
library.add(fas, far, fab, faSearch, faCog, faEnvelope);
dom.watch();

const i18n = new VueI18n({
    locale: 'locale',
    formatter: new CustomFormatter(),
    messages: {
        'locale': window.translations,
    },
});

if (document.getElementById('info-bar')) {
    new Vue({
        el: '#info-bar',
        i18n,
        components: {
            InfoBar,
        },
    });
}

Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
    className: 'toast',
    iconPack: 'custom-class',
});

if (document.getElementById('notifications')) {
    new Vue({
        el: '#notifications',
        i18n,
        components: {
            Notification,
        },
    });
}

new Vue({
    el: '#navbar',
    i18n,
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
        UserNotification,
        NavEnvelope,
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

