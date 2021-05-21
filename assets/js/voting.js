import i18n from './utils/i18n/i18n';
import store from './storage';
import BalanceInit from './components/trade/BalanceInit';
import VotingWidget from './components/voting/VotingWidget';

new Vue({
  el: '#voting',
  i18n,
  components: {
    BalanceInit,
    VotingWidget,
  },
  store,
});
