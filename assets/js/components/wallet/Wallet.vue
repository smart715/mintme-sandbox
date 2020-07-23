<template>
    <div class="px-0 py-2">
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            Balance
        </div>
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
                        <a :href="rebrandingFunc(generateCoinUrl(data.item))" class="text-white truncate-name">
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
                        <div class="d-flex flex-row c-pointer pl-2"
                            :class="{'text-muted': isUserBlocked}"
                            @click="openDeposit(data.item.name, data.item.subunit)">
                            <div><i class="icon-deposit"></i></div>
                            <div>
                                <span class="pl-2 text-xs align-middle">Deposit</span>
                            </div>
                        </div>
                        <div
                            class="d-flex flex-row c-pointer pl-2"
                            :class="{'text-muted': isUserBlocked}"
                            @click="openWithdraw(
                                        data.item.name,
                                        data.item.fee,
                                        data.item.available,
                                        data.item.subunit)"
                        >
                            <div><i class="icon-withdraw"></i></div>
                            <div>
                                <span class="pl-2 text-xs align-middle">Withdraw</span>
                            </div>
                        </div>
                    </div>
                </template>
            </b-table>
        </div>
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            Tokens you own
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
                    <div v-if="data.item.name.length > 17" v-b-tooltip="{title: data.item.name, boundary:'viewport'}" class="first-field">
                        <span v-if="data.item.blocked">
                            <span class="text-muted">
                                {{ data.item.name | truncate(17) }}
                            </span>
                        </span>
                        <span v-else>
                            <a :href="generatePairUrl(data.item)" class="text-white">
                                {{ data.item.name | truncate(17) }}
                            </a>
                        </span>
                    </div>
                    <div
                        v-else
                        class="first-field"
                    >
                        <span v-if="data.item.blocked">
                            <span class="text-muted">
                                {{ data.item.name | truncate(17) }}
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
                        class="row pl-2">
                        <div
                            class="d-flex flex-row c-pointer pl-2"
                            :class="{'text-muted': data.item.blocked}"
                            @click="openDeposit(data.item.name, data.item.subunit, true, data.item.blocked)">
                            <div><i class="icon-deposit"></i></div>
                            <div>
                                <span class="pl-2 text-xs align-middle">Deposit</span>
                            </div>
                        </div>
                        <div
                            class="d-flex flex-row c-pointer pl-2"
                            :class="{'text-muted': data.item.blocked}"
                            @click="openWithdraw(
                                        data.item.name,
                                        data.item.fee,
                                        data.item.available,
                                        data.item.subunit,
                                        true,
                                        data.item.blocked)"
                        >
                            <div><i class="icon-withdraw"></i></div>
                            <div>
                                <span class="pl-2 text-xs align-middle">Withdraw</span>
                            </div>
                        </div>
                    </div>
                </template>
            </b-table>
        </div>
        <table v-if="!hasTokens && !showLoadingIcon" class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2">Create <a :href="createTokenUrl">your own token</a></td>
                </tr>
                <tr>
                    <td colspan="2">
                        You have not bought tokens yet. Find favorite content creators or
                        famous person through search bar or visit <a :href="tradingUrl">trading page</a>.
                        Start trading now.
                    </td>
                </tr>
            </tbody>
        </table>
        <withdraw-modal
            :visible="showModal"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :fee="withdraw.fee"
            :web-fee="withdraw.webFee"
            :available-web="withdraw.availableWeb"
            :withdraw-url="withdrawUrl"
            :max-amount="withdraw.amount"
            :twofa="twofa"
            :subunit="withdraw.subunit"
            :no-close="true"
            :expiration-time="expirationTime"
            @close="closeWithdraw"
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
    </div>
</template>

<script>
import WithdrawModal from '../modal/WithdrawModal';
import DepositModal from '../modal/DepositModal';
import {
    WebSocketMixin,
    FiltersMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    LoggerMixin,
} from '../../mixins';
import Decimal from 'decimal.js';
import {toMoney} from '../../utils';
import {tokSymbol, btcSymbol, webSymbol, ethSymbol} from '../../utils/constants';

export default {
    name: 'Wallet',
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        LoggerMixin,
    ],
    components: {
        WithdrawModal,
        DepositModal,
    },
    props: {
        withdrawUrl: {type: String, required: true},
        createTokenUrl: String,
        tradingUrl: String,
        depositMore: String,
        twofa: String,
        expirationTime: Number,
        isUserBlocked: Boolean,
    },
    data() {
        return {
            tokens: null,
            predefinedTokens: null,
            depositAddresses: {},
            showModal: false,
            selectedCurrency: null,
            isTokenModal: false,
            depositAddress: null,
            depositDescription: null,
            showDepositModal: null,
            noClose: false,
            tooltipOptions: {
                placement: 'bottom',
                arrow: true,
                trigger: 'mouseenter',
                delay: [100, 200],
            },
            predefinedTokenFields: [
                {key: 'name', label: 'Name', class: 'first-field'},
                {key: 'available', label: 'Amount', class: 'field-table'},
                {key: 'action', label: 'Actions', class: 'field-table', sortable: false},
            ],
            tokenFields: [
                {key: 'name', label: 'Name', class: 'first-field'},
                {key: 'available', label: 'Amount', class: 'field-table'},
                {key: 'action', label: 'Actions', class: 'field-table', sortable: false},
            ],
            withdraw: {
                fee: '0',
                webFee: '0',
                amount: '0',
                subunit: 4,
                availableWeb: '0',
            },
            deposit: {
                fee: undefined,
                min: undefined,
            },
        };
    },
    computed: {
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
            return this.tokensToArray(this.predefinedTokens || {});
        },
        items: function() {
            return this.tokensToArray(this.tokens || {});
        },
        showLoadingIconP: function() {
            return (this.predefinedTokens === null);
        },
        showLoadingIcon: function() {
            return (this.tokens === null);
        },
    },
    mounted: function() {
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
                            }, 'wallet-asset-update');

                            this.sendMessage(JSON.stringify({
                                method: 'asset.subscribe',
                                params: this.allTokensName,
                                id: parseInt(Math.random()),
                            }));
                        })
                        .catch((err) => {
                            this.notifyError(
                                'Can not connect to internal services'
                            );
                            this.sendLogs('error', 'Can not connect to internal services', err);
                        });
                })
                .catch((err) => {
                    this.notifyError('Can not update tokens now. Try again later.');
                    this.sendLogs('error', 'Service unavailable. Can not update tokens now', err);
                }),

            this.$axios.retry.get(this.$routing.generate('deposit_addresses'))
                .then((res) => this.depositAddresses = res.data)
                .catch((err) => {
                    this.notifyError('Can not update deposit data now. Try again later.');
                    this.sendLogs('error', 'Service unavailable. Can not update deposit data now.', err);
                }),
        ])
        .then(() => {
            this.openDepositMore();
        })
        .catch((err) => {
            this.notifyError('Can not load Wallet data. Try again later.');
            this.sendLogs('error', 'Service unavailable. Can not load wallet data now.', err);
        });
    },
    methods: {
        openWithdraw: function(currency, fee, amount, subunit, isToken = false, isBlockedToken = false) {
            if ((isToken && isBlockedToken) || (!isToken && this.isUserBlocked )) {
                return;
            }
            if (!this.twofa) {
                this.notifyInfo('Please enable 2FA before withdrawing');
                return;
            }
            this.showModal = true;
            this.selectedCurrency = currency;
            this.isTokenModal = isToken;
            this.withdraw.fee = toMoney(isToken ? 0 : fee, subunit);
            this.withdraw.webFee = toMoney(
                isToken || webSymbol === currency ? this.predefinedTokens[webSymbol].fee : 0,
                subunit
            );
            this.withdraw.availableWeb = this.predefinedTokens[webSymbol].available;
            this.withdraw.amount = toMoney(amount, subunit);
            this.withdraw.subunit = subunit;
        },
        closeWithdraw: function() {
            this.showModal = false;
        },
        openDeposit: function(currency, subunit, isToken = false, isBlockedToken = false) {
            if ((isToken && isBlockedToken) || (!isToken && this.isUserBlocked )) {
                return;
            }
            this.depositAddress = (isToken ? this.depositAddresses[tokSymbol] : this.depositAddresses[currency])
                || 'Loading..';
            this.depositDescription = `Send ${currency} to the address above.`;
            this.selectedCurrency = currency;
            this.deposit.fee = undefined;
            this.isTokenModal = isToken;

            this.$axios.retry.get(this.$routing.generate('deposit_info', {
                    crypto: isToken ? webSymbol : currency,
                }))
                .then((res) => {
                    this.deposit.fee = res.data.fee && 0.0 !== parseFloat(res.data.fee)
                        ? toMoney(res.data.fee, subunit)
                        : undefined;
                    this.deposit.min = res.data.minDeposit ? toMoney(res.data.minDeposit, subunit) : undefined;
                })
                .catch((err) => {
                    this.notifyError('Can not update deposit fee status. Try again later.');
                    this.sendLogs('error', 'Service unavailable. Can not update deposit fee status', err);
                });

            this.showDepositModal = true;
        },
        closeDeposit: function() {
            this.deposit.min = undefined;
            this.showDepositModal = false;
        },
        openDepositMore: function() {
            if (
                [webSymbol, btcSymbol, ethSymbol].includes(this.depositMore) &&
                null !== this.predefinedTokens &&
                this.predefinedTokens.hasOwnProperty(this.depositMore) &&
                this.depositAddresses.hasOwnProperty(this.depositMore) &&
                !this.isUserBlocked
            ) {
                if (window.history.replaceState) {
                    window.history.replaceState(
                        {}, '', location.href.split('?')[0]
                    );
                }

                this.openDeposit(
                    this.depositMore,
                    this.predefinedTokens[this.depositMore].subunit
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
                base: !coin.exchangeble ? coin.name : this.predefinedTokens.BTC.name,
                quote: coin.exchangeble && coin.tradable ? coin.name : this.predefinedTokens.WEB.name,
            };
            return this.$routing.generate('coin', params);
        },
    },
};
</script>
