<template>
    <div class="card h-100 top-holders">
        <div class="card-header">
            {{ $t('trade.top_holders.header') }}
        </div>
        <div v-if="loaded && hasTraders" class="card-body p-0">
            <div class="table-responsive">
                <b-table
                    ref="table"
                    :items="holders"
                    :fields="fields"
                >
                    <template v-slot:cell(trader)="row">
                        <holder-name :value="row.value" :img="row.item.traderAvatar" :url="row.item.url"/>
                    </template>
                </b-table>
            </div>
        </div>
        <div v-else class="card-body h-100 d-flex align-items-center justify-content-center">
            <span v-if="loaded" class="text-center py-4">
                {{ $t('trade.top_holders.no_holders') }}
            </span>
            <span v-else class="py-4">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </span>
        </div>
    </div>
</template>

<script>
import {formatMoney} from '../../utils';
import {FiltersMixin, LoggerMixin, NotificationMixin, WebSocketMixin} from '../../mixins';
import HolderName from './HolderName';

export default {
    name: 'TopHolders',
    mixins: [FiltersMixin, LoggerMixin, NotificationMixin, WebSocketMixin],
    components: {
        HolderName,
    },
    props: {
        tokenName: String,
        tradersProp: {
            type: Array,
            default: () => null,
        },
    },
    data() {
        return {
            traders: this.tradersProp,
            fields: [
                {
                    key: 'trader',
                    label: this.$t('trade.top_holders.trader'),
                },
                {
                    key: 'amount',
                    label: this.$t('trade.top_holders.amount'),
                    formatter: formatMoney,
                },
            ],
        };
    },
    computed: {
        loaded: function() {
            return null !== this.traders;
        },
        hasTraders: function() {
            return this.traders.length > 0;
        },
        holders: function() {
            return this.traders.map((row) => {
                return {
                    trader: row.user.profile.nickname,
                    traderAvatar: row.user.profile.image.avatar_small,
                    url: this.$routing.generate('profile-view', {nickname: row.user.profile.nickname}),
                    amount: Math.round(row.balance),
                };
            });
        },
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        getTraders: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('top_holders', {
                    name: this.tokenName,
                }))
                    .then(({data}) => {
                        this.traders = data;
                        resolve(data);
                    })
                    .catch((err) => reject(
                        this.sendLogs('error', 'Can not get top holders', err)
                    ));
            });
        },
    },
    mounted: function() {
        if (!this.traders) {
            this.getTraders();
        }

        this.getTraders().then(() => {
            this.addMessageHandler((response) => {
                if ('deals.update' === response.method) {
                    this.getTraders();
                }
            }, 'update-top-holders', 'TopHolders');
        });
    },
};
</script>
