const storage = {
    namespaced: true,
    state: {
        balance: {
            tokenExchangeAmount: null,
        },
        stats: {
            releasePeriod: '-',
            hourlyRate: '-',
            releasedAmount: '-',
            frozenAmount: '-',
        },
    },
    getters: {
        getTokenExchangeAmount: function(state) {
            return state.balance.tokenExchangeAmount;
        },
        getStats: function(state) {
            return state.stats;
        },
        getReleasePeriod: function(state) {
            return state.stats.releasePeriod;
        },
    },
    mutations: {
        setTokenExchangeAmount: function(state, n) {
            state.balance.tokenExchangeAmount = n;
        },
        setStats: function(state, n) {
            state.stats = n;
        },
    },
};

export default storage;
