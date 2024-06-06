<template>
    <span v-if="balance">
        {{ balance }}
    </span>
    <span v-else-if="serviceUnavailable" class="text-danger mr-2">
        [{{ $t('toasted.error.service_unavailable_short') }}]
    </span>
    <span v-else class="spinner-border spinner-border-sm">
        <span class="sr-only"> {{ $t('loading') }} </span>
    </span>
</template>

<script>
import Decimal from 'decimal.js';
import {toMoney} from '../utils';
import {mapGetters} from 'vuex';

export default {
    name: 'CoinBalance',
    props: {
        coinName: String,
        withBonus: Boolean,
    },
    data() {
        return {
            balance: null,
            unavailableEmited: false,
        };
    },
    mounted() {
        this.updateBalance();
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
    },
    watch: {
        balances() {
            this.updateBalance();
        },
        coinName() {
            this.updateBalance();
        },
        withBonus() {
            this.updateBalance();
        },
        serviceUnavailable(currentState) {
            if (currentState) {
                this.emitUnavailable();
            }
        },
    },
    methods: {
        updateBalance() {
            if (!this.balances || !this.balances[this.coinName]) {
                this.balance = null;

                if (this.serviceUnavailable) {
                    this.emitUnavailable();
                }

                return;
            }

            let balance = new Decimal(this.balances[this.coinName].available);

            if (this.withBonus) {
                balance = balance.plus(new Decimal(this.balances[this.coinName].bonus || '0'));
            }

            this.balance = toMoney(balance);
        },
        emitUnavailable() {
            if (!this.unavailableEmited) {
                this.$emit('unavailable');
                this.unavailableEmited = true;
            }
        },
    },
};
</script>
