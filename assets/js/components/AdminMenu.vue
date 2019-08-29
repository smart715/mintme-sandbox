<template>
    <sidebar-menu
            @collapse="isClicked = true"
            :class="clickedStyles"
            :width="!isClicked ? '30px' : '350px'"
            :menu="menu" />
</template>

<script>
import Vue from 'vue';
import VueSidebarMenu from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';

Vue.use(VueSidebarMenu);

export default {
    name: 'AdminMenu',
    data() {
        return {
            isClicked: false,
            menu: [
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
                            title: 'Add 100 WEBs',
                        },
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'btc'}),
                            title: 'Add 100 BTCs',
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
