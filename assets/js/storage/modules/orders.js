const storage = {
    namespaced: true,
    state: {
        sellOrders: [],
        buyOrders: [],
        serviceUnavailable: false,
    },
    getters: {
        isServiceUnavailable(state) {
            return state.serviceUnavailable;
        },
        getSellOrders(state) {
            return state.sellOrders;
        },
        getBuyOrders(state) {
            return state.buyOrders;
        },
    },
    mutations: {
        setServiceUnavailable(state, n) {
            state.serviceUnavailable = n;
        },
        setSellOrders(state, n) {
            state.sellOrders = n;
        },
        setBuyOrders(state, n) {
            state.buyOrders = n;
        },
    },
};

export default storage;
