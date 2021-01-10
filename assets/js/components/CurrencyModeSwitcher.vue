<template>
    <div
        class="dropdown d-inline currency-mode-switcher dropup btn-group"
        :class="{ show }"
        v-on-clickaway="hide"
    >
        <button
            id="currency-mode-switcher-button"
            class="btn dropdown-toggle btn-primary"
            type="button"
            aria-haspopup="true"
            :aria-expanded="show"
            @click="toggle"
        >
            <span
                v-html="selectedCurrency ? selectedCurrency : this.$t('trading.currency.' + currencyMode)"
            ></span>
        </button>
        <ul
            role="menu"
            tabindex="-1"
            aria-labelledby="currency-mode-switcher-button"
            class="dropdown-menu"
            :class="{ show }"
        >
            <li role="presentation" @click="changeCurrencyMode(currencyModes.crypto.value)">
                <a role="menuItem" href="#" target="_self" class="dropdown-item">
                    {{ $t('trading.currency.crypto') }}
                </a>
            </li>
            <li role="presentation" class="usdOption" @click="changeCurrencyMode(currencyModes.usd.value)">
                <a role="menuItem" href="#" target="_self" class="dropdown-item">
                    {{ $t('trading.currency.usd') }}
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
import {directive as onClickaway} from 'vue-clickaway';
import {currencyModes} from '../utils/constants';

export default {
    name: 'CurrencyModeSwitcher',
    directives: {
        onClickaway,
    },
    data() {
        return {
            selectedCurrency: '',
            currencyModes,
            show: false,
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
        toggle() {
            this.show = !this.show;
        },
        hide() {
            this.show = false;
        },
    },
    computed: {
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
    },
};
</script>
