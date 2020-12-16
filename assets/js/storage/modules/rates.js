export default {
    namespaced: true,
    state: {
        requesting: false,
        rates: {},
    },
    getters: {
        getRequesting(state) {
            return state.requesting;
        },
        getRates(state) {
            return state.rates;
        },
    },
    mutations: {
        setRequesting(state, n) {
            state.requesting = n;
        },
        setRates(state, n) {
            state.rates = n;
        },
    },
};
