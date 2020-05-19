import TokenOngoingAirdropCampaign from './components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import Trade from './components/trade/Trade';
import TokenPointsProgress from './components/token/TokenPointsProgress';
import TokenIntroductionProfile from './components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenName from './components/token/TokenName';
import TokenDeployIcon from './components/token/deploy/TokenDeployIcon';
import TopHolders from './components/trade/TopHolders';
import store from './storage';
import {tokenDeploymentStatus} from './utils/constants';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      tokenDescription: null,
      tokenWebsite: null,
      tokenFacebook: null,
      tokenYoutube: null,
      editingName: false,
      tokenName: null,
      tokenPending: null,
      tokenDeployed: null,
    };
  },
  components: {
    TokenOngoingAirdropCampaign,
    Trade,
    TokenIntroductionProfile,
    TokenIntroductionStatistics,
    TokenIntroductionDescription,
    TokenName,
    TokenDeployIcon,
    TopHolders,
    TokenPointsProgress,
  },
  updated: function() {
    console.log('pair comp upd pen is ' + this.tokenPending);
    console.log('pair comp upd de is' + this.tokenDeployed);
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
      console.log('emit deploy pending ' + this.tokenPending);
    },
    setTokenDeployed: function() {
      this.tokenPending = null;
      this.tokenDeployed = true;
      console.log('emit deploy pending after deploy ' + this.tokenPending);
      console.log('emit deploy complete ' + this.tokenDeployed);
    },
    getTokenStatus: function(status) {
      return true === this.tokenPending ? tokenDeploymentStatus.pending : true === this.tokenDeployed ? tokenDeploymentStatus.deployed : status;
    },
    facebookUpdated: function(val) {
      this.tokenFacebook = val;
    },
    websiteUpdated: function(val) {
        this.tokenWebsite = val;
    },
    youtubeUpdated: function(val) {
        this.tokenYoutube = val;
    },
  },
  store,
});
