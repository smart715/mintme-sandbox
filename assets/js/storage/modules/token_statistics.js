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
        tokenDeleteSoldLimit: null,
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
        getTokenDeleteSoldLimit: function(state) {
            return state.tokenDeleteSoldLimit;
        },
    },
    mutations: {
        setTokenExchangeAmount: function(state, n) {
            state.balance.tokenExchangeAmount = n;
        },
        setStats: function(state, n) {
            state.stats = n;
        },
        setTokenDeleteSoldLimit: function(state, n) {
            state.tokenDeleteSoldLimit = n;
        },
    },
};

export default storage;
