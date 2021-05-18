<template>
    <div>
        <voting-list
            v-if="activePage.list"
            :token-name-prop="tokenNameProp"
            :votings-prop="votingsProp"
            :min-amount="minAmount"
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
            :token-name-prop="tokenNameProp"
            :votings-prop="votingsProp"
            :voting-prop="votingProp"
        />
    </div>
</template>

<script>
import VotingList from './VotingList';
import VotingCreate from './VotingCreate';
import VotingShow from './VotingShow';
import {mapGetters} from 'vuex';

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
        votingsProp: Array,
        minAmount: Number,
        activePageProp: {
            type: String,
            default: '',
        },
        votingProp: {
            type: Object,
            default: () => {},
        },
    },
    data() {
        return {
            activePageName: this.activePageProp,
        };
    },
    computed: {
        ...mapGetters('voting', {
            voting: 'getCurrentVoting',
        }),
        activePage() {
            return {
                list: this.activePageName === '',
                create: this.activePageName === VOTING_PAGES.create_voting,
                show: this.activePageName === VOTING_PAGES.show_voting,
            };
        },
    },
    methods: {
        goToCreateVoting() {
            this.goToPage(VOTING_PAGES.create_voting);
        },
        goToShowVoting() {
            this.goToPage(VOTING_PAGES.show_voting, {id: this.voting.id});
        },
        goToPage(page, params = {}) {
            history.pushState({}, 'Mintme', this.$routing.generate(page, params));
            this.activePageName = page;
        },
    },
};
</script>
