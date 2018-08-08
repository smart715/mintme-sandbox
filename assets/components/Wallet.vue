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
        <table class="table">
            <thead>
                <tr>
                    <th>Name <font-awesome-icon icon="sort" /></th>
                    <th>Amount <font-awesome-icon icon="sort" /></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="text-orange">
                    <td>own token</td>
                    <td>0.000002</td>
                    <td>^</td>
                </tr>
                <tr>
                    <td>some token</td>
                    <td>0.000002</td>
                    <td>^</td>
                </tr>
                <tr>
                    <td>another token</td>
                    <td>0.0001</td>
                    <td>^</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faSort, faQuestion} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faSort, faQuestion);

export default {
    name: 'Wallet',
    data() {
        return {};
    },
    components: {
        FontAwesomeIcon,
    },
};
</script>
