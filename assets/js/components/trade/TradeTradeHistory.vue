<template>
    <div>
        <div class="card">
            <div class="card-header">
                {{ $t('trade.history.header') }}
                <span class="card-header-icon">
                    <guide>
                        <template slot="header">
                            {{ $t('trade.history.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('trade.history.guide_body', {baseSymbol: market.base.symbol}) | rebranding }}
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fixed-head-table mb-0" ref="tableData">
                    <template v-if="loaded">
                        <b-table
                            v-if="hasOrders"
                            class="w-100"
                            ref="table"
                            :items="ordersList"
                            :fields="fields">

                            <template v-slot:head(pricePerQuote)>
                                <span v-if="shouldTruncate" v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'viewport'}">
                                    {{ $t('trade.history.price_per') }} {{ market.quote | rebranding | truncate(maxLengthToTruncate) }}
                                </span>
                                <span v-else>
                                    {{ $t('trade.history.price_per') }} {{ market.quote | rebranding }}
                                </span>
                            </template>

                            <template v-slot:head(quoteAmount)>
                                <span v-if="shouldTruncate" v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'viewport'}">
                                    {{ market.quote | rebranding | truncate(maxLengthToTruncate) }} {{ $t('trade.history.amount') }}
                                </span>
                                 <span v-else>
                                    {{ market.quote | rebranding }} {{ $t('trade.history.amount') }}
                                </span>
                            </template>

                            <template v-slot:cell(orderMaker)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <a :href="row.item.makerUrl" class="d-flex flex-row flex-nowrap justify-content-between w-100 text-white">
                                        <img
                                            :src="row.item.makerAvatar"
                                            class="rounded-circle d-block flex-grow-0 mr-1"
                                            :alt="$t('avatar')">
                                        <span class="d-inline-block truncate-name flex-grow-1">
                                            <span v-b-tooltip="{title: row.value, boundary:'viewport'}">
                                                {{ row.value }}
                                            </span>
                                        </span>
                                    </a>
                                    <a v-if="row.item.owner" class="d-inline-block flex-grow-0" @click="removeOrderModal(row.item)">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                    </a>
                                </div>
                            </template>
                            <template v-slot:cell(orderTrader)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <a :href="row.item.takerUrl" class="d-flex flex-row flex-nowrap justify-content-between w-100 text-white">
                                        <img
                                            :src="row.item.takerAvatar"
                                            class="rounded-circle d-block flex-grow-0 mr-1"
                                            alt="avatar">
                                        <span class="d-inline-block truncate-name flex-grow-1">
                                            <span v-b-tooltip="{title: row.value, boundary:'viewport'}"
                                            >
                                                {{ row.value }}
                                            </span>
                                        </span>
                                    </a>
                                    <a v-if="row.item.owner" class="d-inline-block flex-grow-0" @click="removeOrderModal(row.item)">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                    </a>
                                </div>
                            </template>
                            <template v-slot:cell(pricePerQuote)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <div class="col-11 pl-0 ml-0">
                                        <span class="d-inline-block truncate-name flex-grow-1">
                                            <span
                                                v-b-tooltip="{title: currencyConvert(row.value, rate, 2), boundary:'viewport'}">
                                                {{ row.value }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:cell(baseAmount)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <div class="col-11 pl-0 ml-0">
                                        <span
                                            class="d-inline-block truncate-name flex-grow-1"
                                            v-text="currencyMode === currencyModes.usd.value ?
                                                currencyConvert(row.value, rate, 2) :
                                                row.value">
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:cell(dateTime)="row">
                                <span class="truncate-name" v-b-tooltip="{title: row.value, boundary:'viewport'}">
                                    {{ row.value | truncate(11) }}
                                </span>
                            </template>
                        </b-table>
                        <div v-if="!hasOrders">
                            <p class="text-center p-5">{{ $t('trade.history.no_deals') }}</p>
                        </div>
                        <div v-if="loading" class="p-1 text-center">
                            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                        </div>
                    </template>
                    <template v-else>
                        <div class="p-5 text-center">
                            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Guide from '../Guide';
import {formatMoney, toMoney, removeSpaces, currencyConversion} from '../../utils';
import {mapGetters} from 'vuex';
import {USD, usdSign, currencyModes} from '../../utils/constants.js';
import Decimal from 'decimal.js';
import {GENERAL} from '../../utils/constants';
import {
    WebSocketMixin,
    FiltersMixin,
    LazyScrollTableMixin,
    RebrandingFilterMixin,
    OrderMixin,
} from '../../mixins/';

export default {
    name: 'TradeTradeHistory',
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        LazyScrollTableMixin,
        RebrandingFilterMixin,
        OrderMixin,
    ],
    props: {
        market: Object,
        currencyMode: String,
    },
    components: {
        Guide,
    },
    data() {
        return {
            maxLengthToTruncate: 4,
            currencyModes,
            fields: [
                {
                    key: 'type',
                    label: this.$t('trade.history.type'),
                },
                {
                    key: 'orderMaker',
                    label: this.$t('trade.history.order_maker'),
                },
                {
                    key: 'orderTrader',
                    label: this.$t('trade.history.order_taker'),
                },
                {
                    key: 'pricePerQuote',
                    formatter: formatMoney,
                },
                {
                    key: 'quoteAmount',
                    formatter: formatMoney,
                },
                {
                    key: 'baseAmount',
                    label: this.$t('trade.orders.sum'),
                    formatter: formatMoney,
                },
                {
                    key: 'dateTime',
                    label: this.$t('trade.history.time'),
                },
            ],
        };
    },
    computed: {
        ...mapGetters('rates', [
            'getRates',
        ]),
        shouldTruncate: function() {
            return this.market.quote.symbol.length > this.maxLengthToTruncate;
        },
        hasOrders: function() {
            return this.ordersList.length > 0;
        },
        ordersList: function() {
            return this.tableData !== false ? this.tableData.filter((order) => order.maker && order.taker)
                .map((order) => {
                    return {
                        dateTime: moment.unix(order.timestamp).format(GENERAL.dateFormat),
                        orderMaker: order.maker.profile.nickname,
                        orderTrader: order.taker.profile.nickname,
                        makerUrl: this.$routing.generate('profile-view', {nickname: order.maker.profile.nickname}),
                        takerUrl: this.$routing.generate('profile-view', {nickname: order.taker.profile.nickname}),
                        type: this.getSideByType(order.side),
                        pricePerQuote: toMoney(order.price, this.market.base.subunit),
                        quoteAmount: toMoney(order.amount, this.market.quote.subunit),
                        baseAmount: toMoney(
                            new Decimal(order.price).mul(order.amount).toString(),
                            this.market.base.subunit
                        ),
                        makerAvatar: order.maker.profile.image.avatar_small,
                        takerAvatar: order.taker.profile.image.avatar_small,
                    };
                }) : [];
        },
        loaded: function() {
            return this.tableData !== null;
        },
        lastId: function() {
            return this.tableData && this.tableData[0] && this.tableData[0].hasOwnProperty('id') ?
                this.tableData[0].id :
                0;
        },
        rate: function() {
            return (this.getRates[this.market.base.symbol] || [])[USD.symbol] || 1;
        },
    },
    mounted: function() {
        this.updateTableData().then((res) => {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((response) => {
                if ('deals.update' === response.method) {
                    const orders = response.params[1];

                    if (orders.length !== 1) {
                        return;
                    }

                    this.$axios.retry.get(this.$routing.generate('executed_order_details', {
                        base: this.market.base.symbol,
                        quote: this.market.quote.symbol,
                        id: parseInt(orders[0].id),
                    })).then((res) => {
                        if (this.tableData.findIndex((item) => item.id === res.data.id) === -1) {
                            this.tableData.unshift(res.data);
                        }

                        this.startScrollListeningOnce(this.ordersList);
                    }).catch((err) => {
                        this.sendLogs('error', 'Can not get executed order details', err);
                    });
                }
            }, 'trade-tableData-update-deals', 'TradeTradeHistory');
        }).catch((err) => {
            this.sendLogs('error', 'Can not update table data', err);
        });
    },
    methods: {
        updateTableData: function(attach = false) {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('executed_orders', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                    id: this.lastId,
                })).then((result) => {
                    if (!result.data.length) {
                        if (!attach) {
                            this.tableData = result.data;
                        }

                        return resolve([]);
                    }

                    this.tableData = !attach ? result.data : this.tableData.concat(result.data);

                    if (this.$refs.table) {
                        this.$refs.table.hasOwnProperty('refresh') ? this.$refs.table.refresh() : null;
                    }

                    resolve(result.data);
                }).catch(reject);
            });
        },
        currencyConvert: function(val, rate, subunit) {
            return currencyConversion(removeSpaces(val), rate, usdSign, subunit);
        },
    },
    filters: {
        currencyConvert: function(val, rate, subunit) {
            return currencyConversion(removeSpaces(val), rate, usdSign, subunit);
        },
    },
};
</script>
