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

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      tokenDescription: null,
      editingName: false,
      tokenName: null,
      tokenPending: null,
      spinnerQuantity: 0,
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
  mounted() {
    this.$on('hide-spinner', () => {
      this.hideSpinner();
    });
    this.$on('show-spinner', () => {
      this.showSpinner();
    });
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
    showSpinner: function() {
      if (!this.spinnerQuantity) {
        this.$refs.spinner.show();
      }
      this.spinnerQuantity = this.spinnerQuantity + 1;
      alert(' + ' + this.spinnerQuantity);
    },
    hideSpinner: function() {
      this.spinnerQuantity = this.spinnerQuantity - 1;
      if (!this.spinnerQuantity) {
        this.$refs.spinner.hide();
      }
      alert(' - ' + this.spinnerQuantity);
    },
  },
  store,
});
