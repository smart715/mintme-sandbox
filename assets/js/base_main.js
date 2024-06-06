import '../scss/main.sass';
import Vue from 'vue';
import {dom, library} from '@fortawesome/fontawesome-svg-core';
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
import {
    faCoins,
    faChartLine,
    faWallet,
    faCubes,
    faQuestion,
    faInfo,
    faDatabase,
    faCheckCircle,
    faPlus,
} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import LocaleSwitcher from './components/LocaleSwitcher';
import {faGithub} from '@fortawesome/fontawesome-free-brands';
import {getScreenMediaSize} from './utils';
import {ScreenMediaSize} from './utils/constants';
import UserIdleModal from './components/modal/UserIdleModal';
import vueTabevents from 'vue-tabevents';
import {OpenPageMixin} from './mixins';
import {VBTooltip} from 'bootstrap-vue';
import Logger from './logger';
import debounce from 'lodash/debounce';

// Async css import
import(/* webpackPreload: true */ '../scss/bootstrap-vue.sass');

window.Vue = Vue;
Vue.use(Routing);
Vue.use(VueI18n);
Vue.use(Helpers);
Vue.use(vueTabevents);
Vue.use(Axios);

Vue.use(Logger);

dom.watch();

library.add(
    faCoins,
    faChartLine,
    faWallet,
    faCubes,
    faQuestion,
    faInfo,
    faDatabase,
    faGithub,
    faCheckCircle,
    faPlus,
);

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

if (document.getElementById('navbar')) {
    new Vue({
        el: '#navbar',
        i18n,
        directives: {
            onClickaway,
            'b-tooltip': VBTooltip,
        },
        mixins: [
            OpenPageMixin,
        ],
        data() {
            return {
                items: [],
                showNavbarMenu: false,
                showProfileMenu: false,
                showTokenDropdown: false,
                showCoinDropdown: false,
                profileDropdownMounted: false,
                tokenDropdownMounted: false,
                showTradingDropdown: false,
                scrollPosition: null,
                isXLScreen: false,
                onScreenResourceDebounce: null,
            };
        },
        components: {
            Avatar,
            UserNotification,
            NavEnvelope,
            FontAwesomeIcon,
            LocaleSwitcher,
            ProfileDropdown: async () => {
                if (document.getElementById('navbar')?.dataset.isLogged) {
                    const data = await import('./components/topnav/ProfileDropdown');

                    return data.default;
                }

                return null;
            },
            NavUserMenu: async () => {
                if (document.getElementById('navbar')?.dataset.isLogged) {
                    const data = await import('./components/topnav/NavUserMenu');

                    return data.default;
                }

                return null;
            },
            NavTokenDropdown: async () => {
                if (document.getElementById('navbar')?.dataset.isLogged) {
                    const data = await import('./components/topnav/NavTokenDropdown');

                    return data.default;
                }

                return null;
            },
            UserIdleModal,
        },
        methods: {
            handleScreenResize: function() {
                this.isXLScreen = window.matchMedia(
                    `screen and (min-width: 1200px)`
                ).matches;

                this.updateMobileNavbarOffset();
            },
            toggleNavbarMenu: function() {
                this.showNavbarMenu = !this.showNavbarMenu;
                document.body.style.overflowY = this.showNavbarMenu ? 'hidden' : 'auto';

                this.updateMobileNavbarOffset();
            },
            updateMobileNavbarOffset() {
                document.querySelector('.navbar-collapse').style.top = this.getNavbarOffset();
                document.querySelector('.navbar-backdrop').style.top = this.getNavbarOffset();
            },
            getNavbarOffset() {
                const navbarBoundaries = document.querySelector('#navbar')?.getBoundingClientRect();

                if (!navbarBoundaries) {
                    return '0';
                }

                return Math.floor(navbarBoundaries.top + navbarBoundaries.height) + 'px';
            },
            toggleProfileMenu: function() {
                this.showProfileMenu = !this.showProfileMenu;
            },
            hideProfileMenu: function() {
                this.showProfileMenu = false;
            },
            toggleTradingDropdown: function() {
                if (getScreenMediaSize() > ScreenMediaSize.LG) {
                    return;
                }

                this.showTradingDropdown = !this.showTradingDropdown;
                this.showCoinDropdown = this.showLangMenu = false;
            },
            toggleTokenDropdown: function() {
                if (getScreenMediaSize() > ScreenMediaSize.LG) {
                    return;
                }

                this.showTokenDropdown = !this.showTokenDropdown;
                this.hideCoinDropdown();
                this.showLangMenu = false;
            },
            toggleCoinDropdown: function() {
                if (getScreenMediaSize() > ScreenMediaSize.LG) {
                    return;
                }

                this.showCoinDropdown = !this.showCoinDropdown;
                this.showTokenDropdown = false;
                this.showLangMenu = false;
            },
            hideCoinDropdown: function() {
                this.showCoinDropdown = false;
            },
            updateScroll: function() {
                this.scrollPosition = window.scrollY;
            },
            onTokenDropdownMount: function() {
                this.tokenDropdownMounted = true;
            },
        },
        created() {
            this.handleScreenResize();
        },
        mounted() {
            this.onScreenResourceDebounce = debounce(this.handleScreenResize, 100);

            window.addEventListener('resize', () => {
                this.onScreenResourceDebounce.cancel();
                this.onScreenResourceDebounce();
            });

            window.addEventListener('scroll', this.updateScroll);
        },
    });
}

if (document.getElementById('login-form')) {
    new Vue({
        i18n,
        el: '#login-form',
        mixins: [
            OpenPageMixin,
        ],
    });
}

if (document.getElementById('api-generator')) {
    new Vue({
        i18n,
        el: '#api-generator',
        mixins: [
            OpenPageMixin,
        ],
    });
}

if (document.querySelector('#global-confirm-modal')) {
    import('./components/modal/GlobalConfirmModal').then((data) => {
        const GlobalConfirmModal = data.default;

        new Vue({
            i18n,
            el: '#global-confirm-modal',
            components: {
                GlobalConfirmModal,
            },
        });
    });
}

if (document.querySelector('#gem-modal')) {
    import('./components/modal/GemModal').then((data) => {
        const GemModal = data.default;

        new Vue({
            i18n,
            el: '#gem-modal',
            components: {
                GemModal,
            },
        });
    });
}
