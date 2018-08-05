import TokenInvest from '../components/TokenInvest';
import TokenIntroduction from '../components/TokenIntroduction';
import TokenNewForm from '../components/TokenNewForm';
import TokenEditForm from '../components/TokenEditForm';
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
    TokenNewForm,
    TokenEditForm,
    Tabs,
  },
});
