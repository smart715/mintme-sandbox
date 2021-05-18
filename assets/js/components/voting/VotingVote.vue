<template>
    <div class="card h-100">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <slot name="title">{{ $t('voting.vote') }}</slot>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column">
                <div
                    v-for="(option, key) in voting.options"
                    :key="key"
                    class="d-flex align-items-center m-2 p-2 bg-primary c-pointer"
                    :class="{border: selected === key}"
                    @click="select(key)"
                >
                    <div class="flex-1 text-center">{{ option.title }}</div>
                </div>
                <button :disabled="btnDisabled" class="btn btn-primary m-2" @click="vote">
                    {{ $t('voting.vote') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import {NotificationMixin, LoggerMixin, RebrandingFilterMixin} from '../../mixins';
import {mapGetters, mapActions} from 'vuex';

export default {
    name: 'VotingVote',
    mixins: [NotificationMixin, LoggerMixin, RebrandingFilterMixin],
    data() {
        return {
            selected: -1,
            requesting: false,
        };
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
            voting: 'getCurrentVoting',
        }),
        ...mapGetters('tradeBalance', {
            quoteBalance: 'getQuoteBalance',
        }),
        btnDisabled() {
            return this.selected < 0 || this.requesting || 0 === this.quoteBalance;
        },
    },
    methods: {
        ...mapActions('voting', [
            'updateVoting',
        ]),
        select(i) {
            this.selected = i;
        },
        vote() {
            if (this.quoteBalance > 0) {
                this.storeVote();
                return;
            }

            this.notifyInfo(
                this.$t('voting.vote.zero_balance', {
                    amount: 0,
                    currency: this.rebrandingFunc(this.tokenName),
                })
            );
        },
        storeVote() {
            this.requesting = true;
            this.$axios.single.post(
                this.$routing.generate('user_vote', {optionId: this.voting.options[this.selected].id})
            )
                .then(({data}) => this.updateVoting(data.voting))
                .catch((err) => {
                    this.notifyError(this.$t(err.response.data.message || 'toasted.error.try_later'));
                    this.sendLogs('error', err);
                })
                .then(() => this.requesting = false);
        },
    },
};
</script>
