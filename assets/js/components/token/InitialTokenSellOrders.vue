<template>
    <div>
        <b-row v-if="balanceLoaded && null !== showInitialOrdersForm">
            <template v-if="showInitialOrdersForm">
                <b-col cols="12">
                    <div class="col-12 pb-3 px-0">
                        <label class="d-block text-left">
                            {{ $t('token_init.create_orders.starting_price') }}
                            <guide>
                                <template slot="header">
                                    {{ $t('token_init.create_orders.tooltip.starting_price') }}
                                </template>
                            </guide>
                        </label>
                        <price-converter-input
                            id="startingPrice"
                            v-model="startingPriceModel"
                            input-id="buy-price-input"
                            @keypress="checkAmountInput"
                            @keyup="checkInputDot"
                            @paste="checkAmountInput"
                            @input="getPrice"
                            :disabled="noEnoughBalance"
                            :from="selectedCurrency"
                            :to="USD.symbol"
                            :subunit="4"
                            symbol="$"
                        />

                    </div>
                    <p v-if="noEnoughBalance" class="bg-danger text-white">
                        {{ $t('token_init.create_orders.no_enough_msg') }}
                        <b>{{ minTokenForSale | toMoney(tokSubunit) | formatMoney }}</b>
                        {{ $t('token_init.create_orders.no_enough_msg_2') }}
                        <b>{{ tokenBalance  | toMoney(tokSubunit) | formatMoney }}</b>.
                    </p>
                    <div class="w-100 mt-1 text-danger">
                        <div v-if="!$v.startingPriceModel.required">
                            {{ $t('token_init.starting_price.required') }}
                        </div>
                        <div v-if="!$v.startingPriceModel.decimal">
                            {{ $t('token_init.starting_price.numeric') }}
                        </div>
                        <div v-if="!$v.startingPriceModel.between">
                            {{ $t('token_init.starting_price.between', translationContext) }}
                        </div>
                    </div>

                    <b-row class="mx-1 my-2">
                        <label class="d-block text-left">
                            {{ $t('token_init.create_orders.tokens_for_sale') }}
                            <guide class="tooltip-center">
                                <template slot="header">
                                    {{ $t('token_init.create_orders.tooltip.tokens_for_sale') }}
                                </template>
                            </guide>
                        </label>
                        <b-col cols="2" class="text-center px-0">
                            <b>{{ $t('min') }}</b>
                        </b-col>
                        <b-col class="p-0">
                            <vue-slider
                                ref="refresh-growth-slider"
                                v-model="tokensForSale"
                                :min="minTokenForSale"
                                :max="maxTokenForSale"
                                :default="1000000"
                                :interval="1"
                                :tooltip="'none'"
                                width="100%"
                                :disabled="amountSliderLock"
                            />
                        </b-col>
                        <b-col cols="2" class="text-center px-0">
                            <b>{{ $t('max') }}</b>
                        </b-col>
                    </b-row>

                    <b-row class="mx-1 my-2">
                        <label class="d-block text-left">
                            {{ $t('token_init.create_orders.price_growth') }}
                            <guide>
                                <template slot="header">
                                    {{ $t('token_init.create_orders.tooltip.price_growth') }}
                                </template>
                            </guide>
                        </label>
                        <b-col cols="2" class="text-center px-0">
                            <b>{{ $t('min') }}</b>
                        </b-col>
                        <b-col class="p-0">
                            <vue-slider
                                ref="growth-slider"
                                v-model="priceGrowth"
                                :min="20"
                                :max="183"
                                :default="42"
                                :interval="1"
                                :tooltip="'none'"
                                width="100%"
                                :disabled="noEnoughBalance"
                            />
                        </b-col>
                        <b-col cols="2" class="text-center px-0">
                            <b>{{ $t('max') }}</b>
                        </b-col>
                    </b-row>
                    <div class="pt-3 text-xs">
                        {{ tokensForSale | toMoney(tokSubunit) | formatMoney }}
                        {{ $t('token_init.create_orders.desc_1') }}
                        {{ startingPriceModel || 0  | toMoney(tokSubunit) | formatMoney }}
                        {{ $t('token_init.create_orders.desc_to') }} {{ endPrice | toMoney(tokSubunit) | formatMoney }}
                        {{ $t('token_init.create_orders.desc_will_receive') }}
                        {{ mintmeAmountToReceive | toMoney(tokSubunit) | formatMoney }}
                        <coin-avatar
                            :is-crypto="true"
                            :symbol="webSymbol"
                        />
                        {{ $t('token_init.create_orders.desc_total') }}
                    </div>
                </b-col>
                <b-col cols="12" class="mt-3">
                    <div class="text-left">
                        <b-button
                            type="submit"
                            class="px-4 mr-1"
                            variant="primary"
                            :disabled="disableSaveButton"
                            @click="saveInitialOrders"
                        >
                            {{ $t('save') }}
                        </b-button>
                    </div>
                </b-col>
            </template>
            <template v-else>
                <b-col>
                    <b-button
                        type="submit"
                        class="px-4 mr-1 mt-2 btn-enabled-focus-light"
                        variant="primary"
                        @click="showConfirmModal = true"
                    >
                        {{ $t('token_init.create_orders.delete') }}
                    </b-button>
                </b-col>
                <confirm-modal
                    :visible="showConfirmModal"
                    type="delete"
                    :show-image="false"
                    @confirm="deleteInitialOrders"
                    @close="showConfirmModal = false"
                >
                    <p class="text-white modal-title pt-2">
                        {{ $t('token_init.create_orders.confirm_delete_msg') }}
                    </p>
                    <template v-slot:confirm>
                        {{ $t('token_init.create_orders.confirm_delete') }}
                    </template>
                </confirm-modal>
            </template>
        </b-row>
        <div v-else class="text-center pt-4">
            <span v-if="serviceUnavailable">
                {{ this.$t('toasted.error.service_unavailable_short') }}
            </span>
            <font-awesome-icon
                v-else
                icon="circle-notch"
                class="loading-spinner"
                fixed-width
                spin
            />
        </div>
    </div>
</template>

<script>
import ConfirmModal from '../modal/ConfirmModal';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import vueSlider from 'vue-slider-component';
import 'vue-slider-component/theme/default.css';
import {BRow, BCol, BButton} from 'bootstrap-vue';
import {NotificationMixin, CheckInputMixin, MoneyFilterMixin} from '../../mixins';
import {HTTP_OK, HTTP_CREATED, HTTP_ACCESS_DENIED, USD, TOK, webSymbol} from '../../utils/constants.js';
import PriceConverterInput from '../PriceConverterInput';
import {mapGetters} from 'vuex';
import Guide from '../Guide';
import CoinAvatar from '../CoinAvatar';
import {required, decimal, between} from 'vuelidate/lib/validators';

library.add(faCircleNotch);

export default {
    name: 'InitialTokenSellOrders',
    components: {
        ConfirmModal,
        Guide,
        BRow,
        BCol,
        BButton,
        vueSlider,
        FontAwesomeIcon,
        PriceConverterInput,
        CoinAvatar,
    },
    mixins: [
        NotificationMixin,
        CheckInputMixin,
        MoneyFilterMixin,
    ],
    props: {
        config: Object,
        tokenName: String,
    },
    data() {
        return {
            showConfirmModal: false,
            totalOrders: this.config.totalOrders,
            minTokenForSale: this.config.minTokenForSale,
            tokensForSale: this.config.maxTokenForSale,
            maxTokenForSale: this.config.maxTokenForSale,
            showInitialOrdersForm: null,
            tokSubunit: TOK.subunit,
            startingPriceModel: this.config.minTokensAmount,
            minStartingPrice: this.config.minTokensAmount,
            maxEndPrice: this.config.maxTokensAmount,
            mintmeAmountToReceive: '15000',
            orderServiceUnavailable: false,
            endPrice: 0,
            loading: false,
            priceGrowth: 42,
            USD,
            webSymbol,
        };
    },
    computed: {
        ...mapGetters('market', {
            selectedMarket: 'getCurrentMarket',
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            balanceServiceUnavailable: 'isServiceUnavailable',
        }),
        balanceLoaded: function() {
            return null !== this.balances;
        },
        tokenBalance: function() {
            return this.balanceLoaded
                ? this.balances[this.tokenName]?.available ?? '0'
                : '0';
        },
        serviceUnavailable: function() {
            return this.orderServiceUnavailable || this.balanceServiceUnavailable;
        },
        selectedCurrency: function() {
            return this.selectedMarket.base.symbol;
        },
        disableSaveButton: function() {
            return this.noEnoughBalance || this.loading || this.$v.startingPriceModel.$invalid;
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        noEnoughBalance: function() {
            if (this.balanceLoaded) {
                const balance = new Decimal(this.tokenBalance);

                return balance.lessThan(this.minTokenForSale);
            }

            return false;
        },
        amountSliderLock: function() {
            if (this.balanceLoaded) {
                const balance = new Decimal(this.tokenBalance);

                return balance.lessThanOrEqualTo(this.minTokenForSale);
            }

            return false;
        },
        translationContext: function() {
            return {
                tokenName: this.tokenName,
                minStartingPrice: this.minStartingPrice,
                maxEndPrice: this.maxEndPrice,
            };
        },
    },
    mounted: function() {
        this.existInitialOrders();
        this.getPrice();

        if (this.balanceLoaded) {
            this.setMinMaxTokensForSale();
        }
    },
    methods: {
        existInitialOrders: function() {
            this.loading = true;
            this.$axios.retry.get(this.$routing.generate('check_initial_orders', {
                tokenName: this.tokenName,
            }))
                .then((response) => {
                    this.showInitialOrdersForm = !response.data;
                    this.loading = false;
                })
                .catch((error) => {
                    this.$logger.error('Can not load token initial order data', error);
                    this.orderServiceUnavailable = true;
                });
        },
        deleteInitialOrders: function() {
            this.loading = true;
            this.showInitialOrdersForm = null;
            this.$axios.single.post(this.$routing.generate('delete_token_initial_orders', {
                tokenName: this.tokenName,
            }))
                .then(async (response) => {
                    if (response.status === HTTP_OK) {
                        this.loading = false;
                        this.showInitialOrdersForm = true;
                        this.notifySuccess(this.$t('token_init.order_deleted_msg'));
                    }
                })
                .catch((error) => {
                    this.loading = false;
                    if (HTTP_ACCESS_DENIED === error.response.status) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('An error has occurred, please try again later.');
                    }
                    this.$logger.error('Can not delete token initial orders data', error);
                });
        },
        saveInitialOrders: function() {
            this.loading = true;
            this.showInitialOrdersForm = null;
            const initTokenPrice = this.startingPriceModel.toString().replace(/^\./, '0.');
            const priceGrowth = String(this.priceGrowth);
            const tokensForSale = String(this.tokensForSale);
            this.$axios.retry.post(this.$routing.generate('token_initial_orders'), {
                initTokenPrice,
                priceGrowth,
                tokensForSale,
                tokenName: this.tokenName,
            })
                .then((response) => {
                    if (response.status === HTTP_CREATED) {
                        this.loading = false;
                        this.showInitialOrdersForm = false;
                        this.notifySuccess(this.$t('token_init.order_created_msg'));
                    }
                })
                .catch((error) => {
                    this.loading = false;
                    if (HTTP_ACCESS_DENIED === error.response.status) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('An error has occurred, please try again later.');
                    }
                    this.$logger.error('Can not save token initial orders', error);
                });
        },
        getPrice: function() {
            const startingPrice = this.startingPriceModel || 0;
            let sumMintme = 0;
            let endPrice = 0;
            let currentPrice = parseFloat(startingPrice);
            for (let i = 1; i <= this.totalOrders; i++) {
                if (1 === i) {
                    sumMintme += startingPrice * this.tokensForSale / this.totalOrders;
                    continue;
                }
                let price = startingPrice * Math.pow(1 + Number(this.priceGrowth) / 100, this.getBaseLog(2, i));

                if (price < currentPrice + Math.pow(10, -this.tokSubunit)) {
                    price = currentPrice + Math.pow(10, -this.tokSubunit);
                }

                currentPrice = price;
                sumMintme += price * this.tokensForSale / this.totalOrders;
                if (this.totalOrders === i) {
                    endPrice = price.toFixed(this.tokSubunit);
                }
            }
            this.endPrice = endPrice;
            this.mintmeAmountToReceive = sumMintme.toFixed(this.tokSubunit);
        },
        checkAmountInput: function() {
            return this.checkInput(this.currencySubunit);
        },
        getBaseLog: function(x, y) {
            return Math.log(y) / Math.log(x);
        },
        setMinMaxTokensForSale: function() {
            const tokenBalance = new Decimal(this.tokenBalance).toNumber();

            if (tokenBalance <= this.config.maxTokenForSale && tokenBalance > this.config.minTokenForSale) {
                this.tokensForSale = tokenBalance;
                this.maxTokenForSale = tokenBalance;
            } else if (tokenBalance <= this.config.minTokenForSale) {
                this.tokensForSale = this.config.minTokenForSale;
                this.maxTokenForSale = this.config.maxTokenForSale;
            }
        },
    },
    watch: {
        priceGrowth: function() {
            this.getPrice();
        },
        tokensForSale: function() {
            this.getPrice();
        },
        tokenBalance: function() {
            this.setMinMaxTokensForSale();
        },
    },
    validations: function() {
        return {
            startingPriceModel: {
                required,
                decimal,
                between: between(this.minStartingPrice, this.maxEndPrice),
            },
        };
    },
};
</script>

