<template>
    <div class="row">
        <div class="col-12 mb-4 px-4">
            <template v-if="marketsOnTopIsLoaded">
                <div class="row justify-content-center">
                    <div v-for="(market) in this.sanitizedMarketsOnTop"
                        :key="market.pair"
                        class="col-12 col-sm-12 col-md-6 chart-markets-xxl p-0"
                    >
                        <div
                            class="card-charts-markets mx-2 my-2 rounded"
                        >
                            <div class="card-header">
                                <a :href="rebrandingFunc(market.tokenUrl)">
                                    <div class="row d-flex justify-content-center align-items-center">
                                        <div class="market-icon col-2">
                                            <img
                                                class="svg-markets"
                                                :src="require('../../../img/' + market.base + '.svg')"
                                            />
                                        </div>
                                        <div class="market-name col-6">
                                            <div class="row pl-3">
                                                <div class="col-12 px-0">
                                                    <span class="h6 font-weight-semibold">
                                                        {{ market.pair | rebranding }}
                                                    </span>
                                                </div>
                                                <div class="col-12 px-0">
                                                    <table-numeric-value
                                                        :value="market.lastPrice | formatMoney"
                                                        :value-usd="market.lastPriceUSD | formatMoney"
                                                        :symbol="market.base"
                                                        coin-avatar-class="coin-avatar-sm"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="market-badge col-4 text-right">
                                            <div
                                                v-if="parseFloat(market.change) > 0"
                                                class="text-center text-white badge rounded-change bg-success py-2"
                                            >
                                                &#9650;+{{ market.change }}
                                            </div>
                                            <div
                                                v-else-if="parseFloat(market.change) < 0"
                                                class="text-center text-white badge
                                                    custom-badge-danger rounded-change py-2"
                                            >
                                                &#9660;{{ market.change }}
                                            </div>
                                            <div
                                                v-else
                                                class="text-center text-white badge rounded-change p-2"
                                            >
                                                {{ market.change }}
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div
                                class="d-none d-sm-block card-body c-pointer"
                                @click="redirectToMarket(market)"
                            >
                                <charts-markets-trading
                                    class="charts-markets"
                                    :chart-data="fillDataCharts(market.change)"
                                    :chart-options="chartOptions"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="d-flex justify-content-center mt-4 mb-4">
                    <div class="spinner-border text-light" role="status">
                        <span class="sr-only">
                            {{ $t('page.trading.loading') }}
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import {
    RebrandingFilterMixin,
    MoneyFilterMixin,
} from '../../mixins/';
import {
    CHART_DEFAULT_DUMMY_DATA,
    CHART_NEGATIVE_DUMMY_DATA,
    CHART_POSITIVE_DUMMY_DATA,
} from '../../utils/constants';
import ChartsMarketsTrading from '../UI/charts/LineChart';
import TableNumericValue from './TableNumericValue';

export default {
    name: 'ChartsMarkets',
    components: {
        ChartsMarketsTrading,
        TableNumericValue,
    },
    mixins: [
        RebrandingFilterMixin,
        MoneyFilterMixin,
    ],
    props: {
        sanitizedMarketsOnTop: Array,
    },
    data: () => ({
        chartOptions: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    display: false,
                },
                x: {
                    display: false,
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    enabled: false,
                },
            },
        },
    }),
    computed: {
        marketsOnTopIsLoaded: function() {
            return this.sanitizedMarketsOnTop.length;
        },
    },
    methods: {
        redirectToMarket(market) {
            window.location.href = this.$routing.generate('coin', {
                base: this.rebrandingFunc(market.base),
                quote: this.rebrandingFunc(market.quote),
            });
        },
        fillDataCharts(change) {
            const floatChange = parseFloat(change);
            const dataCollection = {
                labels: this.checkLabels(floatChange),
                datasets: [{
                    data: this.checkData(floatChange),
                    fill: false,
                    borderColor: this.checkBorderColor(floatChange),
                    tension: 0.4,
                    pointBorderColor: 'transparent',
                    pointBackgroundColor: 'transparent',
                }],
            };

            return dataCollection;
        },
        checkLabels: function(floatChange) {
            if (0 === floatChange) {
                return CHART_DEFAULT_DUMMY_DATA.labels;
            }

            return 0 < floatChange
                ? CHART_POSITIVE_DUMMY_DATA.labels
                : CHART_NEGATIVE_DUMMY_DATA.labels;
        },
        checkData: function(floatChange) {
            if (0 === floatChange) {
                return CHART_DEFAULT_DUMMY_DATA.data;
            }

            return 0 < floatChange
                ? CHART_POSITIVE_DUMMY_DATA.data
                : CHART_NEGATIVE_DUMMY_DATA.data;
        },
        checkBorderColor: function(floatChange) {
            return 0 > floatChange
                ? CHART_NEGATIVE_DUMMY_DATA.borderColor
                : CHART_POSITIVE_DUMMY_DATA.borderColor;
        },
    },
};
</script>
