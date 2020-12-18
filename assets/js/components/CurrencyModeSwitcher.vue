<template>
    <b-dropdown
        id="currency"
        variant="primary"
        class="d-inline currency-mode-switcher"
        :lazy="true"
        dropup
    >
        <template slot="button-content">
            <span
                v-html="selectedCurrency ? selectedCurrency : this.$t('trading.currency.' + currencyMode)">
            >
            </span>
        </template>
        <template>
            <b-dropdown-item @click="changeCurrencyMode(currencyModes.crypto.value)">
                {{ $t('trading.currency.crypto') }}
            </b-dropdown-item>
            <b-dropdown-item class="usdOption" @click="changeCurrencyMode(currencyModes.usd.value)">
                {{ $t('trading.currency.usd') }}
            </b-dropdown-item>
        </template>
    </b-dropdown>
</template>

<script>

import {currencyModes} from '../utils/constants';

export default {
    name: 'CurrencyModeSwitcher',
    data() {
        return {
            selectedCurrency: '',
            currencyModes,
        };
    },
    created() {
        if (null === localStorage.getItem('_currency_mode')) {
            localStorage.setItem('_currency_mode', this.currencyModes.crypto.value);
        }
    },
    methods: {
        changeCurrencyMode: function(mode) {
            this.toggleCurrency(mode);
            localStorage.setItem('_currency_mode', this.currencyModes[mode].value);
            location.reload();
        },
        toggleCurrency: function(mode) {
            this.selectedCurrency = currencyModes[mode].text;
        },
    },
    computed: {
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
    },
};
</script>
