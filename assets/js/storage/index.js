import Vuex from 'vuex';
import Mutations from './mutations';
import Actions from './actions';
import WebsocketModule from './modules/websocket';
import interval from '../utils/interval';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        interval,
    },
    mutations: Mutations,
    actions: Actions,
    modules: {
        websocket: WebsocketModule,
    },
});
