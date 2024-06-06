<template>
    <div class="intro w-100 py-5 px-3">
        <div class="dotted-map container">
            <div class="row pb-5 mx-0 align-items-center">
                <div class="col-12 col-xl-6 pt-5 pb-2 pt-xl-0 text-center">
                    <img
                        class="w-100 h-auto mintme-logo"
                        src="../../../img/logo-coin-white-v2.svg"
                        alt="mintme-logo"
                        loading="lazy"
                    />
                </div>
                <div class="col-12 col-xl-6 pb-5 pt-2 pb-xl-0">
                    <div class="text mx-auto">
                        <span>
                            {{ $t('page.coin.intro.main_text') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="numbers container">
            <div class="row mx-0">
                <div class="col-12 col-md-6 col-xl-3 py-4 px-2">
                    <div class="number mx-auto">
                        <span class="text-primary d-block">
                            {{ stats.totalUsersRegistered | numberFormat }}
                        </span>
                        <span class="text-uppercase d-block">
                            {{ $t('page.coin.intro.number.wallets_created') }}
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 py-4 px-2">
                    <div class="number mx-auto">
                        <span class="text-primary d-block">
                            {{ stats.totalTransactions | numberFormat }}
                        </span>
                        <span class="text-uppercase d-block">
                            {{ $t('page.coin.intro.number.transactions') }}
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 py-4 px-2">
                    <div class="number mx-auto">
                        <span class="text-primary d-block">
                            {{ stats.totalNetworkHashrate | hashFormat }}
                        </span>
                        <span class="text-uppercase d-block">
                            {{ $t('page.coin.intro.number.current_hashrate') }}
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 py-4 px-2">
                    <div class="number mx-auto">
                        <span class="text-primary d-block">
                            {{ stats.totalWallets | numberFormat }}
                        </span>
                        <span class="text-uppercase d-block">
                            {{ $t('page.coin.intro.number.users_mining') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import hash from '../../mixins/filters/hash';
import number from '../../mixins/filters/number';

export default {
    name: 'Intro',
    data() {
        return {
            stats: {
                totalUsersRegistered: 0,
                totalNetworkHashrate: 0,
                totalWallets: 0,
                totalTransactions: 0,
            },
        };
    },
    mounted: function() {
        this.loadStats();
    },
    methods: {
        loadStats: function() {
            this.loadTotalUsersRegistered();
            this.loadTotalWalletsAndTransactions();
            this.loadTotalNetworkHashrate();
        },
        loadTotalUsersRegistered: async function() {
            try {
                const response = await this.$axios.retry.get(this.$routing.generate('get_total_users_registered'));
                this.stats.totalUsersRegistered = response.data.count;
            } catch (err) {
                this.$logger.error('Can not connect to internal services', err);
            }
        },
        loadTotalWalletsAndTransactions: async function() {
            try {
                const response = await this.$axios.retry.get(
                    this.$routing.generate('get_total_wallets_and_transactions')
                );

                this.stats.totalWallets = response.data.addresses;
                this.stats.totalTransactions = response.data.transactions;
            } catch (err) {
                this.$logger.error('Can not connect to internal services', err);
            }
        },
        loadTotalNetworkHashrate: async function() {
            try {
                const response = await this.$axios.retry.post(this.$routing.generate('get_total_network_hashrate'));
                this.stats.totalNetworkHashrate = response.data.hashrate.toFixed(2);
            } catch (err) {
                this.$logger.error('Can not connect to internal services', err);
            }
        },
    },
    filters: {
        numberFormat: function(value) {
            return number.numberFormat(value);
        },
        hashFormat: function(value) {
            return hash.hashFormat(value);
        },
    },
};
</script>
