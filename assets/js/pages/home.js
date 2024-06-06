import '../../scss/pages/home.sass';
import Typed from 'typed.js';
import Countdown from '../components/Countdown.vue';
import FaqItem from '../components/FaqItem';
import i18n from '../utils/i18n/i18n';
import {OpenPageMixin} from '../mixins';
import CryptoInit from '../components/CryptoInit';
import store from '../storage';
import sanitizeHtml from '../sanitize_html';
import PostsInit from '../components/posts/PostsInit';
import BalanceInit from '../components/trade/BalanceInit';
import Feeds from '../components/Feeds';
import Feed from '../components/Feed';
import UserFeed from '../components/posts/UserFeed';
import TopTokensList from '../components/TopTokensList';
import FeedTrendingTags from '../components/posts/FeedTrendingTags';
import {mapMutations} from 'vuex/dist/vuex.common.js';
import {debounce} from 'lodash';
import {MButton} from '../components/UI';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faLongArrowAltLeft} from '@fortawesome/free-solid-svg-icons';

library.add(faLongArrowAltLeft);

Vue.use(sanitizeHtml);

const TABS = {
    ALL: 'all',
    FEED: 'feed',
    TAGS: 'tags',
    ACTIVITY: 'activity',
    TOP_TOKENS: 'top-tokens',
};

new Vue({
    el: '#home',
    i18n,
    store,
    components: {
        PostsInit,
        BalanceInit,
        Countdown,
        FaqItem,
        MainPageVideoBtn: () => import(/* webpackChunkName: "main-page-video-btn" */ '../components/MainPageVideoBtn'),
        CryptoInit,
        Feeds,
        Feed,
        UserFeed,
        TopTokensList,
        FeedTrendingTags,
        MButton,
        FontAwesomeIcon,
    },
    mixins: [
        OpenPageMixin,
    ],
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
        createToken() {
            window.location.href = this.$routing.generate('register');
        },
    },
    computed: {
        tooltipText() {
            return this.hashtag
                ? this.$t('page.pair.tooltip.by_hashtag', {tag: this.hashtag})
                : this.$t('page.pair.tooltip.recent_feed');
        },
        showPostForm() {
            return !this.hashtag && TABS.ALL === this.activeTab;
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
});

new Typed('#typed', {
    stringsElement: '#typed-strings',
    typeSpeed: 100,
    backSpeed: 100,
    loop: true,
    showCursor: true,
    cursorChar: '|',
    backDelay: 2500,
});
