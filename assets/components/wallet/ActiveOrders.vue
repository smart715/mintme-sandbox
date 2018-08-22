<template>
    <div class="table-responsive">
        <table class="table m-0 border-0">
            <thead>
                <tr>
                    <th>Date <font-awesome-icon icon="sort" /></th>
                    <th>Type <font-awesome-icon icon="sort" /></th>
                    <th>Address <font-awesome-icon icon="sort" /></th>
                    <th>Amount <font-awesome-icon icon="sort" /></th>
                    <th>Balance <font-awesome-icon icon="sort" /></th>
                    <th>Status <font-awesome-icon icon="sort" /></th>
                    <th>Free <font-awesome-icon icon="sort" /></th>
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
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'ActiveOrders',
    data() {
        return {};
    },
    components: {
        FontAwesomeIcon,
    },
};
</script>
