export default {
    namespaced: true,
    state: {
        requesting: false,
        loaded: false,
        rates: {},
    },
    getters: {
        getRequesting(state) {
            return state.requesting;
        },
        getLoaded(state) {
            return state.loaded;
        },
        getRates(state) {
            return state.rates;
        },
    },
    mutations: {
        setRequesting(state, n) {
            state.requesting = n;
        },
        setLoaded(state, n) {
            state.loaded = n;
        },
        setRates(state, n) {
            state.rates = n;
        },
    },
};
