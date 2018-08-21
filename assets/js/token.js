import TokenInvest from '../components/token/TokenInvest';
import TokenIntroduction from '../components/token/TokenIntroduction';
import TokenDataForm from '../components/token/TokenDataForm';
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
    TokenDataForm,
    Tabs,
  },
});
