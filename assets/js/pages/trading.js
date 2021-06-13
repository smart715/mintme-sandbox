import '../../scss/pages/trading.sass';
import Trading from '../components/trading/Trading';
import store from '../storage';
import i18n from '../utils/i18n/i18n';

new Vue({
  el: '#trading',
  i18n,
  components: {
    Trading,
  },
  store,

});
