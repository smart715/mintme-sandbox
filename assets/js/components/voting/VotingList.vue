<template>
    <div class="row">
        <div class="col-md-6 col-sm-12 mt-3">
            <slot name="title">
                <h2>
                    {{ $t('voting.propositions') }}
                    <span class="text-primary">
                        {{ $t('voting.propositions.list') }}
                    </span>
                    <guide class="tooltip-center">
                        <template slot="body">
                            <span v-html="propositionTooltip" />
                        </template>
                    </guide>
                </h2>
            </slot>
        </div>
        <div class="mt-3 col-md-6 col-sm-12 btn-new-proposition d-flex align-items-center">
            <div v-b-tooltip="disabledTooltip">
                <a
                    :href="newVotingUrl"
                    class="btn btn-primary font-weight-bold"
                    :class="{disabled: disableNewBtn}"
                    @click.prevent="goToCreate"
                >
                    <div class="pl-2 pr-2 pt-1 pb-1">
                        <font-awesome-icon
                            v-if="loadingBalances && !serviceUnavailable"
                            icon="circle-notch"
                            class="loading-spinner"
                            fixed-width
                            spin
                        />
                        <font-awesome-icon v-else icon="check-square" />
                        {{ $t('voting.new_proposition') }}
                    </div>
                </a>
            </div>
        </div>
        <div class="col-12">
            <div v-if="votings.length">
                <voting-proposition
                    v-for="(proposition, key) in votings"
                    :key="key"
                    :proposition="proposition"
                    class="list-group"
                    :is-token-page="isTokenPage"
                    :is-owner="isOwner"
                    @go-to-show="$emit('go-to-show', proposition)"
                    @proposition-deleted="onDeletePropositionSuccess(proposition)"
                />
                <div v-if="showLoadMore" class="d-flex justify-content-center my-4">
                    <m-button
                        v-if="!isLoadingList"
                        type="secondary-rounded"
                        @click="emitFetchVotings"
                    >
                        {{ $t('see_more') }}
                    </m-button>
                    <div
                        v-if="isLoadingList"
                        class="spinner-border spinner-border-sm"
                        role="status"
                        ref="loadingMoreTrigger"
                    ></div>
                </div>
            </div>
            <div v-else class="d-flex align-items-center justify-content-center">
                <span class="text-center py-4">
                    {{ $t('voting.no_propositions') }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import {VBTooltip} from 'bootstrap-vue';
import VotingProposition from './VotingProposition';
import {mapGetters, mapMutations} from 'vuex';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faPlusSquare, faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {MButton} from '../UI';
import {
    VotingInitMixin,
    NotificationMixin,
    RebrandingFilterMixin,
} from '../../mixins';
import {generateCoinAvatarHtml} from '../../utils';

library.add(faPlusSquare, faCircleNotch);
import Guide from '../Guide';

export default {
    name: 'VotingList',
    mixins: [
        VotingInitMixin,
        NotificationMixin,
        RebrandingFilterMixin,
    ],
    components: {
        MButton,
        Guide,
        VotingProposition,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        tokenAvatar: String,
        isTokenPage: Boolean,
        minAmount: Number,
        loggedIn: Boolean,
        isOwner: Boolean,
        isLoadingList: Boolean,
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
            votings: 'getVotings',
            votingsCount: 'getVotingsCount',
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            quoteFullBalance: 'getQuoteFullBalance',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        currentlyShownAmount() {
            return this.votings.length;
        },
        showLoadMore() {
            return this.votingsCount > this.currentlyShownAmount;
        },
        loadingBalances() {
            return null === this.balances;
        },
        disabledTooltip() {
            return this.serviceUnavailable
                ? {
                    title: this.$t('toasted.error.service_unavailable_support'),
                    boundary: 'viewport',
                    placement: 'bottom',
                }
                : null;
        },
        disableNewBtn() {
            return 0 === this.quoteFullBalance || this.serviceUnavailable;
        },
        propositionTooltip() {
            return this.isTokenPage
                ? this.$t('voting.tooltip.page_token.proposition', {
                    tokenName: this.tokenName,
                    tokenAvatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
                    minAmount: this.minAmount,
                })
                : this.$t('voting.tooltip.propositions', {
                    minAmount: this.minAmount,
                });
        },
        newVotingUrl() {
            return this.$routing.generate('create_voting');
        },
    },
    methods: {
        ...mapMutations('voting', [
            'deleteVoting',
        ]),
        emitFetchVotings() {
            this.$emit('fetch-votings', {
                offset: this.currentlyShownAmount,
            });
        },
        goToCreate() {
            if (!this.loggedIn) {
                this.notifyInfo(this.$t('voting.create.not_logged_in'));
                return;
            }

            if (!this.isOwner && this.minAmount > this.quoteFullBalance) {
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
        onDeletePropositionSuccess(proposition) {
            this.deleteVoting(proposition);
            this.$emit('counter-refreshed');
        },
    },
};
</script>
