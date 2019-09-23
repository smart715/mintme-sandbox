import {Tabs} from 'bootstrap-vue/es/components';
import Trade from './components/trade/Trade';
import TokenIntroductionProfile from './components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenName from './components/token/TokenName';
import TokenDeployIcon from './components/token/deploy/TokenDeployIcon';
import TopHolders from './components/trade/TopHolders';
import store from './storage';
import {tokenDeploymentStatus} from "./utils/constants";

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      tokenDescription: null,
      editingName: false,
      tokenName: null,
      tokenPeriodAdded: null,
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
    Tabs,
  },
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
              tab: i ? 'intro' : 'trade',
            })
        );
        document.title = (i ? 'Information about ' : '') + this.tokenName + ' token | mintMe';
      }
    },
    updateTokenPeriod: function() {
      this.tokenPeriodAdded = true;
    },
    setTokenPending: function() {
      this.tokenPending = true;
    },
    isTokenPeriodAdded: function(isAdded) {
      return null !== this.tokenPeriodAdded ? this.tokenPeriodAdded : isAdded;
    },
    getTokenStatus: function(status) {
      return true === this.tokenPending ? tokenDeploymentStatus.pending : status;
    },
  },
  store,
});
