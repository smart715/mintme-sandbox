import Trading from './components/trading/Trading';
import store from './storage';
import {NestedSpinner} from './mixins/';

new Vue({
  el: '#trading',
  components: {
    Trading,
  },
  mixins: [
    NestedSpinner,
  ],
  store,

});
