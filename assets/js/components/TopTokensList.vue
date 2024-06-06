<template>
    <div class="top-tokens-list">
        <div v-if="isLoading" class="d-flex justify-content-center p-3">
            <span class="spinner-border spinner-border-md">
                <span class="sr-only"> {{ $t('loading') }} </span>
            </span>
        </div>
        <div v-else>
            <a
                v-for="status in marketStatuses"
                :key="status.quote.name"
                :href="getTokenPageUrl(status.quote.name)"
                class="d-flex align-items-center justify-content-between font-size-1 p-2 px-3 top-token-item"
            >
                <div class="position-relative">
                    <coin-avatar
                        is-user-token
                        :image="status.quote.image"
                        image-class="coin-avatar-post"
                        :deployed="true"
                    />
                    <span
                        v-if="status.quote.activeAirdrop"
                        class="has-airdrop"
                        v-b-tooltip="$t('ongoing_airdrop.title')"
                    >
                        <img :src="airdropIcon" />
                    </span>
                </div>
                <div class="flex-fill overflow-hidden px-2">
                    <div class="text-truncate font-weight-semibold">{{ status.quote.name }}</div>
                    <div class="d-flex">
                        <div
                            v-for="(market, index) in status.networks"
                            :key="index"
                        >
                            <avatar
                                :image="getNetworkIcon(market)"
                                type="token"
                                size="small"
                                class="d-inline"
                            />
                        </div>
                    </div>
                </div>
                <div class="font-weight-semibold text-right">
                    <div> {{ status.lastPrice | toMoney(TOK.subunit) | formatMoney }}</div>
                    <div
                        :class="{'text-green': status.changePercentage > 0, 'text-danger': status.changePercentage < 0}"
                    >
                        {{ status.changePercentage > 0 ? '+' : '' }}{{ status.changePercentage }}%
                    </div>
                </div>
            </a>
            <div class="text-center mt-3">
                <button
                    class="btn btn-lg button-secondary rounded-pill"
                    @click="openTokensPage"
                >
                    <span class="pt-2 pb-2 pl-3 pr-3">
                        {{ $t('page.index.see_more') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import {MoneyFilterMixin, NotificationMixin, WebSocketMixin} from '../mixins';
import {TOK} from '../utils/constants';
import CoinAvatar from './CoinAvatar';
import Avatar from './Avatar';
import {getCoinAvatarAssetName, toMoney} from '../utils';
import {VBTooltip} from 'bootstrap-vue';

export default {
    name: 'TopTokensList',
    components: {
        CoinAvatar,
        Avatar,
    },
    mixins: [
        WebSocketMixin,
        NotificationMixin,
        MoneyFilterMixin,
    ],
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        amountToShow: {
            type: Number,
            default: 10,
        },
    },
    data() {
        return {
            isLoading: false,
            marketStatuses: [],
            TOK: TOK,
            airdropIcon: require('../../img/airdrop.svg'),
            topTokensMap: {},
        };
    },
    async mounted() {
        this.loadTopTokens();
    },
    methods: {
        async loadTopTokens() {
            this.isLoading = true;
            try {
                this.topTokensMap = (await this.$axios.retry.get(this.$routing.generate('top-tokens'))).data;
                this.marketStatuses = Object.values(this.topTokensMap).slice(0, this.amountToShow);

                // default db percentage is not correct
                this.marketStatuses.forEach((m) => m.changePercentage = 0);

                this.isLoading = false;
                this.initMarketUpdateListener(Object.keys(this.topTokensMap));
            } catch (err) {
                this.$logger.error('Can not get top tokens', err);
                this.notifyError(this.$t('toasted.error.try_reload'));
            }
        },
        getNetworkIcon(symbol) {
            return require(`../../img/${getCoinAvatarAssetName(symbol)}`);
        },
        openTokensPage() {
            window.location = this.$routing.generate('trading', {type: 'tokens'});
        },
        getTokenPageUrl(tokenName) {
            return this.$routing.generate('token_show_intro', {
                name: tokenName,
            });
        },
        initMarketUpdateListener(marketIds) {
            this.addMessageHandler((result) => {
                if ('state.update' !== result.method) {
                    return;
                }

                const marketInfo = result.params[1];
                const marketLastPrice = parseFloat(marketInfo.last);
                const changePercentage = this.getPercentage(marketLastPrice, parseFloat(marketInfo.open));

                const token = this.topTokensMap[result.params[0]];

                if (!token) {
                    return;
                }

                token.changePercentage = toMoney(changePercentage, 2);
                token.lastPrice = marketInfo.last;
            }, 'top_tokens', 'TopTokens');

            this.addOnOpenHandler(() => {
                const request = JSON.stringify({
                    method: 'state.subscribe',
                    params: marketIds,
                    id: parseInt(Math.random().toString().replace('0.', '')),
                });
                this.sendMessage(request);
            });
        },
        getPercentage: function(lastPrice, openPrice) {
            return openPrice ? (lastPrice - openPrice) * 100 / openPrice : 0;
        },
    },
};
</script>
