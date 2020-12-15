<template>
    <b-dropdown
        id="currency"
        variant="primary"
        class="dropup d-inline"
        :lazy="true"
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
import {HTTP_ACCEPTED, currencyModes} from '../utils/constants';
import {mapMutations, mapGetters} from 'vuex';

export default {
    name: 'CurrencyModeSwitcher',
    props: {
        currentCurrencyMode: String,
    },
    data() {
        return {
            selectedCurrency: '',
            currencyModes: Object.freeze(currencyModes),
        };
    },
    mounted() {
            console.log(Document.);
        //this.globalCurrencyMode(this.currentCurrencyMode);
        //console.log(this.globalCurrencyMode);
      //  this.updateCurrencyMode(this.currentCurrencyMode);
    },
    methods: {
/*        updateCurrencyMode: function(mode) {
            this.globalCurrencyMode(mode);
        },*/
        changeCurrencyMode: function(mode) {
           // console.log(this.globalCurrencyMode);
            this.toggleCurrency(mode);
            this.$axios.single.post(this.$routing.generate('change_currency_mode', {
                mode,
            }))
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        location.reload();
                    } else {
                        this.$toasted.error(this.$t('toasted.error.try_later'));
                    }
                }, (error) => {
                    this.$toasted.error(this.$t('toasted.error.try_later'));
                });
        },
        toggleCurrency: function(mode) {
            this.selectedCurrency = currencyModes[mode].text;
        },
        ...mapMutations('rates', [
            'setGlobalCurrencyMode',
        ]),
    },
    computed: {
        currencyMode: function() {
            return this.currentCurrencyMode;
        },
        ...mapGetters('rates', [
            'getGlobalCurrencyMode',
        ]),
        globalCurrencyMode: {
            get() {
                return this.getGlobalCurrencyMode;
            },
            set(val) {
                this.setGlobalCurrencyMode(val);
            },
        },
    },
};
</script>
