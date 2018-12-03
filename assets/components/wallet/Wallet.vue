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
                                    icon="shopping-cart"
                                    class="text-orange c-pointer"
                                    @click="openWithdraw(name, token.fee, token.precision)"
                            />
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
            @close="closeWithdraw" />
    </div>
</template>

<script>
import WithdrawModal from '../modal/WithdrawModal';

export default {
    name: 'Wallet',
    components: {
        WithdrawModal,
    },
    props: {
        tokens: {type: Object, required: true},
        predefinedTokens: {type: Object, required: true},
        withdrawUrl: {type: String, required: true},
    },
    data() {
        return {
            showModal: false,
            selectedCurrency: null,
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
    },
};
</script>

<style lang="sass">
    @import '../../scss/variables'

    .table-orange-hover
        tr:hover
            color: $theme-orange
</style>
