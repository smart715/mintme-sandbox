const storage = {
    namespaced: true,
    state: {
        contactName: null,
        currentThreadId: null,
    },
    getters: {
        getContactName: function(state) {
            return state.contactName;
        },
        getCurrentThreadId: function(state) {
            return state.currentThreadId;
        },
    },
    mutations: {
        setContactName: function(state, n) {
            state.contactName = n;
        },
        setCurrentThreadId: function(state, n) {
            state.currentThreadId = n;
        },
    },
};

export default storage;
