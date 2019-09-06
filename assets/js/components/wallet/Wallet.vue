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
                <template slot="name" slot-scope="data">
                    <a :href="generateCoinUrl(data.item)" class="text-white">
                        {{ data.item.fullname }} ({{ data.item.name }})
                    </a>
                </template>
                <template slot="available" slot-scope="data">
                    {{ data.value | toMoney(data.item.subunit) | formatMoney }}
                </template>
                <template slot="action" slot-scope="data">
                    <div
                        class="row">
                        <div class="d-flex flex-row c-pointer pl-2"
                            @click="openDeposit(data.item.name, data.item.subunit)">
                            <div><i class="icon-deposit"></i></div>
                            <div>
                                <span class="pl-2 text-xs align-middle">Deposit</span>
                            </div>
                        </div>
                        <div
                            class="d-flex flex-row c-pointer pl-2"
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
            Web tokens you own
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
                <template slot="name" slot-scope="data">
                    <a :href="generatePairUrl(data.item)" class="text-white">{{ data.item.name }}</a>
                </template>
                <template slot="available" slot-scope="data">
                    {{ data.value | toMoney(data.item.subunit) | formatMoney }}
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
            :fee="withdraw.fee"
            :withdraw-url="withdrawUrl"
            :max-amount="withdraw.amount"
            :twofa="twofa"
            :subunit="withdraw.subunit"
            :no-close="true"
            @close="closeWithdraw"
        />
        <deposit-modal
            :address="depositAddress"
            :visible="showDepositModal"
            :description="depositDescription"
            :currency="selectedCurrency"
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
import {WebSocketMixin, MoneyFilterMixin} from '../../mixins';
import Decimal from 'decimal.js';
import {toMoney} from '../../utils';

export default {
    name: 'Wallet',
    mixins: [WebSocketMixin, MoneyFilterMixin],
    components: {
        WithdrawModal,
        DepositModal,
    },
    props: {
        withdrawUrl: {type: String, required: true},
        createTokenUrl: String,
        tradingUrl: String,
        twofa: String,
    },
    data() {
        return {
            tokens: null,
            predefinedTokens: null,
            depositAddresses: {},
            showModal: false,
            selectedCurrency: null,
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
            predefinedTokenFields: {
                name: {label: 'Name'},
                available: {label: 'Amount'},
                action: {label: 'Actions', sortable: false},
            },
            tokenFields: {
                name: {label: 'Name'},
                available: {label: 'Amount'},
            },
            withdraw: {
                fee: '0',
                amount: '0',
                subunit: 4,
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
                    .catch(() => this.$toasted.error(
                        'Can not connect to internal services'
                    ));
            })
            .catch(() => {
                this.$toasted.error('Can not update tokens now. Try again later.');
            });

        this.$axios.retry.get(this.$routing.generate('deposit_addresses'))
            .then((res) => this.depositAddresses = res.data)
            .catch(() => {
                this.$toasted.error('Can not update deposit data now. Try again later.');
            });
    },
    methods: {
        openWithdraw: function(currency, fee, amount, subunit) {
            if (!this.twofa) {
                this.$toasted.info('Please enable 2FA before withdrawing');
                return;
            }
            this.showModal = true;
            this.selectedCurrency = currency;
            this.withdraw.fee = toMoney(fee, subunit);
            this.withdraw.amount = toMoney(amount, subunit);
            this.withdraw.subunit = subunit;
        },
        closeWithdraw: function() {
            this.showModal = false;
        },
        openDeposit: function(currency, subunit) {
            this.depositAddress = this.depositAddresses[currency] || 'Loading..';
            this.depositDescription = `Send ${currency}s to the address above.`;
            this.selectedCurrency = currency;
            this.deposit.fee = undefined;

            this.$axios.retry.get(this.$routing.generate('deposit_fee', {
                    crypto: currency,
                }))
                .then((res) => this.deposit.fee = res.data && parseFloat(res.data) !== 0.0 ?
                    toMoney(res.data, subunit) :
                    undefined
                )
                .catch(() => {
                    this.$toasted.error('Can not update deposit fee status. Try again later.');
                });

            // TODO: Get rid of hardcoded WEB
            this.deposit.min = currency === 'WEB' ? toMoney(1, subunit) : undefined;
            this.showDepositModal = true;
        },
        closeDeposit: function() {
            this.showDepositModal = false;
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
                            .catch(() => {});
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
            return this.$routing.generate('token_show', {name: market.name});
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
