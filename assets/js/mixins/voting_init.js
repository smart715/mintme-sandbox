import {mapActions} from 'vuex';

export default {
    props: {
        tokenNameProp: String,
        votingsProp: Array,
    },
    methods: {
        ...mapActions('voting', [
            'init',
        ]),
    },
    mounted() {
        this.init({
            tokenName: this.tokenNameProp,
            votings: this.votingsProp,
        });
    },
};
