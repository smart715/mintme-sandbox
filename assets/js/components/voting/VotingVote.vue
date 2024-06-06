<template>
    <div class="card container-items-vote mb-4">
        <div class="m-3">
            <ul class="list-group text-justify">
                <li
                    v-for="(option, key) in voting.options"
                    :key="key"
                    class="mb-3 c-pointer list-group-item item-vote"
                    :class="{'active' : selected === key}"
                    @click="select(key)"
                >
                    <span :class="{'text-item-select' : selected === key}">
                        {{option.title}}
                    </span>
                </li>
            </ul>
            <button
                :disabled="btnDisabled"
                class="btn btn-primary btn-lg btn-block"
                @click="vote"
            >
                {{ $t('voting.vote') }}
            </button>
        </div>
    </div>
</template>

<script>
import {NotificationMixin, RebrandingFilterMixin} from '../../mixins';
import {
    mapGetters,
    mapActions,
} from 'vuex';

export default {
    name: 'VotingVote',
    mixins: [
        NotificationMixin,
        RebrandingFilterMixin,
    ],
    data() {
        return {
            selected: -1,
            requesting: false,
        };
    },
    props: {
        minAmount: {
            type: Number,
            default: 0,
        },
        loggedIn: Boolean,
        isOwner: Boolean,
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
            voting: 'getCurrentVoting',
        }),
        ...mapGetters('tradeBalance', {
            quoteFullBalance: 'getQuoteFullBalance',
        }),
        btnDisabled() {
            return 0 > this.selected || this.requesting || 0 === this.quoteFullBalance;
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
            if (!this.loggedIn) {
                this.notifyInfo(this.$t('voting.vote.not_logged_in'));

                return;
            }

            if (!this.isOwner && this.quoteFullBalance < this.minAmount) {
                this.notifyInfo(
                    this.$t('voting.vote.zero_balance', {
                        amount: this.minAmount,
                        currency: this.rebrandingFunc(this.tokenName),
                    })
                );

                return;
            }

            this.storeVote();
        },
        async storeVote() {
            this.requesting = true;
            try {
                const {data} = await this.$axios.single.post(this.$routing.generate(
                    'user_vote',
                    {optionId: this.voting.options[this.selected].id},
                ));

                this.updateVoting(data.voting);
            } catch (err) {
                this.notifyError(this.$t(err.response.data.message || 'toasted.error.try_later'));
                this.$logger.error(err);
            } finally {
                this.requesting = false;
            }
        },
    },
};
</script>
