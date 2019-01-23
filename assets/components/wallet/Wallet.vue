<template>
    <div>
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            Balance
        </div>
        <div class="table-responsive">
            <b-table hover :items="predefinedItems" :fields="predefinedTokenFields" class="wallet-table">
                     <template slot="name" slot-scope="data">
                    {{ data.item.fullname }} ({{ data.item.name }})
                </template>
                <template slot="available" slot-scope="data">
                    {{ data.value | toMoney }}
                </template>
                <template slot="action" slot-scope="data">
                    <div class="d-inline text-center float-left">
                        <span
                            class="icon-deposit c-pointer mx-auto"
                            @click="openWithdraw(data.item.name, data.item.fee, data.item.available)">
                        </span>
                        <span class="text-sm">Deposit</span>
                    </div>
                    <div class="d-inline text-center float-left pl-2">
                        <span
                            class="icon-withdraw c-pointer mx-auto"
                            @click="openDeposit(data.item.name)">
                        </span>
                        <span class="text-sm">Withdraw</span>
                    </div>
                </template>
            </b-table>
        </div>
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            Web tokens you own
        </div>
        <div v-if="hasTokens" class="table-responsive">
            <b-table hover :items="items" :fields="tokenFields" class="wallet-table">
                     <template slot="name" slot-scope="data">
                    {{ data.item.name }}
                </template>
                <template slot="available" slot-scope="data">
                    {{ data.value | toMoney }}
                </template>
            </b-table>
        </div>
        <table v-if="!hasTokens" class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2">Create <a :href="createTokenUrl">own token</a></td>
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
            :fee="fee"
            :withdraw-url="withdrawUrl"
            :max-amount="amount"
            @close="closeWithdraw"
            />
        <deposit-modal
            :address="depositAddress"
            :visible="showDepositModal"
            :description="depositDescription"
            @close="closeDeposit()"
            />
    </div>
</template>

<script>
import WithdrawModal from '../modal/WithdrawModal';
import DepositModal from '../modal/DepositModal';
import AuthSocketMixin from '../../mixins/authsocket';
import Decimal from 'decimal.js';
import {toMoney} from '../../js/utils';

export default {
    name: 'Wallet',
    mixins: [AuthSocketMixin],
    components: {
        WithdrawModal,
        DepositModal,
    },
    props: {
        tokens: {type: Array, default: () => []},
        predefinedTokens: {type: Array, default: () => []},
        withdrawUrl: {type: String, required: true},
        depositAddresses: {type: Object},
        createTokenUrl: String,
        tradingUrl: String,
    },
    data() {
        return {
            immutableTokens: this.tokens,
            immutablePTokens: this.predefinedTokens,
            showModal: false,
            selectedCurrency: null,
            depositAddress: null,
            depositDescription: null,
            showDepositModal: null,
            tooltipOptions: {
                placement: 'bottom',
                arrow: true,
                trigger: 'mouseenter',
                delay: [100, 200],
            },
            depositTooltip: 'Deposit!',
            withdrawTooltip: 'Withdraw!',
            fee: '0',
            amount: '0',
            predefinedTokenFields: {
                name: {label: 'Name', sortable: true},
                available: {label: 'Amount', sortable: true},
                action: {label: 'Actions', sortable: false},
            },
            tokenFields: {
                name: {label: 'Name', sortable: true},
                available: {label: 'Amount', sortable: true},
            },
        };
    },
    computed: {
        hasTokens: function() {
            return Object.values(this.tokens).length > 0;
        },
        allTokens: function() {
            return Object.assign({}, this.tokens || {}, this.predefinedTokens || {});
        },
        allTokensName: function() {
            return Object.values(this.allTokens).map((token) => {
                return token.hiddenName;
            });
        },
        predefinedItems: function() {
            return this.tokensToArray(this.predefinedTokens);
        },
        items: function() {
            return this.tokensToArray(this.tokens);
        },
    },
    mounted: function() {
        this.authorize(() => {
            this.wsClient.send(JSON.stringify({
                'method': 'asset.subscribe',
                'params': this.allTokensName,
                'id': parseInt(Math.random()),
            }));
        }, (response) => {
            if ('asset.update' === response.method) {
                this.updateBalances(response.params[0]);
            }
        });
    },
    methods: {
        openWithdraw: function(currency, fee, amount) {
            this.showModal = true;
            this.selectedCurrency = currency;
            this.fee = fee;
            this.amount = toMoney(amount);
        },
        closeWithdraw: function() {
            this.showModal = false;
        },
        openDeposit: function(currency) {
            this.depositAddress = this.depositAddresses[currency];
            this.depositDescription = `Send ${currency}s to the address above.`;
            this.showDepositModal = true;
        },
        closeDeposit: function() {
            this.showDepositModal = false;
        },
        updateBalances: function(data) {
            Object.keys(data).forEach((oTokenName) => {
                let oToken = data[oTokenName];

                Object.keys(this.immutablePTokens).forEach((token) => {
                    if (oTokenName === this.immutablePTokens[token].hiddenName) {
                        this.immutablePTokens[token].available = Decimal.sub(
                            oToken.available,
                            this.immutablePTokens[token].frozen
                        ).toString();
                    }
                });

                Object.keys(this.immutableTokens).forEach((token) => {
                    if (oTokenName === this.immutableTokens[token].hiddenName) {
                        this.immutableTokens[token].available = Decimal.sub(
                            oToken.available,
                            this.immutableTokens[token].frozen
                        ).toString();
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
    },
    filters: {
        toMoney: function(val) {
            return toMoney(val);
        },
    },
};
</script>
