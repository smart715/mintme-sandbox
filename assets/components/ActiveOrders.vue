<template>
    <div class="table-responsive">
        <table class="table m-0 border-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Address</th>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Free</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>12-12-1970</td>
                    <td class="text-yellow">Sell</td>
                    <td>[token]</td>
                    <td>0.0000</td>
                    <td>123456</td>
                    <td>9876WEB</td>
                    <td>9999</td>
                    <td>x</td>
                </tr>
                <tr>
                    <td>12-12-1970</td>
                    <td class="text-green">Buy</td>
                    <td>Webchain (WEB)</td>
                    <td>0.0000</td>
                    <td>123456</td>
                    <td>9876WEB</td>
                    <td>9999</td>
                    <td>x</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: 'ActiveOrders',
    data() {
        return {};
    },
};
</script>
