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
      reRenderTokenName: 0,
      reRenderTokenDeployIcon: 0,
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
    console.log('pair comp updated ' + this.tokenDeployed);
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
    setTokenDeployed: function() {
      this.tokenDeployed = true;
      this.reRenderTokenName++;
      this.reRenderTokenDeployIcon++;
    },
    getTokenStatus: function(status) {
      return true === this.tokenPending ? tokenDeploymentStatus.pending : status;
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
