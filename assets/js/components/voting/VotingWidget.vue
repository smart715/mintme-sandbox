<template>
    <div>
        <voting-list
            v-if="activePage.list"
            :token-name-prop="tokenNameProp"
            :token-avatar="tokenAvatar"
            :votings-prop="votingsProp"
            :min-amount="minAmountPropose"
            :logged-in="loggedIn"
            :is-loading-list="isLoadingList"
            @fetch-votings="fetchVotings"
            @go-to-create="goToCreateVoting"
            @go-to-show="goToShowVoting"
        />
        <voting-create
            v-if="activePage.create"
            :token-name-prop="tokenNameProp"
            :votings-prop="votingsProp"
        />
        <voting-show
            v-if="activePage.show"
            :min-amount="minAmountVote"
            :token-name-prop="tokenNameProp"
            :votings-prop="votingsProp"
            :voting-prop="votingProp"
            :logged-in="loggedIn"
            :token-avatar="tokenAvatar"
        />
    </div>
</template>

<script>
import VotingList from './VotingList';
import VotingCreate from './VotingCreate';
import VotingShow from './VotingShow';
import {mapGetters, mapMutations} from 'vuex';

window.addEventListener('popstate', function() {
    window.location.reload();
});

const VOTING_PAGES = {
    create_voting: 'create_voting',
    show_voting: 'show_voting',
};

export default {
    name: 'VotingWidget',
    components: {
        VotingList,
        VotingCreate,
        VotingShow,
    },
    props: {
        tokenNameProp: String,
        tokenAvatar: String,
        votingsProp: Array,
        votingAmount: Number,
        minAmountPropose: {
            type: Number,
            default: 100,
        },
        minAmountVote: {
            type: Number,
            default: 0,
        },
        totalVotingCount: {
            type: Number,
            default: 0,
        },
        activePageProp: {
            type: String,
            default: '',
        },
        votingProp: {
            type: Object,
            default: () => {},
        },
        loggedIn: Boolean,
    },
    data() {
        return {
            activePageName: this.activePageProp,
            isLoadingList: false,
        };
    },
    mounted() {
        this.setVotingsCount(this.totalVotingCount);
    },
    computed: {
        ...mapGetters('voting', {
            voting: 'getCurrentVoting',
        }),
        activePage() {
            return {
                list: '' === this.activePageName,
                create: this.activePageName === VOTING_PAGES.create_voting,
                show: this.activePageName === VOTING_PAGES.show_voting,
            };
        },
    },
    methods: {
        ...mapMutations('voting', [
            'setVotingsCount',
            'insertVotings',
        ]),
        async fetchVotings(params) {
            const offset = params.offset;

            this.isLoadingList = true;

            try {
                const response = await this.$axios.retry.get(
                    this.$routing.generate('list_voting_crypto'),
                    {params: {offset}},
                );

                this.insertVotings(response.data);
            } catch (err) {
                this.notifyError(this.$t('toasted.error.load_data'));
                this.$logger.error('Error while loading votings', err);
            } finally {
                this.isLoadingList = false;
            }
        },
        goToCreateVoting() {
            this.goToPage(VOTING_PAGES.create_voting);
        },
        goToShowVoting() {
            this.goToPage(VOTING_PAGES.show_voting, {slug: this.voting.slug});
        },
        goToPage(page, params = {}) {
            history.pushState({}, 'Mintme', this.$routing.generate(page, params));
            this.activePageName = page;
        },
    },
};
</script>
