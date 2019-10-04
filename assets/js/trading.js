import Trading from './components/trading/Trading';
import store from './storage';

new Vue({
  el: '#trading',
  data() {
    return {};
  },
  components: {
    Trading,
  },
  store,
});
