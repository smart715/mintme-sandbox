import Vuex from 'vuex';
import Mutations from './mutations';
import Actions from './actions';
import websocket from './modules/websocket';
import makeOrder from './modules/make_order';
import interval from '../utils/interval';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        interval,
    },
    mutations: Mutations,
    actions: Actions,
    modules: {
        websocket,
        makeOrder,
    },
});
