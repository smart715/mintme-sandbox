const storage = {
    namespaced: true,
    state: {
        stats: {
            releasePeriod: '-',
            hourlyRate: '-',
            releasedAmount: '-',
            frozenAmount: '-',
        },
    },
    getters: {
        getStats: function(state) {
            return state.stats;
        },
    },
    mutations: {
        setStats: function(state, n) {
            state.stats = n;
        },
    },
};

export default storage;
