<template>
    <div>
        <div class="card">
            <div class="card-header my-2 d-flex align-items-center justify-content-between">
                <div
                    class="font-size-3 font-weight-semibold header-highlighting"
                    v-html="$t('trade.history.header')"
                ></div>
                <span class="card-header-icon">
                    <guide
                        :key="tooltipKey"
                        class="font-size-3"
                    >
                        <template slot="header">
                            {{ $t('trade.history.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('trade.history.guide_body') }}
                            <coin-avatar
                                :image="getCryptoIconBySymbol(market.base)"
                                :symbol="market.base.symbol"
                                :is-user-token="true"
                            />
                            {{ market.base.symbol | rebranding }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body pb-4 p-0 px-3">
                <div v-if="serviceUnavailable" class="p-5 text-center text-white">
                    {{ this.$t('toasted.error.service_unavailable_short') }}
                </div>
                <template v-else-if="loaded">
                    <div
                        class="table-orders table-sticky-head overflow-auto custom-scrollbar mb-0"
                        ref="table"
                    >
                        <b-table
                            v-if="hasOrders"
                            class="w-100 table-trade-history"
                            :items="ordersList"
                            :fields="fields"
                        >
                            <template v-slot:head(pricePerQuote)>
                                <span v-b-tooltip="pricePerQuoteTooltip">
                                    {{ $t('trade.history.price') }}
                                </span>
                            </template>
                            <template v-slot:head(quoteAmount)>
                                <span
                                    v-if="shouldTruncate"
                                    v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'viewport'}"
                                >
                                    {{ market.quote | rebranding | truncate(maxLengthToTruncate) }}
                                    {{ $t('trade.history.amount') }}
                                </span>
                                <span v-else>
                                    {{ market.quote | rebranding }} {{ $t('trade.history.amount') }}
                                </span>
                            </template>
                            <template v-slot:head(crypto)>
                                {{ $t('trading.crypto.header') }}
                                <guide boundaries-element="window" max-width="400px" class="font-size-2">
                                    <template slot="body">
                                        <p v-html="$t('trade.history.crypto_body_1')"></p>
                                        <p v-html="$t('trade.history.crypto_body_2')"></p>
                                        <p v-html="$t('trade.history.crypto_body_3')"></p>
                                        <p v-html="$t('trade.history.crypto_body_4')"></p>
                                    </template>
                                </guide>
                            </template>
                            <template v-slot:cell(traders)="row">
                                <div class="d-flex flex-row mr-1 justify-content-start">
                                    <div>
                                        <a
                                            :href="row.item.takerUrl"
                                            class="d-flex flex-row align-items-center
                                                   justify-content-between w-100 text-white"
                                        >
                                            <img
                                                :src="row.item.takerAvatar"
                                                class="rounded-circle flex-grow-0 mr-1"
                                                alt="avatar"
                                            />
                                            <span class="d-inline-block truncate-name trader-name-width flex-grow-1">
                                                <span
                                                    v-if="shouldTruncateNickname(row.value.orderTrader)"
                                                    v-b-tooltip="{title: row.value.orderTrader, boundary:'viewport'}"
                                                >
                                                    {{ row.value.orderTrader }}
                                                </span>
                                                <span v-else >
                                                    {{ row.value.orderTrader }}
                                                </span>
                                            </span>
                                        </a>
                                        <a
                                            v-if="row.item.owner"
                                            class="d-inline-block flex-grow-0"
                                        >
                                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                        </a>
                                    </div>
                                    <font-awesome-icon
                                        icon="caret-right"
                                        class="text-white mx-1 mt-1"
                                    />
                                    <div>
                                        <a
                                            :href="row.item.makerUrl"
                                            class="d-flex flex-row align-items-center
                                                   justify-content-between w-100 text-white"
                                        >
                                            <img
                                                :src="row.item.makerAvatar"
                                                class="rounded-circle flex-grow-0 mr-1"
                                                :alt="$t('avatar')"
                                            />
                                            <span class="d-inline-block truncate-name trader-name-width flex-grow-1">
                                                <span
                                                    v-if="shouldTruncateNickname(row.value.orderMaker)"
                                                    v-b-tooltip="{title: row.value.orderMaker, boundary:'viewport'}"
                                                >
                                                    {{ row.value.orderMaker }}
                                                </span>
                                                <span
                                                    v-else
                                                >
                                                    {{ row.value.orderMaker }}
                                                </span>
                                            </span>
                                        </a>
                                        <a
                                            v-if="row.item.owner"
                                            class="d-inline-block flex-grow-0"
                                        >
                                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                        </a>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:cell(pricePerQuote)="row">
                                <div class="d-flex mr-1">
                                    <span class="d-inline-block flex-grow-1">
                                        <span
                                            v-b-tooltip="{
                                                title: currencyConvert(row.value.amount, rate, USD.subunit),
                                                boundary:'viewport'
                                            }"
                                        >
                                            {{ toMoneyWithTrailingZeroes(row.value.amount) }}
                                        </span>
                                        <coin-avatar
                                            image-class
                                            :image="row.value.avatar"
                                            :is-user-token="true"
                                        />
                                    </span>
                                </div>
                            </template>

                            <template v-slot:cell(crypto)="row">
                                <div class="d-flex mr-1" :class="orderRowClass(row.item.type)">
                                    <div>
                                        <span class="truncate-name">
                                            {{ row.value.quote.amount }}
                                            <img
                                                id="quote-avatar"
                                                class="rounded-circle mr-1"
                                                :class="{'coin-avatar-mintme' : isWebSymbol(row.value.quote.symbol)}"
                                                alt="avatar"
                                                :src="row.value.quote.avatar"
                                            />
                                        </span>
                                    </div>
                                    <font-awesome-icon
                                        icon="caret-right"
                                        class="text-white mx-1 mt-1"
                                    />
                                    <div>
                                        <span class="truncate-name">
                                            {{ row.value.base.amount }}
                                            <img
                                                id="base-avatar"
                                                class="rounded-circle mr-1"
                                                alt="avatar"
                                                :src="row.value.base.avatar"
                                            />
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <template v-slot:cell(dateTime)="row">
                                <div
                                    v-b-tooltip="dateTimeColumnTooltip(row.value.hoverFormatDate)"
                                >
                                    {{ row.value.tableFormatDate }}
                                </div>
                            </template>
                        </b-table>
                    </div>
                    <div v-if="!hasOrders">
                        <p class="text-center p-5">{{ $t('trade.history.no_deals') }}</p>
                    </div>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes, faCaretRight} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import Decimal from 'decimal.js';
import {BTable, VBTooltip} from 'bootstrap-vue';
import Guide from '../Guide';
import {formatMoney, toMoney, toMoneyWithTrailingZeroes, removeSpaces, getCoinAvatarAssetName} from '../../utils';
import {mapGetters} from 'vuex';
import {USD, usdSign, TYPE_SELL} from '../../utils/constants.js';
import {
    GENERAL,
    webSymbol,
    TOKEN_MINTME_ICON_URL,
    NICKNAME_TRUNCATE_LENGTH,
} from '../../utils/constants';
import {
    WebSocketMixin,
    FiltersMixin,
    LazyScrollTableMixin,
    RebrandingFilterMixin,
    OrderMixin,
    CurrencyConverter,
} from '../../mixins/';
import CoinAvatar from '../CoinAvatar';

library.add(faTimes, faCaretRight);

const LIMIT_EXECUTED_ORDERS = 50;

export default {
    name: 'TradeTradeHistory',
    components: {
        BTable,
        Guide,
        FontAwesomeIcon,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        LazyScrollTableMixin,
        RebrandingFilterMixin,
        OrderMixin,
        CurrencyConverter,
    ],
    props: {
        market: Object,
        changingMarket: Boolean,
        isToken: Boolean,
    },
    data() {
        return {
            serviceUnavailable: false,
            maxLengthToTruncate: 3,
            currentPage: 1,
            limit: 50,
            tooltipKey: 0,
            USD,
        };
    },
    computed: {
        ...mapGetters('rates', [
            'getRates',
        ]),
        fields() {
            const commonFields = [
                {
                    key: 'type',
                    label: this.$t('trade.history.type'),
                },
                {
                    key: 'pricePerQuote',
                },
                {
                    key: 'traders',
                    label: this.$t('trade.history.traders'),
                },
                {
                    key: 'crypto',
                },
                {
                    key: 'dateTime',
                    label: this.$t('trade.history.time'),
                    formatter: (date) => {
                        return {
                            tableFormatDate: moment.unix(date).format(GENERAL.dateTimeFormatTable),
                            hoverFormatDate: moment.unix(date).format(GENERAL.dateTimeFormat),
                        };
                    },
                    thClass: this.dateColumnClasses,
                    tdClass: this.dateColumnClasses,
                },
            ];

            return this.isToken ? commonFields : this.removeArrayItem(commonFields, 'traders');
        },
        dateColumnClasses() {
            return this.isToken ? 'text-right' : 'text-left date-column-width';
        },
        shouldTruncate: function() {
            return this.market.quote.symbol.length > this.maxLengthToTruncate;
        },
        hasOrders: function() {
            return 0 < this.ordersList.length;
        },
        ordersList: function() {
            return false !== this.tableData ? this.tableData.filter((order) => order.maker && order.taker)
                .map((order) => {
                    return {
                        dateTime: order.timestamp,
                        traders: {
                            orderMaker: order.maker.profile.nickname,
                            orderTrader: order.taker.profile.nickname,
                        },
                        makerUrl: this.$routing.generate('profile-view', {nickname: order.maker.profile.nickname}),
                        takerUrl: this.$routing.generate('profile-view', {nickname: order.taker.profile.nickname}),
                        type: this.getSideByType(order.side),
                        pricePerQuote: {
                            amount: toMoney(order.price, this.priceSubunits),
                            avatar: this.getCryptoIconBySymbol(order.market.base),
                        },
                        crypto: {
                            quote: {
                                amount: formatMoney(toMoney(order.amount, this.market.quote.subunit)),
                                symbol: order.market.quote.symbol,
                                avatar: order.market.quote?.isToken === undefined
                                    ? this.getTokenIconBySymbol(order.market.quote)
                                    : this.getCryptoIconBySymbol(order.market.quote),
                            },
                            base: {
                                amount: formatMoney(toMoney(
                                    new Decimal(order.price).mul(order.amount).toString(),
                                    this.priceSubunits,
                                )),
                                symbol: order.market.base.symbol,
                                avatar: this.getCryptoIconBySymbol(order.market.base),
                            },
                        },
                        makerAvatar: order.maker.profile.image.avatar_small,
                        takerAvatar: order.taker.profile.image.avatar_small,
                    };
                }) : [];
        },
        loaded: function() {
            return null !== this.tableData;
        },
        priceSubunits: function() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : this.market.base.subunit;
        },
        usdPriceSubunits: function() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : USD.subunit;
        },
        lastId: function() {
            return this.tableData &&
                this.tableData.length &&
                this.tableData[this.tableData.length - 1].hasOwnProperty('id')
                ? this.tableData[this.tableData.length - 1].id
                : 0;
        },
        rate: function() {
            return (this.getRates[this.market.base.symbol] || [])[USD.symbol] || 1;
        },
        pricePerQuoteTooltip() {
            return {
                title: this.$t('trade.history.price_per', {quoteSymbol: this.rebrandingFunc(this.market.quote)}),
                boundary: 'viewport',
            };
        },
        hasOrdersReachedLimit: function() {
            return this.tableData.length >= this.limit;
        },
    },
    mounted: function() {
        this.updateTableData()
            .then(() => {
                this.updateAndParseWsDeals();
            }).catch((err) => {
                this.serviceUnavailable = true;
                this.$logger.error('Can not update table data', err);
            });
    },
    methods: {
        updateAndParseWsDeals: function() {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler(async (response) => {
                if ('deals.update' === response.method) {
                    const orders = response.params[1];

                    if (0 === orders.length) {
                        return;
                    }

                    const ordersCount = orders.length > LIMIT_EXECUTED_ORDERS
                        ? LIMIT_EXECUTED_ORDERS
                        : orders.length;

                    for (let i = ordersCount - 1; 0 <= i; i--) {
                        await this.addOrderTableData(orders[i]);
                    }
                }
            }, 'trade-tableData-update-deals', 'TradeTradeHistory');
        },
        addOrderTableData: async function(order) {
            try {
                if (!this.tableData) {
                    this.tableData = [];
                }

                const orderId = parseInt(order.id);

                if (!this.tableData.find((item) => item.id === orderId)) {
                    const orderDetails = await this.$axios.retry.get(this.$routing.generate('executed_order_details', {
                        base: this.market.base.symbol,
                        quote: this.market.quote.symbol,
                        id: orderId,
                    }));

                    this.insertNewOrder(orderDetails);
                }
            } catch (err) {
                this.$logger.error('Can not get executed order details', err);
            };
        },
        updateTableData: async function(attachItems = false) {
            try {
                const response = await this.$axios.retry.get(
                    this.$routing.generate('executed_orders', {
                        base: this.market.base.symbol,
                        quote: this.market.quote.symbol,
                        id: this.currentPage,
                    }
                    ));

                if (!response.data.length) {
                    if (!attachItems) {
                        this.tableData = response.data;
                    }

                    return;
                }

                if (!attachItems) {
                    this.tableData = response.data;
                    this.currentPage = 2;
                } else {
                    const resultData = response.data.filter((order) => order.id < this.lastId);
                    this.tableData = this.tableData.concat(resultData);

                    if (resultData.length && this.limit < this.tableData[this.tableData.length - 1].id) {
                        this.currentPage++;
                    }
                }
            } catch (error) {
                this.$logger.error('Can not get executed orders', error);
            }
        },
        insertNewOrder: function(orderDetails) {
            if (this.hasOrdersReachedLimit) {
                this.tableData.pop();
            }

            this.tableData.unshift(orderDetails.data);
        },
        currencyConvert: function(val, rate, subunit) {
            return this.currencyConversion(
                removeSpaces(val),
                rate,
                usdSign,
                this.usdPriceSubunits,
                this.usdPriceSubunits
            );
        },
        getCryptoIconBySymbol(base) {
            return require(`../../../img/${getCoinAvatarAssetName(base.symbol)}`);
        },
        getTokenIconBySymbol(quote) {
            if (quote.image && TOKEN_MINTME_ICON_URL !== quote.image.url) {
                return quote.image.avatar_small;
            }

            return require(`../../../../public/media/default_token.png`);
        },
        orderRowClass: function(type) {
            return TYPE_SELL === type
                ? 'justify-content-start flex-row'
                : 'justify-content-end flex-row-reverse';
        },
        shouldTruncateNickname: function(nickname) {
            return nickname.length > NICKNAME_TRUNCATE_LENGTH;
        },
        dateTimeColumnTooltip: function(hoverFormatDate) {
            return {
                title: hoverFormatDate,
                customClass: 'text-right',
            };
        },
        removeArrayItem: function(arr, arrKey) {
            return arr.filter((element) => element.key !== arrKey);
        },
        isWebSymbol(symbol) {
            return webSymbol === symbol;
        },
        toMoneyWithTrailingZeroes: function(val) {
            return toMoneyWithTrailingZeroes(val, this.priceSubunits);
        },
    },
    watch: {
        async changingMarket() {
            if (this.changingMarket) {
                this.tableData = null;

                return;
            }
            await this.updateTableData();
            this.updateAndParseWsDeals();
        },
        market: function() {
            this.tooltipKey += 1;
        },
    },
    filters: {
        currencyConvert: function(val, rate, subunit) {
            return this.currencyConversion(removeSpaces(val), rate, usdSign, subunit, this.market.base.subunit);
        },
    },
};
</script>
