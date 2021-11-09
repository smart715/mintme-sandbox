const storage = {
    namespaced: true,
    state: {
        query: [],
    },
    mutations: {
        addOrder: function(state, number) {
            state.query.push(number);
        },
        deleteOrder: function(state) {
            state.query.splice(0, 1);
        },
    },
    getters: {
        getQuery: function(state) {
            return state.query;
        },
    },
};

export default storage;
