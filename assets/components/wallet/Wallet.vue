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
                    <tr v-for="(token, name) in predefinedTokens" :key="name">
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
                <tr v-for="(token, name) in tokens" :key="name">
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

export default {
    name: 'Wallet',
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
    },
};
</script>

<style lang="sass">
    @import '../../scss/variables'

    .table-orange-hover
        tr:hover
            color: $theme-orange
</style>
