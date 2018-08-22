<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Trade History
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <table class="table m-0 border-0">
                        <thead>
                            <tr>
                                <th>
                                    Type
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Order maker
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Order trader
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Price per token
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Token amount
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    WEB amount
                                    <font-awesome-icon icon="sort" />
                                </th>
                                <th>
                                    Date & Time
                                    <font-awesome-icon icon="sort" />
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'TokenInvestTradeHistory',
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
