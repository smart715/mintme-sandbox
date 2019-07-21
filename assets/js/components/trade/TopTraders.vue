<template>
    <div class="card h-100">
        <div class="card-header">
            Top Traders
        </div>
        <div class="card-body p-0">
            <div class="table-responsive fix-height" ref="traders">
                <template v-if="loaded">
                    <b-table v-if="hasTraders"
                    :items="traders"
                    :fields="fields">
                    <template slot="trader" slot-scope="row">
                        {{ row.value }}
                        <img
                            src="../../../img/avatar.png"
                            class="float-right"
                            alt="avatar">
                    </template>
                </b-table>
                    <div v-else>
                        <p class="text-center p-5">No Traders yet</p>
                    </div>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
            <div class="text-center pb-2" v-if="showDownArrow">
                <img
                    src="../../../img/down-arrows.png"
                    class="icon-arrows-down c-pointer"
                    alt="arrow down"
                    @click="scrollDown">
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import {GENERAL} from '../../utils/constants';
export default {
    name: 'TopTraders',
    props: {
      tokenName: String,
    },
    data() {
        return {
            traders: null,
            fields: {
                trader: {
                    label: 'Trader',
                },
                date: {
                    label: 'Date',
                },
                amount: {
                    label: 'Amount',
                },
            },
        };
    },
    computed: {
        showDownArrow: function() {
            return null !== this.traders && this.traders.length > 7;
        },
        loaded: function() {
            return null !== this.traders;
        },
        hasTraders: function() {
            return this.traders.length > 0;
        },
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.traders;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        getTraders: function() {
            this.$axios.single.get(this.$routing.generate('top_traders', {
                name: this.tokenName,
            }))
            .then(({data}) => this.traders = data.map((row) => {
                return {
                    trader: `${row.user.profile.firstName} ${row.user.profile.lastName}`,
                    date: moment.unix(row.timestamp).format(GENERAL.dateFormat),
                    amount: Math.round(row.balance),
                };
            }));
        },
    },
    mounted: function() {
        this.getTraders();
        setInterval(() => this.getTraders(), 20 * 1000);
    },
};
</script>
