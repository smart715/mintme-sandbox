import TokenInvest from '../components/TokenInvest';
import TokenIntroduction from '../components/TokenIntroduction';

new Vue({
  el: '#token',
  data() {
    return {
      selectedTab: 'tokenInvest',
    };
  },
  components: {
    TokenInvest,
    TokenIntroduction,
  },
});
