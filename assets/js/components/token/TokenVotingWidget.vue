<template>
    <div>
        <voting-list
            v-if="activePageMap.list"
            :token-name-prop="tokenName"
            :token-avatar="tokenAvatar"
            :votings-prop="votings"
            :min-amount="minAmountPropose"
            :logged-in="loggedIn"
            :is-token-page="isTokenPage"
            :is-owner="isOwner"
            :is-loading-list="isLoadingList"
            @fetch-votings="fetchVotings"
            @go-to-create="goToCreateVoting"
            @go-to-show="goToShowVoting"
            @counter-refreshed="$emit('counter-refreshed')"
        />
        <voting-create
            v-if="activePageMap.create"
            :token-name-prop="tokenName"
            :votings-prop="votings"
            :is-token-page="!!tokenName"
            @voting-created="newVotingCreated"
        />
        <voting-show
            v-if="activePageMap.show && currentVoting"
            :token-avatar="tokenAvatar"
            :min-amount="minAmountVote"
            :token-name-prop="tokenName"
            :votings-prop="votings"
            :voting-prop="currentVoting"
            :logged-in="loggedIn"
            :is-owner="isOwner"
            :is-token="true"
        />
    </div>
</template>

<script>
import VotingList from '../voting/VotingList';
import VotingCreate from '../voting/VotingCreate';
import VotingShow from '../voting/VotingShow';
import {mapGetters, mapMutations} from 'vuex';
import {NotificationMixin} from '../../mixins';

window.addEventListener('popstate', function() {
    window.location.reload();
});

const VOTING_PAGES = {
    voting_list: 'voting',
    create_voting: 'create-voting',
    show_voting: 'show-voting',
};

export default {
    name: 'TokenVotingWidget',
    mixins: [NotificationMixin],
    components: {
        VotingList,
        VotingCreate,
        VotingShow,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        votings: Array,
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
        activePage: {
            type: String,
            default: '',
        },
        voting: {
            type: Object,
            default: () => {},
        },
        loggedIn: {
            type: Boolean,
            default: false,
        },
        isOwner: {
            type: Boolean,
            default: false,
        },
        isTokenPage: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            activePageName: this.activePage,
            isLoadingList: false,
        };
    },
    computed: {
        ...mapGetters('voting', {
            currentVoting: 'getCurrentVoting',
        }),
        activePageMap() {
            return {
                list: this.activePageName === VOTING_PAGES.voting_list,
                create: this.activePageName === VOTING_PAGES.create_voting,
                show: this.activePageName === VOTING_PAGES.show_voting,
            };
        },
    },
    created() {
        if (this.voting) {
            this.setCurrentVoting(this.voting);
        }
    },
    mounted() {
        this.$emit('mounted');
        this.setVotingsCount(this.totalVotingCount);
    },
    methods: {
        ...mapMutations('voting', [
            'setVotings',
            'insertVotings',
            'setCurrentVoting',
            'setVotingsCount',
            'addVoting',
        ]),
        reloadVotingsList() {
            this.setVotings([]);
            this.fetchVotings({offset: 0});
        },
        goToCreateVoting() {
            this.goToPage('token_create_voting', {name: this.tokenName});
            this.activePageName = VOTING_PAGES.create_voting;

            this.$emit('page-changed', this.activePageName);
        },
        goToShowVoting(voting) {
            this.setCurrentVoting(voting);
            this.goToPage('token_show_voting', {name: this.tokenName, slug: voting.slug});
            this.activePageName = VOTING_PAGES.show_voting;

            this.$emit('page-changed', this.activePageName);
        },
        goToVotingsList() {
            this.goToPage('token_list_voting', {name: this.tokenName});
            this.activePageName = VOTING_PAGES.voting_list;

            this.fetchVotings();

            this.$emit('page-changed', this.activePageName);
        },
        goToPage(page, params = {}) {
            history.pushState({}, 'Mintme', this.$routing.generate(page, params));
        },
        newVotingCreated(voting) {
            this.$emit('voting-created', voting);

            this.reloadVotingsList();
            this.goToShowVoting(voting);
        },
        async fetchVotings(params) {
            const offset = params.offset;
            const limit = params.limit || null;

            this.isLoadingList = true;
            try {
                const {data} = await this.$axios.retry.get(this.$routing.generate(
                    'list_voting',
                    {
                        tokenName: this.tokenName,
                    },
                ),
                {params: {offset, limit}}
                );

                this.insertVotings(data);
            } catch (err) {
                this.notifyError(this.$t('toasted.error.load_data'));
                this.$logger.error('Error while loading votings', err);
            } finally {
                this.isLoadingList = false;
            }
        },
    },
    watch: {
        activePage: function() {
            this.activePageName = this.activePage;
        },
    },
};
</script>
