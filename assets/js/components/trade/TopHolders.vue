<template>
    <div class="card h-100">
        <div class="card-header">
            Top Holders
        </div>
        <div class="card-body p-0">
            <div class="table-responsive fixed-head-table">
                <template v-if="loaded">
                    <b-table v-if="hasTraders"
                     ref="table"
                    :items="traders"
                    :fields="fields">
                    <template slot="trader" slot-scope="row">
                        <a :href="row.item.url">
                            <span v-b-tooltip="{title: row.value, boundary:'viewport'}">
                                {{ row.value | truncate(50) }}
                            </span>
                        </a>
                        <img
                            src="../../../img/avatar.png"
                            class="float-right"
                            alt="avatar">
                    </template>
                </b-table>
                    <div v-else>
                        <p class="text-center p-5">No Holders yet</p>
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
import {formatMoney} from '../../utils';
import {GENERAL} from '../../utils/constants';
import {FiltersMixin} from '../../mixins';

export default {
    name: 'TopHolders',
    mixins: [FiltersMixin],
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
                    formatter: formatMoney,
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
            let parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        getTraders: function() {
            this.$axios.single.get(this.$routing.generate('top_holders', {
                name: this.tokenName,
            }))
            .then(({data}) => this.traders = data.map((row) => {
                return {
                    trader: `${row.user.profile.firstName} ${row.user.profile.lastName}`,
                    url: this.$routing.generate('profile-view', {pageUrl: row.user.profile.page_url}),
                    date: row.timestamp ? moment.unix(row.timestamp).format(GENERAL.dateFormat) : '-',
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
