const storage = {
    namespaced: true,
    state: {
        releasePeriod: null,
        hourlyRate: null,
        releasedAmount: null,
        frozenAmount: null,
    },
    getters: {
        getReleasePeriod: function(state) {
            return state.releasePeriod;
        },
        getHourlyRate: function(state) {
            return state.hourlyRate;
        },
        getReleasedAmount: function(state) {
            return state.releasedAmount;
        },
        getFrozenAmount: function(state) {
            return state.frozenAmount;
        },
    },
    mutations: {
        setReleasePeriod: function(state, n) {
            state.releasePeriod = n;
        },
        setHourlyRate: function(state, n) {
            state.hourlyRate = n;
        },
        setReleasedAmount: function(state, n) {
            state.releasedAmount = n;
        },
        setFrozenAmount: function(state, n) {
            state.frozenAmount = n;
        },
    },
};

export default storage;
