import Vuex from 'vuex';
import storage from './storage';

if (!window.store) {
    Vue.use(Vuex);
    window.store = new Vuex.Store(storage);
}

export default window.store;
