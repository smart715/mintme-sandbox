import Trading from './components/trading/Trading';
import {Pagination} from 'bootstrap-vue/es/components';
import store from './storage';

new Vue({
  el: '#trading',
  data() {
    return {
        showUsd: false,
    };
  },
  components: {
    Trading,
    Pagination,
  },
  store,
  methods: {
    toggleUsd: function() {
      this.showUsd = !this.showUsd;
    },
  },
});
