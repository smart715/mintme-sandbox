import '../../scss/pages/token_settings.sass';
import i18n from '../utils/i18n/i18n';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCheckSquare} from '@fortawesome/free-regular-svg-icons';
import {
    faBullhorn,
    faBullseye,
    faCog,
    faCoins,
    faLongArrowAltLeft,
    faParachuteBox,
    faRocket,
    faStar,
    faStore,
    faTrophy,
} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import TokenSettingsGeneral from '../components/token_settings/TokenSettingsGeneral';
import TokenSettingsAdvanced from '../components/token_settings/TokenSettingsAdvanced';
import TokenSettingsDeploy from '../components/token_settings/TokenSettingsDeploy';
import TokenSettingsMarkets from '../components/token_settings/TokenSettingsMarkets';
import TokenSettingsInit from '../components/token_settings/TokenSettingsInit';
import TokenSettingsPromotion from '../components/token_settings/TokenSettingsPromotion';
import TokenSettingsHeader from '../components/token_settings/TokenSettingsHeader';
import TokenSettingsNav from '../components/token_settings/TokenSettingsNav';
import BalanceInit from '../components/trade/BalanceInit';
import MarketInit from '../components/trade/MarketInit';
import CryptoInit from '../components/CryptoInit';
import store from '../storage';
import {MButton} from '../components/UI';
import {mapGetters} from 'vuex';
import {faDiscord} from '@fortawesome/fontawesome-free-brands';
import {
    TOKEN_SETTINGS_TABS as SETTINGS_TABS,
    TOKEN_SETTINGS_PROMOTION_TABS as PROMOTION_TABS,
} from '../utils/constants';

library.add(
    faCheckSquare,
    faLongArrowAltLeft,
    faCog,
    faBullhorn,
    faRocket,
    faBullseye,
    faParachuteBox,
    faStore,
    faTrophy,
    faDiscord,
    faCoins,
    faStar,
);

import('../storage/modules/token_settings').then((data) => {
    createVueInstance(data.default);
});

/**
 * Init Vue for #token-settings element
 * @param {Object} storeModule
 */
function createVueInstance(storeModule) {
    new Vue({
        el: '#token-settings',
        i18n,
        components: {
            FontAwesomeIcon,
            TokenSettingsGeneral,
            MButton,
            TokenSettingsAdvanced,
            TokenSettingsDeploy,
            TokenSettingsInit,
            TokenSettingsMarkets,
            BalanceInit,
            MarketInit,
            TokenSettingsPromotion,
            TokenSettingsHeader,
            TokenSettingsNav,
            CryptoInit,
        },
        data() {
            const tokenSettingsElement = document.querySelector('#token-settings');

            return {
                tabs: [],
                activeTab: tokenSettingsElement.dataset['activeTab'] || SETTINGS_TABS.general,
                activeSubTab: tokenSettingsElement.dataset['activeSubTab'],
                SETTINGS_TABS,
                sideNavOpened: false,
                marketsFeatureEnabled: 'true' === tokenSettingsElement.dataset['marketsFeatureEnabled'],
                rewardsFeatureEnabled: 'true' === tokenSettingsElement.dataset['rewardsFeatureEnabled'],
            };
        },
        mounted() {
            this.tabs = [
                {
                    icon: ['fas', 'cog'],
                    id: SETTINGS_TABS.general,
                    name: this.$t('page.token_settings.tab.general'),
                    url: this.getTabUrl(SETTINGS_TABS.general),
                },
                {
                    icon: ['fas', 'bullhorn'],
                    id: SETTINGS_TABS.promotion,
                    name: this.$t('page.token_settings.tab.promotion'),
                    url: this.getTabUrl(SETTINGS_TABS.promotion),
                    tabs: [
                        {
                            disabled: !this.rewardsFeatureEnabled,
                            icon: ['fas', 'trophy'],
                            id: 'bounty',
                            name: this.$t('page.token_settings.tab.promotion.tab.bounty'),
                            url: this.getTabUrl(SETTINGS_TABS.promotion, PROMOTION_TABS.bounty),
                        },
                        {
                            disabled: !this.rewardsFeatureEnabled,
                            icon: ['fas', 'store'],
                            id: 'token_shop',
                            name: this.$t('page.token_settings.tab.promotion.tab.token_shop'),
                            url: this.getTabUrl(SETTINGS_TABS.promotion, PROMOTION_TABS.token_shop),
                        },
                        {
                            icon: ['fas', 'parachute-box'],
                            id: 'airdrop',
                            name: this.$t('page.token_settings.tab.promotion.tab.airdrop'),
                            url: this.getTabUrl(SETTINGS_TABS.promotion, PROMOTION_TABS.airdrop),
                        },
                        {
                            icon: ['fab', 'discord'],
                            id: 'discord_rewards',
                            name: this.$t('page.token_settings.tab.promotion.tab.discord'),
                            url: this.getTabUrl(SETTINGS_TABS.promotion, PROMOTION_TABS.discord_rewards),
                        },
                        {
                            icon: ['fas', 'coins'],
                            id: 'signup_bonus',
                            name: this.$t('page.token_settings.tab.promotion.tab.signup_bonus'),
                            url: this.getTabUrl(SETTINGS_TABS.promotion, PROMOTION_TABS.signup_bonus),
                        },
                        {
                            icon: ['fas', 'star'],
                            id: 'token_promotion',
                            name: this.$t('page.token_settings.tab.promotion.tab.token_promotion'),
                            url: this.getTabUrl(SETTINGS_TABS.promotion, PROMOTION_TABS.token_promotion),
                        },
                    ],
                },
                {
                    icon: ['far', 'check-square'],
                    id: SETTINGS_TABS.advanced,
                    name: this.$t('page.token_settings.tab.advanced'),
                    url: this.getTabUrl(SETTINGS_TABS.advanced),
                },
                {
                    disabled: !this.getIsCreatedOnMintmeSite,
                    icon: ['fas', 'rocket'],
                    id: SETTINGS_TABS.deploy,
                    name: this.$t('page.token_settings.tab.deploy'),
                    url: this.getTabUrl(SETTINGS_TABS.deploy),
                },
                {
                    disabled: !this.marketsFeatureEnabled,
                    icon: ['fas', 'bullseye'],
                    id: SETTINGS_TABS.markets,
                    name: this.$t('page.token_settings.tab.markets'),
                    url: this.getTabUrl(SETTINGS_TABS.markets),
                },
            ];

            this.updateActiveTab(true);
        },
        computed: {
            ...mapGetters('tokenSettings', {
                getTokenName: 'getTokenName',
                getIsCreatedOnMintmeSite: 'getIsCreatedOnMintmeSite',
            }),
        },
        methods: {
            onNavTabChange({tab, subTab = null}) {
                this.activeTab = tab;
                this.activeSubTab = subTab;

                this.updateActiveTab();
                this.closeSidenav();

                if (!window.history.replaceState) {
                    return;
                }

                window.history.replaceState(
                    {},
                    '',
                    this.$routing.generate('token_settings', {
                        tokenName: this.getTokenName,
                        tab,
                        sub: subTab,
                    })
                );
            },
            handleTokenDeployEvent: function(crypto) {
                this.$refs['token-settings-init'].handleDeployEvent(crypto);
            },
            getTabUrl(tab, subTab = null) {
                return this.$routing.generate('token_settings', {
                    tab,
                    tokenName: this.getTokenName,
                    sub: subTab,
                });
            },
            updateActiveTab(openSpoilers = false) {
                this.tabs = this.tabs.map((tab) => {
                    tab.active = (this.activeTab === tab.id) && !tab.tabs;

                    if (!tab.tabs) {
                        return tab;
                    }

                    tab.tabs = tab.tabs.map((subTab) => {
                        subTab.active = this.activeSubTab === subTab.id;

                        if (this.activeSubTab !== subTab.id) {
                            return subTab;
                        }

                        tab.active = true;

                        if (openSpoilers) {
                            tab.opened = true;
                        }

                        return subTab;
                    });

                    return tab;
                });
            },
            onOpenSidenav() {
                this.sideNavOpened = true;
                document.body.style.overflowY = 'hidden';

                const navbarBoundaries = document.querySelector('#navbar')?.getBoundingClientRect();

                if (!navbarBoundaries) {
                    return;
                }

                const offset = Math.floor(navbarBoundaries.top + navbarBoundaries.height) + 'px';

                document.querySelector('.settings-nav-wrp').style.marginTop = offset;
            },
            closeSidenav() {
                this.sideNavOpened = false;
                document.body.style.overflowY = 'auto';
            },
        },
        store,
    });
}
