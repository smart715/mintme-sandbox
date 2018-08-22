<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Sell Orders
                <span class="card-header-icon">
                    Total: xxxToken
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <table class="table m-0 border-0">
                        <thead>
                            <tr>
                                <th>
                                    Price
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Amount
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Sum WEB
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Trader
                                    <font-awesome-icon icon="sort" />
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                            <tr>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>Jon Doe 😄</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'TokenInvestSellOrders',
    props: {
        containerClass: String,
    },
    data() {
        return {};
    },
    components: {
        FontAwesomeIcon,
    },
};
</script>
