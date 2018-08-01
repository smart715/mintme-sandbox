import TokenInvest from '../components/TokenInvest';
import TokenIntroduction from '../components/TokenIntroduction';
import Tabs from 'bootstrap-vue/es/components';

Vue.use(Tabs);

new Vue({
  el: '#token',
  components: {
    TokenInvest,
    TokenIntroduction,
  },
});
