import Tabs from 'bootstrap-vue/es/components';
import TokenIntroductionProfile from '../components/token/TokenIntroductionProfile';
import TokenIntroductionStatistics from '../components/token/TokenIntroductionStatistics';
import TokenIntroductionDescription from '../components/token/TokenIntroductionDescription';
import TokenName from '../components/token/TokenName';

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
