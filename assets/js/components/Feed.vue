<template>
    <div v-if="showFeed">
        <div
            v-for="(n, i) in itemsToShow"
            :key="i"
            class="feed d-flex flex-column"
            :class="itemClass"
        >
            <div :class="{'padding-feed-cell': !isFeedPage}">
                <div :class="{'w-100': !isFeedPage}" v-html="sanitizedItems[i].message"></div>
                <div :class="dateClass">
                    {{ sanitizedItems[i].date }}
                </div>
            </div>
        </div>
        <div class="text-center mt-4" v-if="!isHomePage && min !== max">
            <button class="btn btn-lg button-secondary rounded-pill" @click="toggle">
                <span class="pt-2 pb-2 pl-3 pr-3">
                    {{ showMoreText }}
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import {getCoinAvatarAssetName} from '../utils';
import {RebrandingFilterMixin, BnbToBscFilterMixin} from '../mixins';
import TruncateFilterMixin from '../mixins/filters/truncate';
import {
    TOK,
    WSAPI,
    TOKEN_DEFAULT_ICON_URL,
    WEB,
} from '../utils/constants';
import {mapGetters} from 'vuex';

export default {
    name: 'Feed',
    mixins: [
        TruncateFilterMixin,
        RebrandingFilterMixin,
        BnbToBscFilterMixin,
    ],
    props: {
        itemsProp: Array,
        mercureHubUrl: String,
        min: Number,
        max: Number,
        lang: String,
        isFeedPage: Boolean,
        isHomePage: {
            type: Boolean,
            default: false,
        },
        showMoreHomePage: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            items: this.itemsProp,
            topic: 'activities',
            showMoreFeedPage: false,
        };
    },
    mounted() {
        moment.locale(this.lang);
        const es = new EventSource(this.mercureHubUrl + '?topic=' + encodeURIComponent(this.topic));
        this.items = this.groupedItems;

        es.onmessage = (e) => {
            const data = JSON.parse(e.data);

            if (this.isNotADuplicate(data)) {
                this.items.unshift(data);
                this.items = this.groupedItems;
            }
        };
    },
    computed: {
        ...mapGetters('crypto', {
            enabledCryptosMap: 'getCryptosMap',
        }),
        sanitizedItems() {
            return this.items.map((item) => {
                return {
                    message: this.$t(`activity_${item.type}_message`, item.context),
                    date: moment(item.createdAt).fromNow(),
                };
            });
        },
        showFeed() {
            return 0 < this.items.length;
        },
        itemsToShow() {
            const max = this.showMore ? this.max : this.min;
            return Math.min(max, this.items.length);
        },
        groupedItems() {
            return this.items.reduce((items, item) => {
                if (!this.isGroupableType(item)) {
                    items.push(item);

                    return items;
                }

                if (0 < items.length
                    && items[items.length - 1].type === item.type
                    && (this.isDonation(items[items.length - 1], item) || this.isTrade(items[items.length - 1], item))
                ) {
                    const amount = new Decimal(items[items.length - 1].context.amount).add(item.context.amount);
                    items[items.length - 1].context.amount = amount.toFixed();
                } else {
                    items.push(item);
                }

                return items;
            }, []);
        },
        showMoreText() {
            return this.showMore ? this.$t('page.index.see_less') : this.$t('page.index.see_more');
        },
        itemClass() {
            return this.isFeedPage ? 'mt-3' : 'mb-4';
        },
        showMore() {
            return this.isHomePage ? this.showMoreHomePage : this.showMoreFeedPage;
        },
        dateClass() {
            if (this.isFeedPage) {
                return 'date text-right';
            }

            return 'font-size-12 text-subtitle recent-activities-date';
        },
    },
    methods: {
        isNotADuplicate(item) {
            return !this.items.some((i) => i.type === item.type && this.isEqualObject(i.context, item.context));
        },
        isEqualObject(obj1, obj2) {
            return JSON.stringify(obj1) === JSON.stringify(obj2);
        },
        isDonation(savedItem, receivedItem) {
            return WSAPI.order.type.DONATION === savedItem.type
                && savedItem.context.token === receivedItem.context.token
                && savedItem.context.tradeIconUrl === receivedItem.context.tradeIconUrl;
        },
        isTrade(savedItem, receivedItem) {
            return WSAPI.order.type.TOKEN_TRADED === savedItem.type
                && savedItem.context.token === receivedItem.context.token
                && savedItem.context.buyer === receivedItem.context.buyer
                && savedItem.context.tradeIconUrl === receivedItem.context.tradeIconUrl;
        },
        isGroupableType(item) {
            return WSAPI.order.type.DONATION === item.type
                || WSAPI.order.type.TOKEN_TRADED === item.type;
        },
        toggle() {
            this.showMoreFeedPage = !this.showMoreFeedPage;
        },
        tokenIcon(token) {
            return token
                && token.image
                && TOKEN_DEFAULT_ICON_URL !== token.image.url
                ? token.image.avatar_small
                : this.getDefaultTokenIcon(token);
        },
        getDefaultTokenIcon(token) {
            const icon = getCoinAvatarAssetName(token.cryptoSymbol) || WEB.icon;

            return require(`../../img/${icon}`);
        },
        tradeIcon(symbol, token = null) {
            return TOK.symbol === symbol && token?.image
                ? this.tokenIcon(token)
                : require('../../img/' + getCoinAvatarAssetName(symbol));
        },
        profileIcon(profileType) {
            return profileType && profileType.profile.image
                ? profileType.profile.image.avatar_small
                : require('../../img/user-avatar.png');
        },
        rebrandBlockchain: function(blockchain) {
            return this.rebrandingFunc(this.bnbToBscFunc(blockchain));
        },
    },
};
</script>
