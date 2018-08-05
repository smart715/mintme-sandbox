<template>
    <div class="table-responsive">
        <table class="table m-0 border-0">
            <thead>
                <tr>
                    <th>Date <font-awesome-icon icon="sort" /></th>
                    <th>Type <font-awesome-icon icon="sort" /></th>
                    <th>Name <font-awesome-icon icon="sort" /></th>
                    <th>Amount <font-awesome-icon icon="sort" /></th>
                    <th>Price <font-awesome-icon icon="sort" /></th>
                    <th>Total cost <font-awesome-icon icon="sort" /></th>
                    <th>Free <font-awesome-icon icon="sort" /></th>
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faSort} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
library.add(faSort);
export default {
    name: 'TradingHistory',
    data() {
        return {};
    },
    components: {
        FontAwesomeIcon,
    },
};
</script>
