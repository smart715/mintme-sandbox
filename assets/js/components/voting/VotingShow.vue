<template>
    <div class="row">
        <div class="col-md-12 mt-3 text-break">
            <h2>{{ voting.title }}</h2>
            <p>
                <span class="text-primary">{{ $t('voting.form.start_date') }}</span> {{ startDate }} |
                <span class="text-primary">{{ $t('voting.form.end_date') }}</span> {{ endDate }} |
                <span class="text-primary">{{ $t('voting.form.created_by') }}</span>
                <avatar
                    :image="getProfileImageUrl"
                    size="small"
                    class="d-inline-block align-bottom"
                />
                <a :href="profileUrl">{{ voting.creatorProfile.nickname }}</a>
            </p>
        </div>
        <div class="col-md-4 ml-3 d-md-none">
            <voting-vote
                v-if="!voting.closed"
                :min-amount="minAmount"
                :logged-in="loggedIn"
                :is-owner="isOwner"
                class="w-100"
            />
        </div>
        <div class="row col-md-4 ml-3 d-none d-md-block">
            <voting-vote
                v-if="!voting.closed"
                :min-amount="minAmount"
                :logged-in="loggedIn"
                :is-owner="isOwner"
                class="w-100"
            />
            <div class="mb-3 w-100 d-none d-md-block">
                <div class="card">
                    <voting-result
                        :token-avatar="tokenAvatar"
                        :is-token="isToken"
                        class="mt-3"
                    />
                </div>
            </div>
        </div>
        <div
            class="col-md-8 mb-3"
            :class="{'col-md-12': voting.closed}"
        >
            <div class="card">
                <div class="m-3">
                    <plain-text-view :text="voting.description" class="text-color-description" />
                </div>
            </div>
            <div class="card d-none d-md-block">
                <voting-votes
                    :token-avatar="tokenAvatar"
                    :is-token="isToken"
                    class="mt-3 pt-3"
                />
            </div>
        </div>
        <div class="col-md-4 mb-3 d-md-none">
            <div class="card">
                <voting-result
                    :token-avatar="tokenAvatar"
                    :is-token="isToken"
                    class="mt-3"
                />
            </div>
        </div>
        <div class="col-md-8 d-md-none">
            <div class="card">
                <voting-votes
                    :token-avatar="tokenAvatar"
                    :is-token="isToken"
                    class="mt-3"
                />
            </div>
        </div>
    </div>
</template>

<script>
import VotingVote from './VotingVote';
import VotingVotes from './VotingVotes';
import VotingResult from './VotingResult';
import PlainTextView from '../UI/PlainTextView';
import {GENERAL} from '../../utils/constants';
import {VotingInitMixin, TokenPageTabSelector} from '../../mixins';
import {mapGetters, mapMutations} from 'vuex';
import moment from 'moment';
import Avatar from '../Avatar.vue';

export default {
    name: 'VotingShow',
    components: {
        VotingResult,
        VotingVote,
        VotingVotes,
        PlainTextView,
        Avatar,
    },
    mixins: [
        VotingInitMixin,
        TokenPageTabSelector,
    ],
    props: {
        tabIndex: Number,
        minAmount: {
            type: Number,
            default: 0,
        },
        votingProp: {
            type: Object,
            default: () => {},
        },
        loggedIn: Boolean,
        tokenAvatar: String,
        isOwner: Boolean,
        isToken: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        ...mapGetters('voting', {
            voting: 'getCurrentVoting',
        }),
        startDate() {
            return moment(this.voting.createdAt).format(GENERAL.dateTimeFormat);
        },
        endDate() {
            return moment(this.voting.endDate).format(GENERAL.dateTimeFormat);
        },
        profileUrl() {
            return this.$routing.generate('profile-view', {
                nickname: this.voting.creatorProfile.nickname,
            });
        },
        getProfileImageUrl() {
            return this.voting?.creatorProfile?.image?.avatar_small;
        },
    },
    methods: {
        ...mapMutations('voting', [
            'setCurrentVoting',
        ]),
        setPageTitle() {
            if (false === this.isShowVotingTab) {
                document.title = this.voting.title + ' - '
                    + this.$t('voting.voting') + ' | mintMe';
            }
        },
    },
    watch: {
        tabIndex(val, oldVal) {
            this.setPageTitle();
        },
    },
    created() {
        if (!this.voting.id && this.votingProp.id) {
            this.setCurrentVoting(this.votingProp);
        }
    },
    mounted() {
        this.setPageTitle();
    },
};
</script>
