import Tabs from 'bootstrap-vue/es/components';
import TokenIntroductionProfile from '../components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from '../components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from '../components/token/introduction/TokenIntroductionDescription';
import TokenName from '../components/token/introduction/TokenName';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      editingName: false,
    };
  },
  components: {
    TokenIntroductionProfile,
    TokenIntroductionStatistics,
    TokenIntroductionDescription,
    TokenName,
    Tabs,
  },
});
