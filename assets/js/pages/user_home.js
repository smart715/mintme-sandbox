import '../../scss/pages/user_home.sass';
import UserFeed from '../components/posts/UserFeed';
import FeedTrendingTags from '../components/posts/FeedTrendingTags';
import Feed from '../components/Feed';
import i18n from '../utils/i18n/i18n';
import Axios from '../axios';
import Guide from '../components/Guide';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faLongArrowAltLeft} from '@fortawesome/free-solid-svg-icons';
import store from '../storage';
import BalanceInit from '../components/trade/BalanceInit';
import PostsInit from '../components/posts/PostsInit';
import CreatePostModal from '../components/modal/CreatePostModal';
import {mapMutations} from 'vuex';
import {debounce} from 'lodash';
import {MButton} from '../components/UI';
import {tokenDeploymentStatus} from '../utils/constants';
import TopTokensList from '../components/TopTokensList';

library.add(faLongArrowAltLeft);

Vue.use(Axios);

const TABS = {
    ALL: 'all',
    FEED: 'feed',
    TAGS: 'tags',
    ACTIVITY: 'activity',
    TOP_TOKENS: 'top-tokens',
};

new Vue({
    el: '#user-home',
    i18n,
    components: {
        Guide,
        UserFeed,
        FeedTrendingTags,
        FontAwesomeIcon,
        BalanceInit,
        PostsInit,
        CreatePostModal,
        Feed,
        MButton,
        TopTokensList,
    },
    data() {
        return {
            hashtag: '', // keep it not null to update watcher
            showPopularHashtags: true,
            activeTab: document.getElementById('active_tab')?.value || TABS.ALL,
            onScreenResourceDebounce: null,
            isXLScreen: false,
            isSmallScreen: false,
            selectedToken: null,
        };
    },
    methods: {
        ...mapMutations('posts', [
            'addPost',
        ]),
        onHashtagChange(hashtag) {
            this.hashtag = hashtag;
        },
        clearHashtag() {
            this.hashtag = null;
        },
        onHashtagsLoaded(hashtags) {
            this.showPopularHashtags = !!Object.keys(hashtags || {}).length;
        },
        onPostSaveSuccess(event) {
            if (event.isNew) {
                this.addPost(event.post);
            }
        },
        changeTab(tab) {
            this.activeTab = tab;

            const url = this.$routing.generate('homepage') + (TABS.ALL === this.activeTab ? '?tab=all' : '');
            window.history.replaceState({}, '', url);
        },
        handleScreenResize() {
            const oldIsXlScreen = this.isXLScreen;
            const oldSmallScreen = this.isSmallScreen;

            this.isXLScreen = window.matchMedia(
                `screen and (min-width: 1400px)`
            ).matches;

            this.isSmallScreen = window.matchMedia(
                `screen and (max-width: 767px)`
            ).matches;

            if (oldIsXlScreen !== this.isXLScreen || oldSmallScreen !== this.isSmallScreen) {
                this.$forceUpdate();
            }
        },
        onTokenChange(token) {
            this.selectedToken = token;
        },
        createToken() {
            window.location.href = this.$routing.generate('token_create');
        },
        deployToken() {
            window.location.href = this.$routing.generate('token_settings', {
                tokenName: this.selectedToken.name,
                tab: 'deploy',
            });
        },
    },
    computed: {
        tooltipText() {
            return this.hashtag
                ? this.$t('page.pair.tooltip.by_hashtag', {tag: this.hashtag})
                : this.$t('page.pair.tooltip.recent_feed');
        },
        isAllTab() {
            return TABS.ALL === this.activeTab;
        },
        showPostForm() {
            return !this.hashtag && (this.isAllTab || TABS.FEED === this.activeTab);
        },
        showCreateToken() {
            return !this.selectedToken;
        },
        showDeployToken() {
            return this.selectedToken && tokenDeploymentStatus.notDeployed === this.selectedToken.deploymentStatus;
        },
    },
    created() {
        this.handleScreenResize();
    },
    mounted() {
        this.hashtag = document.getElementById('hashtag_param')?.value || null;
        this.onScreenResourceDebounce = debounce(this.handleScreenResize, 100);

        window.addEventListener('resize', () => {
            this.onScreenResourceDebounce.cancel();
            this.onScreenResourceDebounce();
        });

        window.addEventListener('scroll', this.updateScroll);
    },
    watch: {
        hashtag() {
            const currentURL = new URL(window.location.href);

            if (this.hashtag) {
                currentURL.searchParams.set('hashtag', this.hashtag);
            } else {
                currentURL.searchParams.delete('hashtag');
            }

            window.history.replaceState({}, '', currentURL);
        },
    },
    store,
});
