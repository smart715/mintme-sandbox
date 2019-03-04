import Trading from '../components/trading/Trading';
import Pagination from 'bootstrap-vue/es/components';
import store from './storage';

new Vue({
  el: '#trading',
  data() {
    return {};
  },
  components: {
    Trading,
    Pagination,
  },
  store,
});
