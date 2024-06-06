<template>
    <div
        v-if="!showUsd"
        class="d-inline-flex line-height-1 align-items-center"
        v-b-tooltip.hover
        :title="getTitleWithCrypto(value)"
    >
        <span>
            {{ getDisplayValue(value) }}
        </span>
        <img
            :src="cryptoImg(symbol)"
            class="ml-1 rounded-circle"
            :class="coinAvatarClass"
        />
    </div>
    <div
        v-else
        class="d-inline-flex line-height-1 align-items-center"
        v-b-tooltip.hover
        :title="getTitleWithUsd(valueUsd)"
    >
        <font-awesome-icon icon="dollar-sign" transform="up-1 left-3" />
        <span>
            {{ getDisplayValue(valueUsd) }}
        </span>
    </div>
</template>

<script>
import {faDollarSign} from '@fortawesome/free-solid-svg-icons';
import {NumberAbbreviationFilterMixin} from '../../mixins';
import Decimal from 'decimal.js';
import {MAX_NUMBER_1K, GENERAL} from '../../utils/constants';
import {getCoinAvatarAssetName, getPriceAbbreviation} from '../../utils';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {VBTooltip} from 'bootstrap-vue';

library.add(faDollarSign);

export default {
    name: 'TableNumericValue',
    components: {
        FontAwesomeIcon,
    },
    mixins: [
        NumberAbbreviationFilterMixin,
    ],
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        showUsd: {
            type: Boolean,
            default: true,
        },
        value: String,
        valueUsd: String,
        symbol: String,
        coinAvatarClass: String,
        subunit: Number,
        valueAbbreviation: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        showPriceAbbreviation() {
            return this.valueAbbreviation && this.subunit > GENERAL.precision;
        },
    },
    methods: {
        getDisplayValue(value) {
            return this.showPriceAbbreviation
                ? getPriceAbbreviation(value)
                : this.numberTruncateWithLetterFunc(value);
        },
        getTitleWithCrypto(number) {
            if (number > MAX_NUMBER_1K) {
                return `${new Decimal(number).toDP(0)} ${this.symbol}`;
            }

            return this.showPriceAbbreviation
                ? `${number} ${this.symbol}`
                : '';
        },
        getTitleWithUsd(number) {
            if (number > MAX_NUMBER_1K) {
                return `${new Decimal(number).toDP(0)} USD`;
            }

            return this.showPriceAbbreviation
                ? `$ ${number}`
                :'';
        },
        cryptoImg: function(symbol) {
            return require(`../../../img/${getCoinAvatarAssetName(symbol)}`);
        },
    },
};
</script>
