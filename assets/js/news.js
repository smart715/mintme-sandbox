import Vue from 'vue';
import Vuex from 'vuex';
import NewsImage from './components/NewsImage';

Vue.use(Vuex);

const store = new Vuex.Store({
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
});

new Vue({
    el: '#news-selector',
    store,
    components: {NewsImage},
});

