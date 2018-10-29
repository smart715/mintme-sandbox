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
                    <tr>
                        <td>Bitcoin (BTC)</td>
                        <td>0.123</td>
                        <td>^</td>
                    </tr>
                    <tr>
                        <td>Webchain (WEB)</td>
                        <td>120.24353343
                            <font-awesome-icon
                                icon="question"
                                class="text-green"
                            />
                            :30</td>
                        <td>^</td>
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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(token, name) in tokens">
                    <td>{{ name }}</td>
                    <td>{{ token.available }}</td>
                    <td>^</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: 'Wallet',
    props: {
        tokens: { type: Object, required: true }
    },
    data() {
        return {};
    },
};
</script>

<style lang="sass">
    @import '../../scss/variables'

    .table-orange-hover
        tr:hover
            color: $theme-orange
</style>