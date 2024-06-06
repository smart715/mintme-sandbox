import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';

const PRECISION_INPUT = 4;

export default {
    methods: {
        ...mapMutations('tradeBalance', [
            'setInputFlags',
            'removeInputFlag',
        ]),
        isZero(amount) {
            return new Decimal(amount || 0).isZero();
        },
        getNewAmount(totalPrice, price, subunit) {
            if (this.isZero(price)) {
                return '0';
            }

            const amount = this.getNewValue(totalPrice, price, subunit);

            return isNaN(amount)
                ? '0'
                : amount;
        },
        getNewPrice(totalPrice, amount, subunit) {
            if (this.isZero(amount)) {
                return '0';
            }

            const price = this.getNewValue(totalPrice, amount, subunit);

            return isNaN(price)
                ? '0'
                : price;
        },
        getTotalPrice(price, amount, subunit) {
            return price && amount
                ? new Decimal(price || 0)
                    .mul(amount || 0)
                    .toDP(subunit, Decimal.ROUND_HALF_UP)
                    .toFixed()
                : '';
        },
        getNewValue(value, balance, subunit = PRECISION_INPUT) {
            if (this.isZero(balance)) {
                return '0';
            }

            return new Decimal(value || 0).div(balance)
                .toDP(subunit, Decimal.ROUND_HALF_UP)
                .toString();
        },
        setInputFlag(flag) {
            if (!this.inputFlags.includes(flag)) {
                this.inputFlags.push(flag);
            }
        },
        hasInputFlag(flag) {
            return this.inputFlags.includes(flag);
        },
        syncInputPriceFlag(price, amount, flags) {
            if (this.isZero(price) && !this.hasInputFlag(flags.amount) && !this.hasInputFlag(flags.totalPrice)) {
                this.removeInputFlag(flags.price);
                return;
            }

            if (this.hasInputFlag(flags.amount) && this.isZero(amount)) {
                this.removeInputFlag(flags.amount);
            }

            if (
                this.hasInputFlag(flags.amount) &&
                this.hasInputFlag(flags.totalPrice)
            ) {
                this.removeInputFlag(flags.totalPrice);
            }

            this.setInputFlag(flags.price);
        },
        syncInputAmountFlag(price, amount, flags) {
            if (this.isZero(amount) && !this.hasInputFlag(flags.price) && !this.hasInputFlag(flags.totalPrice)) {
                this.removeInputFlag(flags.amount);
                return;
            }

            if (this.hasInputFlag(flags.price) && this.isZero(price)) {
                this.removeInputFlag(flags.price);
            }

            if (
                this.hasInputFlag(flags.price) &&
                this.hasInputFlag(flags.totalPrice)
            ) {
                this.removeInputFlag(flags.totalPrice);
            }

            this.setInputFlag(flags.amount);
        },
        syncInputTotalPriceFlag(price, amount, totalPrice, flags) {
            if (this.isZero(totalPrice) && !this.hasInputFlag(flags.price) && !this.hasInputFlag(flags.amount)) {
                this.removeInputFlag(flags.totalPrice);
                return;
            }

            if (
                this.hasInputFlag(flags.price) && !this.isZero(price) &&
                this.hasInputFlag(flags.amount) && !this.isZero(amount)
            ) {
                this.removeInputFlag(flags.amount);
            }

            if (
                this.hasInputFlag(flags.price) && !this.isZero(price) &&
                this.hasInputFlag(flags.amount) && this.isZero(amount)
            ) {
                this.removeInputFlag(flags.amount);
            }

            this.setInputFlag(flags.totalPrice);
        },
    },
    computed: {
        ...mapGetters('tradeBalance', [
            'getInputFlags',
        ]),
        inputFlags: {
            get() {
                return this.getInputFlags;
            },
            set(value) {
                this.setInputFlag(value);
            },
        },
    },
};
