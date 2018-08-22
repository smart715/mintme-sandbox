<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Sell Orders
                <span class="card-header-icon">
                    Total: xxxToken
                    <font-awesome-icon
                        icon="question"
                        class="ml-1 mb-1 p-1 h4 bg-orange rounded-circle square"
                    />
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
import {faSort} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faSort);

export default {
    name: 'TokenTradeSellOrders',
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
