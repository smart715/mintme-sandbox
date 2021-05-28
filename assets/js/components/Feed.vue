<template>
    <div class="feed-container" v-if="showFeed">
        <div class="feed d-flex flex-column">
            <div v-for="(n, i) in itemsToShow" :key="i" class="feed-row d-flex flex-row justify-content-between">
                <div class="feed-cell" v-html="sanitizedItems[i].message"></div>
                <div class="feed-cell d-flex align-items-center">
                    {{ sanitizedItems[i].date }}
                </div>
            </div>
        </div>
        <div class="text-center">
            <font-awesome-icon
                class="icon-default mt-3 c-pointer"
                :icon="['fac', 'downArrow']"
                :transform="showMore ? 'rotate-180' : ''"
                @click="toggle"
            />
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import {toMoney} from '../utils';
import {currencies, TOK} from '../utils/constants';
import {library} from '@fortawesome/fontawesome-svg-core';
import {downArrow} from '../utils/icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {RebrandingFilterMixin} from '../mixins';
import TruncateFilterMixin from '../mixins/filters/truncate';

library.add(downArrow);

export default {
    name: 'Feed',
    components: {
        FontAwesomeIcon,
    },
    mixins: [
        TruncateFilterMixin,
        RebrandingFilterMixin,
    ],
    props: {
        itemsProp: Array,
        mercureHubUrl: String,
        min: Number,
        max: Number,
    },
    data() {
        return {
            items: this.itemsProp,
            topic: 'activities',
            showMore: false,
        };
    },
    mounted() {
        const es = new EventSource(this.mercureHubUrl + '?topic=' + encodeURIComponent(this.topic));
        es.onmessage = (e) => {
            let data = JSON.parse(e.data);
            this.items.unshift(data);
        };
    },
    computed: {
        sanitizedItems() {
            return this.items.map((item) => {
                return {
                    message: this.$t(`activity_${item.type}_message`, this.createTranslationContext(item)),
                    date: moment(item.createdAt).fromNow(),
                };
            });
        },
        showFeed() {
            return this.items.length > 0;
        },
        itemsToShow() {
            let max = this.showMore ? this.max : this.min;
            return Math.min(max, this.items.length);
        },
    },
    methods: {
        createTranslationContext(item) {
            let subunit = item.currency ? currencies[item.currency].subunit : TOK.subunit;
            let symbol = this.rebrandingFunc(TOK.symbol === item.currency ? 'tokens' : item.currency);

            return {
                token: this.truncateFunc(item.token.name, 25),
                tokenUrl: this.$routing.generate('token_show', {name: item.token.name}),
                user: item.user ? this.truncateFunc(item.user.profile.nickname, 12) : null,
                userUrl: item.user ? this.$routing.generate('profile-view', {nickname: item.user.profile.nickname}) : null,
                amount: item.amount ? toMoney(item.amount, subunit) : null,
                buyer: item.buyer ? this.truncateFunc(item.buyer.profile.nickname, 12) : null,
                buyerUrl: item.buyer ? this.$routing.generate('profile-view', {nickname: item.buyer.profile.nickname}) : null,
                seller: item.seller ? this.truncateFunc(item.seller.profile.nickname, 12) : null,
                sellerUrl: item.seller ? this.$routing.generate('profile-view', {nickname: item.seller.profile.nickname}) : null,
                symbol,
            };
        },
        toggle() {
            this.showMore = !this.showMore;
        },
    },
};
</script>
