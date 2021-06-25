<template>
    <sidebar-menu
        :class="clickedStyles"
        :menu="menu"
        :width="menuWidth"
        @collapse="isClicked = true"
    />
</template>

<script>
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTasks, faAnchor, faCubes, faSignInAlt} from '@fortawesome/free-solid-svg-icons';

library.add(faTasks, faAnchor, faCubes, faSignInAlt);

export default {
    name: 'AdminMenu',
    components: {
        SidebarMenu,
    },
    props: {
        isUserLogged: Boolean,
    },
    data() {
        return {
            isClicked: false,
            testingMenu:
                {
                    title: 'Tester Options',
                    icon: 'fa fa-tasks',
                    child: [
                        {
                            href: this.$routing.generate('hacker-toggle-info-bar'),
                            title: this.$t('hacker_menu.tester_widget'),
                        },
                    ],
                },
            authorizedMenu: [
                {
                    header: true,
                    title: this.$t('hacker_menu.title'),
                },
                {
                    title: this.$t('hacker_menu.permissions.title'),
                    icon: 'fa fa-anchor',
                    child: [
                        {
                            href: this.$routing.generate('hacker-set-role', {role: 'admin'}),
                            title: this.$t('hacker_menu.permissions.admin'),
                        },
                        {
                            href: this.$routing.generate('hacker-set-role', {role: 'user'}),
                            title: this.$t('hacker_menu.permissions.user'),
                        },
                    ],
                },
                {
                    title: this.$t('hacker_menu.crypto.title'),
                    icon: 'fa fa-cubes',
                    child: [
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'web'}),
                            title: this.$t('hacker_menu.crypto.web'),
                        },
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'eth'}),
                            title: this.$t('hacker_menu.crypto.eth'),
                        },
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'btc'}),
                            title: this.$t('hacker_menu.crypto.btc'),
                        },
                        {
                          href: this.$routing.generate('hacker-add-crypto', {crypto: 'usdc'}),
                          title: this.$t('hacker_menu.crypto.usdc'),
                        },
                        {
                            href: this.$routing.generate('hacker-add-crypto', {crypto: 'bnb'}),
                            title: this.$t('hacker_menu.crypto.bnb'),
                        },
                    ],
                },
            ],
            nonAuthorizedMenu: [
                {
                    header: true,
                    title: this.$t('hacker_menu.permissions.title'),
                },
                {
                    title: 'Quick Menu',
                    icon: 'fa fa-sign-in-alt',
                    child: [
                        {
                            href: this.$routing.generate('quick-registration'),
                            title: this.$t('hacker_menu.quick_registration'),
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
            let menu = this.isUserLogged ?
                this.authorizedMenu :
                this.nonAuthorizedMenu;

            menu.push(this.testingMenu);
            return menu;
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

<style lang="scss">
    @import '../../scss/variables';

    .v-sidebar-menu {
        background: $secondary !important;
        z-index: 1040;
    }

    .v-sidebar-menu .vsm-dropdown>.vsm-list {
        background: $primary !important;
    }

    .v-sidebar-menu .vsm-item.first-item>.vsm-link>.vsm-icon {
        background: transparent !important;
    }

    .v-sidebar-menu.vsm-default .vsm-item.first-item.open-item>.vsm-link {
        background: $primary-light !important;
    }

    .v-sidebar-menu.vsm-collapsed {
        background: none !important;

        & > *:not(button) {
            display: none !important;
        }
    }

    .v-sidebar-menu .vsm-arrow:after {
        content: "↓" !important;
    }

    .v-sidebar-menu .collapse-btn:after {
        content: ">" !important;
    }

    .vsm-collapsed {
        width: 10px !important;

        .collapse-btn {
            width: 30px !important;
        }
    }
</style>
