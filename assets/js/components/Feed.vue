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
import Decimal from 'decimal.js';
import {toMoney} from '../utils';
import {currencies, TOK, WSAPI} from '../utils/constants';
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
        this.items = this.groupedItems;

        es.onmessage = (e) => {
            let data = JSON.parse(e.data);
            this.items.unshift(data);
            this.items = this.groupedItems;
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
        groupedItems() {
            let temp = [];

            for (let index = 0; index < this.items.length; index++) {
                let grouped = false;
                const item = this.items[index];

                if (!this.isGroupableType(item)) {
                    temp.push(item);
                    continue;
                }

                for (let tempIndex = 0; tempIndex < temp.length; tempIndex++) {
                    const tempItem = temp[tempIndex];

                    if (tempItem.type !== item.type) {
                        continue;
                    }

                    if (this.isDonation(tempItem, item) || this.isTrade(tempItem, item)) {
                        const amount = new Decimal(temp[tempIndex].amount).add(item.amount);
                        temp[tempIndex].amount = amount.toFixed();
                        grouped = true;
                        break;
                    }
                }

                if (!grouped) {
                    temp.push(item);
                }
            }

            return temp;
        },
    },
    methods: {
        isDonation(savedItem, receivedItem) {
            return WSAPI.order.type.DONATION === savedItem.type
                && savedItem.token.name === receivedItem.token.name
                && savedItem.currency === receivedItem.currency;
        },
        isTrade(savedItem, receivedItem) {
            return WSAPI.order.type.TOKEN_TRADED === savedItem.type
                && savedItem.token.name === receivedItem.token.name
                && savedItem.buyer.id === receivedItem.buyer.id;
        },
        isGroupableType(item) {
            return WSAPI.order.type.DONATION === item.type
                || WSAPI.order.type.TOKEN_TRADED === item.type;
        },
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
                post: item.post ? `"${this.truncateFunc(item.post.title, 32)}"` : '',
                postUrl: item.post ? this.$routing.generate('show_post', {id: item.post.id}) : '',
                symbol,
            };
        },
        toggle() {
            this.showMore = !this.showMore;
        },
    },
};
</script>
