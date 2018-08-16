import '../scss/token-page.sass';
import TokenInvest from '../components/TokenInvest';
import TokenIntroductionProfile from '../components/TokenIntroductionProfile';
import TokenIntroductionStatistics from '../components/TokenIntroductionStatistics';
import TokenIntroductionDescription from '../components/TokenIntroductionDescription';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
    };
  },
  components: {
    TokenInvest,
    TokenIntroductionProfile,
    TokenIntroductionStatistics,
    TokenIntroductionDescription,
  },
});
