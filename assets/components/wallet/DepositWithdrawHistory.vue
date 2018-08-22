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
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>12-12-1970</td>
                    <td>Deposit</td>
                    <td>0xe1f05911dba854fwc...</td>
                    <td>0.0000</td>
                    <td>0</td>
                    <td>OK</td>
                    <td>9999</td>
                </tr>
                <tr>
                    <td>12-12-1970</td>
                    <td>Withdraw</td>
                    <td>0xe1f05911dba854fwc...</td>
                    <td>0.0000</td>
                    <td>0</td>
                    <td>OK</td>
                    <td>9999</td>
                </tr>
                <tr>
                    <td>12-12-1970</td>
                    <td>Deposit</td>
                    <td>0xe1f05911dba854fwc...</td>
                    <td>0.0000</td>
                    <td>0</td>
                    <td>OK</td>
                    <td>9999</td>
                </tr>
                <tr>
                    <td>12-12-1970</td>
                    <td>Deposit</td>
                    <td>0xe1f05911dba854fwc...</td>
                    <td>0.0000</td>
                    <td>0</td>
                    <td>OK</td>
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
    name: 'DepositWithdrawHistory',
    data() {
        return {};
    },
    components: {
        FontAwesomeIcon,
    },
};
</script>
