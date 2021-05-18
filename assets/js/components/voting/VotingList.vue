<template>
    <div>
        <div class="card h-100 posts-container">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <slot name="title">{{ $t('voting.propositions') }}</slot>
                    <a
                        :href="newVotingUrl"
                        class="btn btn-primary"
                        :class="{disabled: disableNewBtn}"
                        @click.prevent="goToCreate"
                    >
                        {{ $t('voting.new_proposition') }}
                    </a>
                </div>
            </div>
            <div v-if="votings.length" class="card-body posts overflow-hidden position-relative">
                <voting-proposition
                    v-for="(proposition, key) in votings"
                    :key="key"
                    :proposition="proposition"
                    class="mb-2"
                    @go-to-show="$emit('go-to-show')"
                />
            </div>
            <div v-else class="card-body h-100 d-flex align-items-center justify-content-center">
                <span class="text-center py-4 ">
                    {{ $t('voting.no_propositions') }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import VotingProposition from './VotingProposition';
import {VotingInitMixin, NotificationMixin, RebrandingFilterMixin} from '../../mixins';
import {mapGetters} from 'vuex';

export default {
    name: 'VotingList',
    mixins: [
        VotingInitMixin,
        NotificationMixin,
        RebrandingFilterMixin,
    ],
    components: {
        VotingProposition,
    },
    props: {
        minAmount: Number,
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
            votings: 'getVotings',
        }),
        ...mapGetters('tradeBalance', {
            quoteBalance: 'getQuoteBalance',
        }),
        newVotingUrl() {
            return this.$routing.generate('token_create_voting', {
                name: this.rebrandingFunc(this.tokenName),
            });
        },
        disableNewBtn() {
            return 0 === this.quoteBalance;
        },
    },
    methods: {
        goToCreate() {
            if (this.minAmount > this.quoteBalance) {
                this.notifyInfo(
                    this.$t('voting.create.min_amount_required', {
                        amount: this.minAmount,
                        currency: this.rebrandingFunc(this.tokenName),
                    })
                );
                return;
            }

            this.$emit('go-to-create');
        },
    },
};
</script>
