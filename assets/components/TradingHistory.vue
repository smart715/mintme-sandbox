<template>
    <div class="table-responsive">
        <table class="table m-0 border-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Amount</th>
                    <th>Price</th>
                    <th>Total cost</th>
                    <th>Free</th>
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
                </tr>
                <tr>
                    <td>12-12-1970</td>
                    <td class="text-yellow">Sell</td>
                    <td>Webchain (WEB)</td>
                    <td>0.0000</td>
                    <td>123456</td>
                    <td>9876WEB</td>
                    <td>9999</td>
                </tr>
                <tr>
                    <td>12-12-1970</td>
                    <td class="text-green">Buy</td>
                    <td>[token]</td>
                    <td>0.0000</td>
                    <td>123456</td>
                    <td>9876WEB</td>
                    <td>9999</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: 'TradingHistory',
    data() {
        return {};
    },
};
</script>
