const storage = {
    namespaced: true,
    state: {
        sellOrders: [],
        buyOrders: [],
    },
    getters: {
        getSellOrders(state) {
            return state.sellOrders;
        },
        getBuyOrders(state) {
            return state.buyOrders;
        },
    },
    mutations: {
        setSellOrders(state, n) {
            state.sellOrders = n;
        },
        setBuyOrders(state, n) {
            state.buyOrders = n;
        },
    },
};

export default storage;
