import Tabs from 'bootstrap-vue/es/components';
import TokenTrade from '../components/token/trade/TokenTrade';
import TokenIntroductionProfile from '../components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from '../components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from '../components/token/introduction/TokenIntroductionDescription';
import TokenName from '../components/token/TokenName';
import store from './storage';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      editingName: false,
    };
  },
  components: {
    TokenTrade,
    TokenIntroductionProfile,
    TokenIntroductionStatistics,
    TokenIntroductionDescription,
    TokenName,
    Tabs,
  },
  methods: {
    onTabUpdated: function() {
      store.state.interval.clearAll();
    },
  },
  store,
});
