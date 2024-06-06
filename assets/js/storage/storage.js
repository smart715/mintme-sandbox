import Mutations from './mutations';
import Actions from './actions';
import websocket from './modules/websocket';
import tradeBalance from './modules/trade_balance';
import newsImages from './modules/news_images';
import interval from '../utils/interval';
import tokenStatistics from './modules/token_statistics';
import chat from './modules/chat';
import rates from './modules/rates';
import user from './modules/user';
import voting from './modules/voting';
import orders from './modules/orders';
import tokenInfo from './modules/token_info';
import rewardsAndBounties from './modules/rewards_and_bounties';
import market from './modules/market';
import minOrder from './modules/min_order';
import posts from './modules/posts';
import profile from './modules/profile';
import tokenSettings from './modules/token_settings';
import pair from './modules/pair';
import crypto from './modules/crypto';

export default {
    state: {
        interval,
    },
    mutations: Mutations,
    actions: Actions,
    modules: {
        websocket,
        tradeBalance,
        tokenStatistics,
        newsImages,
        chat,
        rates,
        user,
        voting,
        orders,
        tokenInfo,
        rewardsAndBounties,
        market,
        minOrder,
        posts,
        profile,
        tokenSettings,
        pair,
        crypto,
    },
};
