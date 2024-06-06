import '../../scss/pages/pair.sass';
import '../../scss/pages/voting.sass';
import '../../scss/pages/dw_modal.sass';
import BalanceInit from '../components/trade/BalanceInit';
import MarketInit from '../components/trade/MarketInit';
import QuickTrade from '../components/QuickTrade';
import PostsInit from '../components/posts/PostsInit';
import TokenPosts from '../components/token/TokenPosts';
import TokenGeneralInformation from '../components/token/TokenGeneralInformation';
import TokenIntroductionDescription from '../components/token/introduction/TokenIntroductionDescription';
import TokenSocialMediaIcons from '../components/token/TokenSocialMediaIcons';
import TokenShare from '../components/token/TokenShare';
import TokenAvatar from '../components/token/TokenAvatar';
import TopHolders from '../components/trade/TopHolders';
import BountiesAndRewards from '../components/bountiesAndRewards/BountiesAndRewards';
import TokenCoverImage from '../components/token/TokenCoverImage';
import {NotificationMixin, RebrandingFilterMixin, StringMixin} from '../mixins/';
import Trade from '../components/trade/Trade';
import {
    tabs,
    MEDIA_BREAKPOINTS,
    MINTME,
    TOKEN_NAME_TRUNCATE_LENGTH,
    ScreenMediaSize,
    WEB,
    logoWithText,
} from '../utils/constants';
import {getScreenMediaSize} from '../utils';
import {mapGetters, mapMutations} from 'vuex';
import {VBTooltip} from 'bootstrap-vue';
import Avatar from '../components/Avatar';
import TokenDirectMessage from '../components/chat/TokenDirectMessage';
import i18n from '../utils/i18n/i18n';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faAngleUp, faAngleDown, faCog, faUser, faCaretDown} from '@fortawesome/free-solid-svg-icons';
import {faPlusSquare, faThumbsUp} from '@fortawesome/free-regular-svg-icons';
import {faDiscord, faTelegramPlane, faFacebookF, faYoutube} from '@fortawesome/fontawesome-free-brands';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import ImageUploader from '../components/ImageUploader';
import TokenReleaseChart from '../components/token/TokenReleaseChart';
import TokenPriceOverviewChart from '../components/token/TokenPriceOverviewChart';
import TokenExchangePrice from '../components/token/TokenExchangePrice';
import {MButton} from '../components/UI';
import {initializeApp} from 'firebase/app';
import {
    TwitterAuthProvider,
    getAuth,
    signInWithPopup,
} from 'firebase/auth';
import FetchableCounter from '../components/FetchableCounter';
import TokenContractAddresses from '../components/token/TokenContractAddresses';
import TokenName from '../components/token/TokenName';
import StickySidebar from '../utils/sticky-sidebar';
import TokenSinglePostPage from '../components/token/TokenSinglePostPage';
import TokenFollowButton from '../components/token/TokenFollowButton';
import TruncateFilterMixin from '../mixins/filters/truncate';
import CryptoInit from '../components/CryptoInit';

library.add(
    faAngleUp,
    faAngleDown,
    faCog,
    faTelegramPlane,
    faDiscord,
    faFacebookF,
    faUser,
    faYoutube,
    faPlusSquare,
    faThumbsUp,
    faCaretDown,
);

new Vue({
    el: '#token',
    components: {
        FontAwesomeIcon,
        Avatar,
        TokenDirectMessage,
        BalanceInit,
        MarketInit,
        PostsInit,
        QuickTrade,
        TokenPosts,
        TokenAvatar,
        TokenCreatedModal: () => import('../components/modal/TokenCreatedModal').then((data) => data.default),
        TokenDeployedModal: () => import('../components/modal/TokenDeployedModal').then((data) => data.default),
        TokenIntroductionDescription,
        TokenGeneralInformation,
        TokenCoverImage,
        TokenOngoingAirdropCampaign: () => import('../components/token/airdrop_campaign/TokenOngoingAirdropCampaign')
            .then((data) => data.default),
        TokenSocialMediaIcons,
        TokenShare,
        TopHolders,
        Trade,
        TokenVotingWidget: () => import('../components/token/TokenVotingWidget').then((data) => data.default),
        BountiesAndRewards,
        ImageUploader,
        TokenReleaseChart,
        MButton,
        TokenPriceOverviewChart,
        TokenExchangePrice,
        FetchableCounter,
        TokenContractAddresses,
        TokenName,
        TokenSinglePostPage,
        TokenFollowButton,
        CryptoInit,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        NotificationMixin,
        RebrandingFilterMixin,
        StringMixin,
        TruncateFilterMixin,
    ],
    i18n,
    data() {
        return {
            tabIndex: 0,
            tokenDescription: null,
            editingName: false,
            tokenName: null,
            cryptoSymbol: null,
            deployInterval: null,
            postFromUrl: null,
            showCreatedModal: true,
            singlePost: null,
            comments: null,
            showDeployedOnBoard: null,
            shouldUnfoldDescription: true,
            isMobileScreen: null,
            isWideScreen: null,
            isAirdropComponentLoaded: false,
            votingWidgetLoaded: false,
            currentTokenAvatar: null,
            timeOutStickySidebar: null,
            activeTab: tabs.intro,
            activeVotingTab: tabs.voting,
            tabsWithSidebar: [],
            initialized: false,
            ownDeployedTokens: false,
            scrollPosition: null,
            maxLengthToTruncate: TOKEN_NAME_TRUNCATE_LENGTH,
            logoWithText: require(`../../img/${logoWithText.icon}`),
            logoWithoutText: require(`../../img/${WEB.icon}`),
            bounties: [],
            rewards: [],
            statisticsOpened: false,
        };
    },
    created: function() {
        const navLinks = document.querySelectorAll('.token-nav .navbar-nav a');

        if (navLinks) { // remove static class from nav links
            navLinks.forEach((el) => el.classList.remove('active'));
        }
    },
    beforeMount: function() {
        this.checkScreenSize();

        window.addEventListener('resize', () => {
            this.checkScreenSize();
            clearTimeout(this.timeOutStickySidebar);
            this.startTimeOutStickySidebar();
        });

        this.tabsWithSidebar = JSON.parse(document.getElementById('tabs-with-sidebar').value) || [];

        const tab = document.getElementById('tab-name').value;
        const isVotingSubTab = [tabs.create_voting, tabs.show_voting].includes(tab);

        this.activeVotingTab = isVotingSubTab ? tab : tabs.voting;
        this.activeTab = isVotingSubTab ? tabs.voting : tab;

        const postData = document.getElementById('posts-data')?.value;
        this.posts = postData ? JSON.parse(postData) : [];
        this.setPosts(this.posts);

        const rewardsData = document.getElementById('rewards-data')?.value;
        this.rewards = rewardsData ? JSON.parse(rewardsData) : [];

        const bountiesData = document.getElementById('bounties-data')?.value;
        this.bounties = bountiesData ? JSON.parse(bountiesData) : [];
    },
    mounted() {
        this.postFromUrl = (/(?:posts#)(\d+)/g.exec(window.location.href) || [])[1] || null;

        if (null !== this.postFromUrl) {
            // Prevent browser from restoring previous scroll height (if page was raloaded)
            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'manual';
            }
            document.getElementById(this.postFromUrl).scrollIntoView();
        }

        const aux = this.$refs['tokenAvatar'];

        if (aux && aux.$attrs['showsuccess']) {
            this.notifySuccess(this.$t('page.pair.token_created'));
        }

        this.initFireBase();
        this.initTwitterAuthPopUp();

        if (window.location.href.includes('saveSuccess=true')) {
            this.showRedirectMessage();
        }

        this.initStickySidebar();

        this.initialized = true;

        this.setOwnDeployedTokens(this.ownDeployedTokens);
        window.addEventListener('scroll', this.updateScroll);
    },
    computed: {
        ...mapGetters('market', {
            currentMarket: 'getCurrentMarket',
        }),
        ...mapGetters('tradeBalance', [
            'getQuoteBalance',
        ]),
        ...mapGetters('voting', {
            currentVoting: 'getCurrentVoting',
        }),
        tabHasSidebar() {
            return this.tabsWithSidebar.includes(this.activeTab);
        },
        isPostTab: function() {
            return this.tabIndex === tabsArr.indexOf(tabs.post);
        },
    },
    methods: {
        ...mapMutations('tradeBalance', [
            'setUseBuyMarketPrice',
            'setBuyAmountInput',
            'setSubtractQuoteBalanceFromBuyAmount',
        ]),
        ...mapMutations('tokenInfo', [
            'setTokenAvatar',
        ]),
        ...mapMutations('posts', [
            'setSinglePost',
            'setPosts',
        ]),
        ...mapMutations('user', [
            'setOwnDeployedTokens',
        ]),
        setImage: function(avatarUrl) {
            this.currentTokenAvatar = avatarUrl;
        },
        initFireBase: function() {
            initializeApp(window.firebaseConfig);
        },
        initTwitterAuthPopUp: function() {
            window.twitterProvider = new TwitterAuthProvider();
            window.auth = getAuth();
            window.auth.useDeviceLanguage();
            window.signInWithPopup = signInWithPopup;
        },
        closeDeployedModal: function() {
            this.showDeployedOnBoard = false;
            this.$axios.single.patch(
                this.$routing.generate('token_update_deployed_modal', {tokenName: this.tokenName})
            );
        },
        descriptionUpdated: function(val) {
            this.tokenDescription = val;
        },
        goToTrade: function(amount) {
            this.changeTab(tabs.trade);

            if (amount) {
                this.setUseBuyMarketPrice(true);
                this.setBuyAmountInput(amount);
                this.setSubtractQuoteBalanceFromBuyAmount(true);
            }
        },
        singlePostDeleted: function() {
            this.changeTab(tabs.intro);
        },
        goToPost: function(post) {
            const postUrl = post.slug
                ? this.$routing.generate('token_show_post', {name: post.token.name, slug: post.slug}, true)
                : this.$routing.generate('show_post', {id: post.id}, true);

            window.history.replaceState({}, '', postUrl);

            this.setSinglePost(post);
            this.changeTab('post', post);
        },
        checkScreenSize: function() {
            // if screen < lg bootstrap breakpoint
            this.isMobileScreen = window.matchMedia(
                `screen and (max-width: ${MEDIA_BREAKPOINTS.max_width.md}px)`
            ).matches;
            this.isWideScreen = window.matchMedia(
                `screen and (min-width: ${MEDIA_BREAKPOINTS.min_width.xlg}px)`
            ).matches;
        },
        showRedirectMessage: function() {
            this.notifySuccess(this.$t('page.profile.tokens.save_changes'));
            window.history.replaceState(
                {},
                '',
                window.location.href.replace('?saveSuccess=true', ''),
            );
        },
        initStickySidebar() {
            new StickySidebar('.sticky-bar',
                {
                    topSpacing: 20,
                    bottomSpacing: 20,
                });
        },
        startTimeOutStickySidebar() {
            this.timeOutStickySidebar = setTimeout(() => this.initStickySidebar(), 500);
        },
        changeTab(tab, data) {
            this.activeTab = tab;
            let title = '';

            switch (tab) {
                case tabs.intro:
                    title = this.$t('page.pair.title_info', {name: this.tokenName, description: this.tokenDescription});
                    break;
                case tabs.voting:
                    this.activeVotingTab = tabs.voting;
                    title = this.$t('page.pair.title_voting', {name: this.tokenName});
                    break;
                case tabs.post:
                    title = this.$t('page.pair.title_post', {postTitle: data.title, tokenName: this.tokenName});
                    break;
                case tabs.trade:
                    title = this.$t('page.pair.title_market_tab', {name: this.tokenName});
                    break;
                default:
                    break;
            }

            const url = this.getUrlByTab(tab, data);

            document.title = title;
            window.scrollTo({top: 0});
            window.history.replaceState({}, '', url);
        },
        getUrlByTab(tab, data) {
            if (tabs.voting === tab) {
                return this.$routing.generate('token_list_voting', {name: this.tokenName});
            }

            if (tabs.trade === tab) {
                const crypto = this.rebrandingFunc(this.currentMarket.base.symbol) || MINTME.symbol;
                return this.$routing.generate('token_show_trade', {name: this.tokenName, crypto});
            }

            if (tabs.post === tab) {
                return data.slug
                    ? this.$routing.generate('token_show_post', {name: data.token.name, slug: data.slug}, true)
                    : this.$routing.generate('show_post', {id: data.id}, true);
            }

            return this.$routing.generate('token_show_intro', {name: this.tokenName});
        },
        getTabLinkClass(tab) {
            if (
                (tabs.trade === tab && this.activeTab === tabs.trade)
                || (tabs.voting === tab && this.activeTab === tabs.voting)
                || (tab === tabs.intro && this.activeTab !== tabs.trade && this.activeTab !== tabs.voting)
            ) {
                return 'active';
            }

            return '';
        },
        votingPageChanged(page) {
            this.activeVotingTab = page;
        },
        votingCreated() {
            if (this.$refs['votingCounter']) {
                this.$refs['votingCounter'].refreshCounter();
            }
        },
        counterRefreshed: function() {
            this.votingCreated();
        },
        updateScroll: function() {
            this.scrollPosition = window.scrollY;

            const tokenName = document.getElementById('nav-token-name');
            const mintmeLogo = document.getElementById('mintme-logo');

            if (getScreenMediaSize() > ScreenMediaSize.XXS) {
                if (50 < this.scrollPosition) {
                    mintmeLogo.src = this.logoWithoutText;
                    tokenName.innerHTML = this.truncateFunc(this.tokenName, this.maxLengthToTruncate);
                } else {
                    tokenName.innerHTML = '';
                    mintmeLogo.src = this.logoWithText;
                }
                tokenName.classList.remove('h4');
                tokenName.classList.add('h6');
            }

            if (getScreenMediaSize() >= ScreenMediaSize.MD) {
                tokenName.classList.remove('h6');
                tokenName.classList.add('h4');
            }
        },
        openStatistics() {
            this.statisticsOpened = true;
        },
    },
    store,
});
