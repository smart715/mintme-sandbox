import Vuex from 'vuex';
import Mutations from './mutations';
import Actions from './actions';
import WebsocketModule from './modules/websocket';

Vue.use(Vuex);

export default new Vuex.Store({
    state: { },
    mutations: Mutations,
    actions: Actions,
    modules: {
        websocket: WebsocketModule,
    },
});
