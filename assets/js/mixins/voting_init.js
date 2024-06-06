import {mapActions, mapGetters} from 'vuex';

export default {
    props: {
        tokenNameProp: String,
        votingsProp: Array,
        initializeOnce: Boolean,
    },
    computed: {
        ...mapGetters('voting', {
            isInitialized: 'getIsInitialized',
        }),
    },
    methods: {
        ...mapActions('voting', [
            'init',
        ]),
    },
    mounted() {
        if (this.initializeOnce && this.isInitialized) {
            return;
        }

        this.init({
            tokenName: this.tokenNameProp,
            votings: this.votingsProp,
        });
    },
};
