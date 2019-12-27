import Trading from './components/trading/Trading';
import store from './storage';

new Vue({
  el: '#trading',
  data() {
    return {
        showUsd: false,
        enableUsd: true,
    };
  },
  components: {
    Trading,
  },
  store,
  methods: {
    toggleUsd: function(show) {
      this.showUsd = show;
    },
    disableUsd: function() {
      this.showUsd = false;
      this.enableUsd = false;
    },
  },
});
