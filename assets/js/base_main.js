import '../scss/main.sass';
import Vue from 'vue';
import {dom} from '@fortawesome/fontawesome-svg-core';
import TokenSearcher from './components/token/TokenSearcher';
import {directive as onClickaway} from 'vue-clickaway';
import Toasted from 'vue-toasted';
import Notification from './components/Notification';
import Routing from './routing';
import Axios from './axios';
import Avatar from './components/Avatar';
import VueI18n from 'vue-i18n';
import CustomFormatter from './utils/i18n/custom-formatter';
import UserNotification from './components/UserNotification';
import NavEnvelope from './components/chat/NavEnvelope';
import Helpers from './helpers';

// Async css import
import(/* webpackPreload: true */ '../scss/bootstrap-vue.sass');

window.Vue = Vue;
Vue.use(Routing);
Vue.use(Axios);
Vue.use(VueI18n);
Vue.use(Helpers);

dom.watch();

const i18n = new VueI18n({
    locale: 'locale',
    formatter: new CustomFormatter(),
    messages: {
        'locale': window.translations,
    },
});

if (document.getElementById('info-bar')) {
    import('./components/InfoBar').then((data) => {
        const InfoBar = data.default;

        new Vue({
            el: '#info-bar',
            i18n,
            components: {
                InfoBar,
            },
        });
    });
}

if (document.getElementById('admin-menu')) {
    import('./components/AdminMenu').then((data) => {
        const AdminMenu = data.default;

        new Vue({
            el: '#admin-menu',
            i18n,
            components: {
                AdminMenu,
            },
        });
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

