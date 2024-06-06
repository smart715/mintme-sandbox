const storage = {
    namespaced: true,
    state: {
        currentMarketIndex: null,
        markets: null,
    },
    getters: {
        getCurrentMarket: function(state) {
            return state.markets[state.currentMarketIndex];
        },
        getMarkets: function(state) {
            return state.markets;
        },
    },
    mutations: {
        setCurrentMarketIndex: function(state, n) {
            state.currentMarketIndex = n;
        },
        setMarkets: function(state, n) {
            state.markets = n;
        },
    },
};

export default storage;
