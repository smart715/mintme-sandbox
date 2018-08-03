import TokenInvest from '../components/TokenInvest';
import TokenIntroduction from '../components/TokenIntroduction';
import Tabs from 'bootstrap-vue/es/components';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
    };
  },
  components: {
    TokenInvest,
    TokenIntroduction,
    Tabs,
  },
});
