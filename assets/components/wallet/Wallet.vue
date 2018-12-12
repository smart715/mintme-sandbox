<template>
    <div>
        <div class="card-title font-weight-bold pl-3 pt-3 pb-1">
            Balance
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name <font-awesome-icon icon="sort" /></th>
                        <th>Amount <font-awesome-icon icon="sort" /></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(token, name) in immutablePTokens" :key="name">
                        <td>{{ token.fullname }} ({{ name }})</td>
                        <td>{{ token.available }}</td>
                        <td>
                            <font-awesome-icon
                                :title="withdrawTooltip"
                                v-tippy="tooltipOptions"
                                icon="shopping-cart"
                                class="text-orange c-pointer"
                                @click="openWithdraw(name, token.fee, token.precision)"
                            />
                            <font-awesome-icon
                                :title="depositTooltip"
                                v-tippy="tooltipOptions"
                                icon="piggy-bank"
                                class="text-orange c-pointer"
                                @click="openDeposit(name)"
                                size="1x"/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-title font-weight-bold pl-3 pt-3 pb-1">
            Web tokens you own
        </div>
        <table class="table table-orange-hover">
            <thead>
                <tr>
                    <th>Name <font-awesome-icon icon="sort" /></th>
                    <th>Amount <font-awesome-icon icon="sort" /></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(token, name) in immutableTokens" :key="name">
                    <td>{{ name }}</td>
                    <td>{{ token.available }}</td>
                </tr>
            </tbody>
        </table>
        <withdraw-modal
            :visible="showModal"
            :currency="selectedCurrency"
            :fee="fee"
            :precision="precision"
            :withdraw-url="withdrawUrl"
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

export default {
    name: 'Wallet',
    mixins: [AuthSocketMixin],
    components: {
        WithdrawModal,
        DepositModal,
    },
    props: {
        tokens: {type: Object, required: true},
        predefinedTokens: {type: Object, required: true},
        withdrawUrl: {type: String, required: true},
        depositAddresses: {type: Object},
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
            fee: 0,
            precision: 8,
        };
    },
    computed: {
        allTokens: function() {
            return Object.assign({}, this.tokens || {}, this.predefinedTokens || {});
        },
        allTokensName: function() {
            return Object.values(this.allTokens).map((token) => {
                return token.hiddenName;
            });
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
        openWithdraw: function(currency, fee, precision) {
            this.showModal = true;
            this.selectedCurrency = currency;
            this.fee = fee;
            this.precision = precision;
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
                        this.immutablePTokens[token].available = oToken.available;
                    }
                });

                Object.keys(this.immutableTokens).forEach((token) => {
                    if (oTokenName === this.immutableTokens[token].hiddenName) {
                        this.immutableTokens[token].available = oToken.available;
                    }
                });
            });
        },
    },
};
</script>

<style lang="sass">
    @import '../../scss/variables'

    .table-orange-hover
        tr:hover
            color: $theme-orange
</style>
