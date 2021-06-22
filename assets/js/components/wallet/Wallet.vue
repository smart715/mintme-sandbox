<template>
    <div class="px-0 py-2">
        <buy-crypto
            class="px-4 py-2"
            :coinify-ui-url="coinifyUiUrl"
            :coinify-partner-id="coinifyPartnerId"
            :coinify-crypto-currencies="coinifyCryptoCurrencies"
            :addresses="depositAddresses"
            :addresses-signature="addressesSignature"
            :predefined-tokens="predefinedItems"
            :mintme-exchange-mail-sent="mintmeExchangeMailSent"
        />
        <div class="table-responsive">
            <div v-if="showLoadingIconP" class="p-5 text-center">
                <font-awesome-icon
                    icon="circle-notch"
                    spin class="loading-spinner"
                    fixed-width
                    />
            </div>
            <b-table v-else hover :items="predefinedItems" :fields="predefinedTokenFields">
                <template v-slot:cell(name)="data">
                    <div class="first-field">
                        <a :href="data.item.url" class="text-white truncate-name">
                            {{ data.item.fullname|rebranding }} ({{ data.item.name|rebranding }})
                        </a>
                    </div>
                </template>
                <template v-slot:cell(available)="data">
                    <span class="text-break">
                        {{ data.value | toMoney(data.item.subunit) | formatMoney }}
                    </span>
                </template>
                <template v-slot:cell(action)="data">
                    <div class="row pl-2">
                        <button
                            class="btn btn-transparent d-flex flex-row pl-2"
                            :class="actionButtonClass(isCryptoActionDisabled('depositDisabled', data))"
                            @click="openDeposit(data.item.name, data.item.subunit)">
                            <div class="hover-icon">
                                <font-awesome-icon
                                    class="icon-default"
                                    :class="{
                                        'text-muted': isCryptoActionDisabled('depositDisabled', data)
                                        }"
                                    :icon="['fac', 'deposit']"
                                />
                                <span class="pl-2 text-xs align-middle wallet-action-txt">
                                  {{ $t('wallet.deposit') }}
                                </span>
                            </div>
                        </button>
                        <button
                            class="btn btn-transparent d-flex flex-row pl-2"
                            :class="actionButtonClass(isCryptoActionDisabled('withdrawalsDisabled', data))"
                            @click="openWithdraw(
                                        data.item.name,
                                        data.item.fee,
                                        data.item.available,
                                        data.item.subunit
                                        )"
                        >
                            <div class="hover-icon">
                                <font-awesome-icon
                                    class="icon-default"
                                    :class="{
                                        'text-muted': isCryptoActionDisabled('withdrawalsDisabled', data)
                                        }"
                                    :icon="['fac', 'withdraw']"
                                />
                                <span class="pl-2 text-xs align-middle wallet-action-txt">
                                  {{ $t('wallet.withdraw') }}
                                </span>
                            </div>
                        </button>
                    </div>
                </template>
            </b-table>
        </div>
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            {{ $t('wallet.own_tokens') }}
        </div>
        <div class="text-center p-5" v-if="showLoadingIcon">
            <font-awesome-icon
                icon="circle-notch"
                spin class="loading-spinner"
                fixed-width
                />
        </div>
        <div v-if="hasTokens" class="table-responsive">
            <b-table hover :items="items" :fields="tokenFields">
                <template v-slot:cell(name)="data">
                    <div
                        v-if="data.item.name.length > 17"
                        v-b-tooltip="{title: data.item.name, boundary:'viewport'}"
                        class="first-field"
                    >
                        <span v-if="data.item.blocked">
                            <span class="text-muted">
                                {{ data.item.name | truncate(14) }}
                            </span>
                        </span>
                        <span v-else>
                            <a :href="generatePairUrl(data.item)" class="text-white">
                                {{ data.item.name | truncate(14) }}
                            </a>
                        </span>
                    </div>
                    <div
                        v-else
                        class="first-field"
                    >
                        <span v-if="data.item.blocked">
                            <span class="text-muted">
                                {{ data.item.name | truncate(14) }}
                            </span>
                        </span>
                        <span v-else>
                            <a :href="generatePairUrl(data.item)" class="text-white">
                                {{ data.item.name }}
                            </a>
                        </span>
                    </div>
                </template>
                <template v-slot:cell(available)="data">
                    <span class="text-break">
                        {{ data.value | toMoney(data.item.subunit) | formatMoney }}
                    </span>
                </template>
                <template v-slot:cell(action)="data">
                    <div
                        v-if="data.item.deployed"
                        class="row pl-2"
                    >
                        <button
                            class="btn btn-transparent d-flex flex-row pl-2"
                            :class="actionButtonClass(isTokenActionDisabled('depositDisabled', data))"
                            @click="openDeposit(
                                data.item.name,
                                data.item.subunit,
                                true,
                                data.item.blocked,
                                data.item.cryptoSymbol
                               )"
                        >
                            <div class="hover-icon">
                                <font-awesome-icon
                                    class="icon-default"
                                    :class="{
                                        'text-muted': isTokenActionDisabled('depositDisabled', data)
                                        }"
                                    :icon="['fac', 'deposit']"
                                />
                                <span class="pl-2 text-xs align-middle wallet-action-txt">
                                  {{ $t('wallet.deposit') }}
                                </span>
                            </div>
                        </button>
                        <button
                            class="btn btn-transparent d-flex flex-row pl-2"
                            :class="actionButtonClass(isTokenActionDisabled('withdrawalsDisabled', data))"
                            @click="openWithdraw(
                                        data.item.name,
                                        data.item.fee,
                                        data.item.available,
                                        data.item.subunit,
                                        true,
                                        data.item.blocked,
                                        data.item.cryptoSymbol
                                        )"
                        >
                            <div>
                                <div class="hover-icon">
                                <font-awesome-icon
                                    class="icon-default"
                                    :class="{
                                        'text-muted': isTokenActionDisabled('withdrawalsDisabled', data)
                                        }"
                                    :icon="['fac', 'withdraw']"
                                />
                                <span class="pl-2 text-xs align-middle wallet-action-txt">
                                  {{ $t('wallet.withdraw') }}
                                </span>
                                </div>
                            </div>
                        </button>
                    </div>
                </template>
            </b-table>
        </div>
        <div class="table-responsive">
            <table v-if="!showLoadingIcon && items.length <= 0" class="table table-hover no-owned-token">
              <tbody>
                    <tr>
                        <td class="first-field">
                            <div class="truncate-name">
                              {{ $t('wallet.create_token_1') }}
                              <a :href="createTokenUrl">
                                {{ $t('wallet.create_token_2') }}
                              </a>
                            </div>
                        </td>
                        <td class="field-table">&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            {{ $t('wallet.no_tokens_1') }} <a :href="tradingUrl">{{ $t('wallet.no_tokens_2') }}</a>
                            {{ $t('wallet.no_tokens_3') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <withdraw-modal
            :visible="showModal"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :fee="withdraw.fee"
            :base-fee="withdraw.baseFee"
            :base-symbol="withdraw.baseSymbol"
            :available-base="withdraw.availableBase"
            :withdraw-url="withdrawUrl"
            :max-amount="withdraw.amount"
            :twofa="twofa"
            :subunit="withdraw.subunit"
            :no-close="true"
            :expiration-time="expirationTime"
            @close="closeWithdraw"
            :currency-mode="currencyMode"
        />
        <deposit-modal
            :address="depositAddress"
            :visible="showDepositModal"
            :description="depositDescription"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :fee="deposit.fee"
            :min="deposit.min"
            :no-close="false"
            @close="closeDeposit()"
        />
        <add-phone-alert-modal
            :visible="addPhoneModalVisible"
            :message="addPhoneModalMessage"
            :no-close="false"
            @close="addPhoneModalVisible = false"
        >
        </add-phone-alert-modal>
    </div>
</template>

<script>
import WithdrawModal from '../modal/WithdrawModal';
import DepositModal from '../modal/DepositModal';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';
import {
    WebSocketMixin,
    FiltersMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    LoggerMixin,
    AddPhoneAlertMixin,
} from '../../mixins';
import Decimal from 'decimal.js';
import {toMoney} from '../../utils';
import {
    tokSymbol,
    webSymbol,
    ethSymbol,
    tokEthSymbol,
    ethCryptoTokens,
} from '../../utils/constants';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {deposit as depositIcon, withdraw as withdrawIcon} from '../../utils/icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import BuyCrypto from './BuyCrypto';
import {BTable, VBTooltip} from 'bootstrap-vue';

library.add(depositIcon, withdrawIcon, faCircleNotch);

export default {
    name: 'Wallet',
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        LoggerMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        BTable,
        BuyCrypto,
        WithdrawModal,
        DepositModal,
        AddPhoneAlertModal,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        withdrawUrl: {type: String, required: true},
        createTokenUrl: String,
        tradingUrl: String,
        depositMoreProp: String,
        twofa: String,
        expirationTime: Number,
        disabledCrypto: String,
        disabledServicesConfig: String,
        tokenWithdrawFee: Number,
        isUserBlocked: Boolean,
        coinifyUiUrl: String,
        coinifyPartnerId: Number,
        coinifyCryptoCurrencies: Array,
        cantMakeDepositWithdrawal: Boolean,
        profileNickname: String,
        mintmeExchangeMailSent: Boolean,
    },
    data() {
        return {
            depositMore: null,
            tokens: null,
            predefinedTokens: null,
            depositAddresses: {},
            addressesSignature: {},
            showModal: false,
            selectedCurrency: null,
            isTokenModal: false,
            depositAddress: null,
            depositDescription: null,
            showDepositModal: null,
            noClose: false,
            addPhoneModalMessageType: 'deposit_withdrawal',
            addPhoneModalProfileNickName: this.profileNickname,
            tooltipOptions: {
                placement: 'bottom',
                arrow: true,
                trigger: 'mouseenter',
                delay: [100, 200],
            },
            predefinedTokenFields: [
                {key: 'name', label: this.$t('wallet.table.name'), class: 'first-field'},
                {key: 'available', label: this.$t('wallet.table.available'), class: 'field-table'},
                {key: 'action', label: this.$t('wallet.table.action'), class: 'field-table', sortable: false},
            ],
            tokenFields: [
                {key: 'name', label: this.$t('wallet.table.name'), class: 'first-field'},
                {key: 'available', label: this.$t('wallet.table.available'), class: 'field-table'},
                {key: 'action', label: this.$t('wallet.table.action'), class: 'field-table', sortable: false},
            ],
            withdraw: {
                fee: '0',
                baseFee: '0',
                amount: '0',
                subunit: 4,
                availableBase: '0',
            },
            deposit: {
                fee: undefined,
                min: undefined,
            },
        };
    },
    computed: {
        currencyMode: function() {
             return localStorage.getItem('_currency_mode');
        },
        hasTokens: function() {
            return Object.values(this.tokens || {}).length > 0;
        },
        allTokens: function() {
            return Object.assign({}, this.tokens || {}, this.predefinedTokens || {});
        },
        allTokensName: function() {
            return Object.values(this.allTokens).map((token) => {
                return token.identifier;
            });
        },
        predefinedItems: function() {
            return this.tokensToArray(this.predefinedTokens || {}).map((item) => {
                item.url = this.rebrandingFunc(this.generateCoinUrl(item));
                return item;
            });
        },
        items: function() {
            return this.tokensToArray(this.tokens || {}).filter((token) => !(!token.deployed && token.available <= 0));
        },
        showLoadingIconP: function() {
            return (this.predefinedTokens === null);
        },
        showLoadingIcon: function() {
            return (this.tokens === null);
        },
        disabledServices: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
    },
    mounted: function() {
        if (window.localStorage.getItem('mintme_signedup_from_quick_trade') !== null) {
            this.depositMore = window.localStorage.getItem('mintme_quick_trade_currency');

            window.localStorage.removeItem('mintme_signedup_from_quick_trade');
            window.localStorage.removeItem('mintme_quick_trade_currency');
        }

        Promise.all([
            this.$axios.retry.get(this.$routing.generate('tokens'))
                .then((res) => {
                    let tokensData = res.data;
                    this.tokens = tokensData.common;
                    this.predefinedTokens = tokensData.predefined;
                })
                .then(() => {
                    this.authorize()
                        .then(() => {
                            this.addMessageHandler((response) => {
                                if ('asset.update' === response.method) {
                                    this.updateBalances(response.params[0]);
                                }
                            }, 'wallet-asset-update', 'Wallet');

                            this.sendMessage(JSON.stringify({
                                method: 'asset.subscribe',
                                params: this.allTokensName,
                                id: parseInt(Math.random()),
                            }));
                        })
                        .catch((err) => {
                            this.notifyError(
                                this.$t('toasted.error.can_not_connect')
                            );
                            this.sendLogs('error', 'Can not connect to internal services', err);
                        });
                })
                .catch((err) => {
                    this.sendLogs('error', 'Service unavailable. Can not update tokens now', err);
                }),

            this.$axios.retry.get(this.$routing.generate('deposit_addresses_signature'))
                .then((res) => {
                    this.depositAddresses = res.data.addresses;
                    this.addressesSignature = res.data.signatures;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Service unavailable. Can not update deposit data now.', err);
                }),
        ])
        .then(() => {
            if (this.depositMore === null) {
                this.depositMore = this.depositMoreProp;
            }
            this.openDepositMore();
        })
        .catch((err) => {
            this.sendLogs('error', 'Service unavailable. Can not load wallet data now.', err);
        });
    },
    methods: {
        checkIsUserAbleToDepositWithdraw: function() {
            if (this.cantMakeDepositWithdrawal) {
                this.addPhoneModalVisible = true;
                return false;
            }
            return true;
        },
        isCryptoActionDisabled: function(action, data) {
            return this.isUserBlocked
            || this.isDisabledCrypto(data.name)
            || this.disabledServices[action]
            || this.disabledServices.allServicesDisabled;
        },
        isTokenActionDisabled: function(action, data) {
            return data.item.blocked
            || this.disabledServices[action]
            || this.disabledServices.allServicesDisabled;
        },
        actionButtonClass: function(disabled) {
            return disabled ?
                'text-muted pointer-events-none' :
                'text-white';
        },
        isDisabledCrypto: function(name) {
            return JSON.parse(this.disabledCrypto).includes(name);
        },
        openWithdraw: function(currency, fee, amount, subunit, isToken = false, isBlockedToken = false, crypto = '') {
            if (this.isDisabledCrypto(currency)
                || this.disabledServices.withdrawalsDisabled
                || this.disabledServices.allServicesDisabled
            ) {
              this.notifyError('Withdrawals are disabled. Please try again later');

              return;
            }
            if ((isToken && isBlockedToken) || (!isToken && this.isUserBlocked )) {
                return;
            }

            if (!this.checkIsUserAbleToDepositWithdraw()) {
                return;
            }

            this.showModal = true;
            this.selectedCurrency = currency;
            this.isTokenModal = isToken;
            this.withdraw.fee = fee ? toMoney(fee) : null;
            this.withdraw.baseSymbol = crypto;
            this.withdraw.baseFee = toMoney(
                isToken
                    ? ethSymbol === crypto ? this.tokenWithdrawFee : this.predefinedTokens[crypto || webSymbol].fee
                    : 0
            );
            this.withdraw.availableBase = this.predefinedTokens[crypto || webSymbol].available;
            this.withdraw.amount = toMoney(amount, subunit);
            this.withdraw.subunit = subunit;
        },
        closeWithdraw: function() {
            this.showModal = false;
        },
        openDeposit: function(currency, subunit, isToken = false, isBlockedToken = false, crypto = null) {
            if (this.isDisabledCrypto(currency)
                || this.disabledServices.depositDisabled
                || this.disabledServices.allServicesDisabled
            ) {
              this.notifyError('Deposits are disabled. Please try again later');

              return;
            }

            if ((isToken && isBlockedToken) || (!isToken && this.isUserBlocked )) {
                return;
            }

            if (!this.checkIsUserAbleToDepositWithdraw()) {
                return;
            }

            if (isToken && !this.tokens[currency].deployed) {
                return;
            }

            this.depositAddress = (isToken
                ? this.depositAddresses[tokSymbol + crypto]
                : ethCryptoTokens.includes(currency)
                        ? this.depositAddresses[tokEthSymbol]
                        : this.depositAddresses[currency]
                ) || this.$t('wallet.loading');
            this.depositDescription = this.$t('wallet.send_to_address', {currency: currency});
            this.selectedCurrency = currency;
            this.deposit.fee = undefined;
            this.isTokenModal = isToken;

            this.$axios.retry.get(this.$routing.generate('deposit_info', {
                    crypto: currency,
                }))
                .then((res) => {
                    this.deposit.fee = res.data.fee && 0.0 !== parseFloat(res.data.fee)
                        ? toMoney(res.data.fee, subunit)
                        : undefined;
                    this.deposit.min = res.data.minDeposit ? toMoney(res.data.minDeposit, subunit) : undefined;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Service unavailable. Can not update deposit fee status', err);
                });

            this.showDepositModal = true;
        },
        closeDeposit: function() {
            this.deposit.min = undefined;
            this.showDepositModal = false;
        },
        openDepositMore: function() {
            const isToken = !!this.tokens[this.depositMore];

            const asset = isToken ?
                this.tokens[this.depositMore] :
                this.predefinedTokens[this.depositMore]
            ;

            if (asset && !this.isUserBlocked) {
                if (window.history.replaceState) {
                    window.history.replaceState(
                        {}, '', location.href.split('?')[0]
                    );
                }

                this.openDeposit(this.depositMore,
                    asset.subunit,
                    isToken,
                    asset.blocked,
                    asset.cryptoSymbol
                );
            }
        },
        updateBalances: function(data) {
            Object.keys(data).forEach((oTokenName) => {
                let oToken = data[oTokenName];

                Object.keys(this.predefinedTokens).forEach((token) => {
                    if (oTokenName === this.predefinedTokens[token].identifier) {
                        this.predefinedTokens[token].available = oToken.available;
                    }
                });

                Object.keys(this.tokens).forEach((token) => {
                    if (oTokenName === this.tokens[token].identifier) {
                        if (!this.tokens[token].owner) {
                            this.tokens[token].available = oToken.available;
                            return;
                        }

                        this.$axios.retry.get(this.$routing.generate('lock-period', {name: token}))
                            .then((res) =>
                                this.tokens[token].available = res.data ?
                                    new Decimal(oToken.available).sub(res.data.frozenAmount) : oToken.available
                            )
                            .catch((err) => {
                                this.sendLogs('error', 'Can not get lock-period', err);
                            });
                    }
                });
            });
        },
        tokensToArray: function(tokens) {
            Object.keys(tokens).map(function(key) {
                tokens[key].name = key;
            });

            return Object.values(tokens);
        },
        generatePairUrl: function(market) {
            return this.$routing.generate('token_show', {name: market.name, tab: 'trade'});
        },
        generateCoinUrl: function(coin) {
            let params = {
                quote: coin.exchangeble && coin.tradable ? coin.name : this.predefinedTokens.WEB.name,
                base: !coin.exchangeble ? coin.name : this.predefinedTokens.BTC.name,
            };
            return this.$routing.generate('coin', params);
        },
    },
};
</script>
