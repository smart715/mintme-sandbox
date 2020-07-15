<template>
    <sidebar-menu
        :class="clickedStyles"
        :menu="menu"
        :width="menuWidth"
        @collapse="isClicked = true"
    />
</template>

<script>
import Vue from 'vue';
import VueSidebarMenu from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';

Vue.use(VueSidebarMenu);

export default {
    name: 'AdminMenu',
    props: {
        isUserLogged: Boolean,
    },
    data() {
        return {
            isClicked: false,
            authorizedMenu: [
                {
                    header: true,
                    title: 'HACKER MENU',
                },
                {
                    title: 'Permissions',
                    icon: 'fa fa-anchor',
                    child: [
                        {
                            href: this.$routing.generate('hacker-set-role', {role: 'admin'}),
                            title: 'Make me Admin',
                        },
                        {
                            href: this.$routing.generate('hacker-set-role', {role: 'user'}),
                            title: 'Make me User',
                        },
                    ],
                },
                {
                    title: 'Crypto',
                    icon: 'fa fa-cubes',
                    child: [
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'web'}),
                            title: 'Add 100 MINTME',
                        },
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'eth'}),
                            title: 'Add 0.05 ETH',
                        },
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'btc'}),
                            title: 'Add 0.001 BTC',
                        },
                    ],
                },
            ],
            nonAuthorizedMenu: [
                {
                    header: true,
                    title: 'HACKER MENU',
                },
                {
                    title: 'Quick Menu',
                    icon: 'fa fa-sign-in-alt',
                    child: [
                        {
                            href: this.$routing.generate('quick-registration'),
                            title: 'Quick registration',
                        },
                    ],
                },
            ],
        };
    },
    computed: {
        clickedStyles: function() {
            return !this.isClicked ? 'v-sidebar-menu vsm-collapsed' : '';
        },
        menu: function() {
            return this.isUserLogged ? this.authorizedMenu : this.nonAuthorizedMenu;
        },
        menuWidth: function() {
            return this.isClicked ? '350px' : '30px';
        },
    },
    watch: {
        isClicked: function() {
            let collapseBtn = document.querySelector('.v-sidebar-menu button.collapse-btn');
            collapseBtn.click();
        },
    },
};
</script>

<style lang="sass">
    @import '../../scss/variables'

    .v-sidebar-menu
        background: $secondary !important

    .v-sidebar-menu .vsm-dropdown>.vsm-list
        background: $primary !important

    .v-sidebar-menu .vsm-item.first-item>.vsm-link>.vsm-icon
        background: transparent !important

    .v-sidebar-menu.vsm-default .vsm-item.first-item.open-item>.vsm-link
        background: $primary-light !important

    .v-sidebar-menu.vsm-collapsed
        background: none !important

        & > *:not(button)
            display: none !important

    .v-sidebar-menu .vsm-arrow:after
        content: "↓" !important

    .v-sidebar-menu .collapse-btn:after
        content: ">" !important

    .vsm-collapsed
        width: 30px !important
</style>
