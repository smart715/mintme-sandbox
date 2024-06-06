<template>
    <div class="card token-price-overview p-3">
        <div
            class="font-size-3 font-weight-semibold header-highlighting"
            v-html="$t('page.pair.overview_chart_title')"
        ></div>
        <div class="period-switcher d-flex align-items-center justify-content-end">
            <div
                class="px-2 py-1 ml-3"
                :class="periodClass(PERIODS.WEEK)"
                @click="setPeriod(PERIODS.WEEK)"
            >
                {{ $t('page.pair.overview_chart.one_week') }}
            </div>
            <div
                class="px-2 py-1 ml-3"
                :class="periodClass(PERIODS.MONTH)"
                @click="setPeriod(PERIODS.MONTH)"
            >
                {{ $t('page.pair.overview_chart.one_month') }}
            </div>
            <div
                class="px-2 py-1 ml-3"
                :class="periodClass(PERIODS.HALF_YEAR)"
                @click="setPeriod(PERIODS.HALF_YEAR)"
            >
                {{ $t('page.pair.overview_chart.half_year') }}
            </div>
        </div>
        <div v-if="loading" class="d-flex align-items-center justify-content-center chart-spinner">
            <div class="spinner-border spinner-border-sm my-1" role="status"></div>
        </div>
        <div v-else-if="serviceUnavailable" class="d-flex align-items-center justify-content-center chart-spinner">
            {{ this.$t('toasted.error.service_unavailable_short') }}
        </div>
        <div v-if="noData" class="d-flex align-items-center justify-content-center chart-spinner">
            {{ $t('page.pair.overview_chart.no_data') }}
        </div>
        <line-chart
            v-if="!loading && !noData && dataCollection"
            class="overview_chart"
            :chart-data="dataCollection"
            :chart-options="chartOptions"
        />
    </div>
</template>

<script>
import moment from 'moment';
import LineChart from '../UI/charts/LineChart';
import Decimal from 'decimal.js';
import {NotificationMixin} from '../../mixins';

const PERIODS = {
    WEEK: 'week',
    MONTH: 'month',
    HALF_YEAR: 'half_year',
};

export default {
    name: 'TokenPriceOverviewChart',
    mixins: [NotificationMixin],
    components: {
        LineChart,
    },
    props: {
        currentMarket: Object,
    },
    data() {
        return {
            PERIODS,
            stats: [],
            activePeriod: PERIODS.WEEK,
            serviceUnavailable: false,
            dataCollection: null,
            chartLabels: [],
            chartStats: [],
            loading: false,
            noData: false,
            chartOptions: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        display: false,
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            fontColor: 'white',
                            fontSize: 14,
                        },
                    },
                },
            },
        };
    },
    created() {
        this.setPeriod(PERIODS.WEEK);
    },
    methods: {
        setPeriodLabel() {
            const labels = [];
            if (this.activePeriod === PERIODS.HALF_YEAR) {
                for (let i = 0; 6 > i; i++) {
                    labels.push(moment().subtract(i, 'month').format('MMM'));
                }
            } else if (this.activePeriod === PERIODS.MONTH) {
                for (let i = 0; 6 > i; i++) {
                    labels.push(moment().subtract(i * 5, 'days').format('DD.MM'));
                }
            } else if (this.activePeriod === PERIODS.WEEK) {
                for (let i = 0; 7 > i; i++) {
                    labels.push(moment().subtract(i, 'days').format('DD.MM'));
                }
            }

            this.chartLabels = labels.reverse();
        },
        setPeriodStats(statsData) {
            let stats = [];
            if (this.activePeriod === PERIODS.HALF_YEAR) {
                stats = this.generateStatsPerMonth(6, statsData);
            } else if (this.activePeriod === PERIODS.MONTH) {
                stats = this.generateStatsPerInterval(6, 5, statsData);
            } else if (this.activePeriod === PERIODS.WEEK) {
                stats = this.generateStatsPerInterval(7, 1, statsData);
            }

            this.chartStats = stats;
        },
        generateStatsPerInterval(colsAmount, intervalDays, statsData) {
            const stats = new Array(colsAmount).fill(new Decimal(0));
            const divideNum = new Array(colsAmount).fill(0);

            for (const stat of statsData) {
                const [date, price] = [moment.utc(stat.time * 1000), stat.close];
                const daysAway = moment().diff(date, 'days');
                const idx = (colsAmount - 1) - Math.floor(daysAway / intervalDays);
                if (0 <= idx) {
                    stats[idx] = stats[idx].add(price);
                    divideNum[idx]++;
                }
            }

            return stats
                .map((stat, idx) => 0 < divideNum[idx] ? stat.dividedBy(divideNum[idx]) : new Decimal(0))
                .map((stat) => stat.toNumber());
        },
        generateStatsPerMonth(months, statsData) {
            const stats = {};
            const divideNum = {};

            for (const stat of statsData) {
                const [date, price] = [moment.utc(stat.time * 1000), stat.close];
                const month = date.month();
                stats[month] = stats[month] ? stats[month].add(price) : new Decimal(price);
                divideNum[month] = divideNum[month] ? divideNum[month] + 1 : 1;
            }

            return Array(months).fill(0).map((_, i) => {
                const month = moment().subtract(i * 30, 'days').month();
                return stats[month] ? stats[month].dividedBy(divideNum[month]).toNumber() : 0;
            }).reverse();
        },
        updateChart(stats) {
            this.setPeriodLabel();
            this.setPeriodStats(stats);
            this.dataCollection = {
                labels: this.chartLabels,
                datasets: [{
                    data: this.chartStats,
                    fill: true,
                    backgroundColor: 'rgba(208, 175, 33, 0.2)',
                    borderColor: '#D0AF21',
                    tension: 0.4,
                    pointBorderWidth: 0,
                    pointBorderColor: 'transparent',
                    pointBackgroundColor: 'transparent',
                }],
            };
        },
        setPeriod(period) {
            if (this.loading || this.serviceUnavailable) {
                return;
            }

            this.activePeriod = period;
            this.loading = true;

            this.$axios.retry.get(this.$routing.generate('market_kline', {
                base: this.currentMarket.base.symbol,
                quote: this.currentMarket.quote.symbol,
            }), {params: {period: this.activePeriod}})
                .then((res) => {
                    if (!res.data) {
                        this.noData = true;
                        return;
                    }

                    this.stats = res.data;
                    this.updateChart(res.data);
                })
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                    this.$logger.error('Can not load the chart data', err);
                    this.serviceUnavailable = true;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        periodClass(period) {
            return {
                'active': period === this.activePeriod,
            };
        },
    },
};

</script>
