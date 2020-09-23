import Vuex from 'vuex';
import Mutations from './mutations';
import Actions from './actions';
import websocket from './modules/websocket';
import makeOrder from './modules/make_order';
import newsImages from './modules/news_images';
import interval from '../utils/interval';
import tokenStatistics from './modules/token_statistics';

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
        tokenStatistics,
        newsImages,
    },
});
