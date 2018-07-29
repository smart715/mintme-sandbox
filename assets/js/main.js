import '../scss/main.sass';
import ClickOutside from 'vue-click-outside';

window.Vue = require('vue');

Vue.options.delimiters = ['{[', ']}'];

new Vue({
    el: '#navbar',
    directives: {
        ClickOutside,
    },
    data: {
        showNavbarMenu: false,
        showProfileMenu: false,
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

