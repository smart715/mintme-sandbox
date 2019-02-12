<template>
    <div class="px-0 py-2">
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            Balance
        </div>
        <div class="table-responsive">
            <font-awesome-icon
                    icon="circle-notch"
                    spin class="loading-spinner"
                    fixed-width
                    v-if="showLoadingIconP"
            />
            <b-table v-else hover :items="predefinedItems" :fields="predefinedTokenFields">
                <template slot="name" slot-scope="data">
                    {{ data.item.fullname }} ({{ data.item.name }})
                </template>
                <template slot="available" slot-scope="data">
                    {{ data.value | toMoney }}
                </template>
                <template slot="action" slot-scope="data">
                         <div  class="row justify-content-center">
                             <div class="d-inline">
                                 <i
                                     class="icon-deposit c-pointer float-left"
                                     @click="openWithdraw(data.item.name, data.item.fee, data.item.available)">
                                 </i>
                                 <span class="pl-2 float-left text-sm align-middle">Deposit</span>
                             </div>
                             <div class="d-inline pl-3">
                                 <span class="d-inline">
                                     <i
                                         class="icon-withdraw c-pointer float-left"
                                         @click="openDeposit(data.item.name)">
                                     </i>
                                 </span>
                                 <span class="pl-2 float-left text-sm align-middle">Withdraw</span>
                             </div>
                         </div>
                </template>
            </b-table>
        </div>
        <div class="card-title font-weight-bold pl-4 pt-2 pb-1">
            Web tokens you own
        </div>
        <font-awesome-icon
                icon="circle-notch"
                spin class="loading-spinner"
                fixed-width
                v-if="showLoadingIcon"
        />
        <div v-if="hasTokens" class="table-responsive">
            <b-table hover :items="items" :fields="tokenFields">
                <template slot="name" slot-scope="data">
                    {{ data.item.name }}
                </template>
                <template slot="available" slot-scope="data">
                    {{ data.value | toMoney }}
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
import WebSocketMixin from '../../js/mixins/websocket';
import Decimal from 'decimal.js';
import {toMoney} from '../../js/utils';

export default {
    name: 'Wallet',
    mixins: [WebSocketMixin],
    components: {
        WithdrawModal,
        DepositModal,
    },
    props: {
        withdrawUrl: {type: String, required: true},
        createTokenUrl: String,
        tradingUrl: String,
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
                name: {label: 'Name'},
                available: {label: 'Amount'},
                action: {label: 'Actions', sortable: false},
            },
            tokenFields: {
                name: {label: 'Name'},
                available: {label: 'Amount'},
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
                return token.hiddenName;
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
                        });
                        this.sendMessage(JSON.stringify({
                            method: 'asset.subscribe',
                            params: this.allTokensName,
                            id: parseInt(Math.random()),
                        }));
                    })
                    .catch((err) => {
                        this.$toasted.error(
                            'Can not connect to internal services'
                        );
                    });
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
            this.depositAddress = this.depositAddresses[currency] || 'Loading..';
            this.depositDescription = `Send ${currency}s to the address above.`;
            this.showDepositModal = true;
        },
        closeDeposit: function() {
            this.showDepositModal = false;
        },
        updateBalances: function(data) {
            Object.keys(data).forEach((oTokenName) => {
                let oToken = data[oTokenName];

                Object.keys(this.predefinedTokens).forEach((token) => {
                    if (oTokenName === this.predefinedTokens[token].hiddenName) {
                        this.predefinedTokens[token].available = Decimal.sub(
                            oToken.available,
                            this.predefinedTokens[token].frozen
                        ).toString();
                    }
                });

                Object.keys(this.tokens).forEach((token) => {
                    if (oTokenName === this.tokens[token].hiddenName) {
                        this.tokens[token].available = Decimal.sub(
                            oToken.available,
                            this.tokens[token].frozen
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
