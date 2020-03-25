import Trading from './components/trading/Trading';
import tradingSortPlugin from './trading-sort-plugin.js';
import store from './storage';

// load the trading table sorting plugin
Vue.use(tradingSortPlugin);

new Vue({
  el: '#trading',
  components: {
    Trading,
  },
  store,

});
