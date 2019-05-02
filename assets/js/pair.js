import {Tabs} from 'bootstrap-vue/es/components';
import Trade from './components/trade/Trade';
import TokenIntroductionProfile from './components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenName from './components/token/TokenName';
import store from './storage';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      tokenDescription: null,
      editingName: false,
    };
  },
  components: {
    Trade,
    TokenIntroductionProfile,
    TokenIntroductionStatistics,
    TokenIntroductionDescription,
    TokenName,
    Tabs,
  },
  methods: {
    descriptionUpdated: function(val) {
      this.tokenDescription = val;
    },
  },
  store,
});
