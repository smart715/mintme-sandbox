const storage = {
    namespaced: true,
    state: {
        minOrder: '',
    },
    getters: {
        getMinOrder(state) {
            return state.minOrder;
        },
    },
    mutations: {
        setMinOrder(state, n) {
            state.minOrder = n;
        },
    },
};

export default storage;
