import Trade from './components/trade/Trade';
import TokenIntroductionProfile from './components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenName from './components/token/TokenName';
import TokenDeployIcon from './components/token/deploy/TokenDeployIcon';
import TopHolders from './components/trade/TopHolders';
import PageLoadSpinner from './components/PageLoadSpinner';
import store from './storage';
import {tokenDeploymentStatus} from './utils/constants';
import {NestedSpinner} from './mixins';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      tokenDescription: null,
      editingName: false,
      tokenName: null,
      tokenPending: null,
    };
  },
  components: {
    Trade,
    TokenIntroductionProfile,
    TokenIntroductionStatistics,
    TokenIntroductionDescription,
    TokenName,
    TokenDeployIcon,
    TopHolders,
    PageLoadSpinner,
  },
  mixins: [NestedSpinner],
  methods: {
    descriptionUpdated: function(val) {
      this.tokenDescription = val;
    },
    tabUpdated: function(i) {
      if (window.history.replaceState) {
        // prevents browser from storing history with each change:
        window.history.replaceState(
            {}, document.title, this.$routing.generate('token_show', {
              name: this.tokenName,
              tab: i ? 'trade' : 'intro',
            })
        );
      }
    },
    setTokenPending: function() {
      this.tokenPending = true;
    },
    getTokenStatus: function(status) {
      return true === this.tokenPending ? tokenDeploymentStatus.pending : status;
    },
  },
  store,
});
