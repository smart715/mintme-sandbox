<template>
    <sidebar-menu
        :class="clickedStyles"
        :menu="menu"
        :width="menuWidth"
        @collapse="isClicked = true"
    />
</template>

<script>
import '../../scss/pages/admin_menu.sass';
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTasks, faAnchor, faCubes, faSignInAlt} from '@fortawesome/free-solid-svg-icons';
import {RebrandingFilterMixin} from '../mixins';

library.add(faTasks, faAnchor, faCubes, faSignInAlt);

export default {
    name: 'AdminMenu',
    mixins: [RebrandingFilterMixin],
    components: {
        SidebarMenu,
    },
    props: {
        isUserLogged: Boolean,
    },
    mounted() {
        if (this.isUserLogged) {
            this.loadBalance();
        }
    },
    data() {
        return {
            isClicked: false,
            cryptos: [],
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
            const menu = this.isUserLogged ?
                this.authorizedMenu :
                this.nonAuthorizedMenu;

            if (this.isUserLogged) {
                menu[2].child = Object.values(this.cryptos).reduce((acc, crypto) => {
                    acc.push({
                        title: `Add ${this.rebrandingFunc(crypto.identifier)}`,
                        child: [0.001, 0.1, 1, 5, 100].reduce((acc, amount) => {
                            acc.push({
                                href: this.$routing.generate('hacker-add-crypto', {crypto: crypto.identifier, amount}),
                                title: `${amount} ${this.rebrandingFunc(crypto.identifier)}`,
                            });

                            return acc;
                        }, []),
                    });

                    return acc;
                }, []);
            }

            return [...menu, this.testingMenu];
        },
        menuWidth: function() {
            return this.isClicked ? '350px' : '30px';
        },
    },
    methods: {
        loadBalance: async function() {
            const response = await this.$axios.retry.get(this.$routing.generate('tokens'));
            this.cryptos = response.data.predefined;
        },
    },
    watch: {
        isClicked: function() {
            const collapseBtn = document.querySelector('.v-sidebar-menu button.collapse-btn');

            if (collapseBtn) {
                collapseBtn.click();
            }
        },
    },
};
</script>
