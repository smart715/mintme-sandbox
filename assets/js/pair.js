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
import {tokenDeploymentStatus, HTTP_OK} from './utils/constants';

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
      deployInterval: null,
      retryCount: 0,
      retryCountLimit: 10,
      tokenAddressTimeout: null,
      tokenAddress: null,
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
  watch: {
    tokenPending: function(val) {
      this.tokenPending = val;
      clearInterval(this.deployInterval);
      this.deployInterval = setInterval(() => {
          this.$axios.single.get(this.$routing.generate('is_token_deployed', {name: this.tokenName}))
          .then((response) => {
            if (response.data.deployed === true) {
                this.tokenDeployed = true;
                this.tokenPending = false;
                clearInterval(this.deployInterval);
            }
            this.retryCount++;
            if (this.retryCount >= this.retryCountLimit) {
                clearInterval(this.deployInterval);
            }
          }, (error) => {
              this.notifyError('An error has occurred, please try again later');
          });
      }, 60000);
    },
    tokenDeployed: function() {
      clearTimeout(this.tokenAddressTimeout);
      this.tokenAddressTimeout = setTimeout(() => {
        this.$axios.single.get(this.$routing.generate('token_address', {name: this.tokenName}))
        .then((response) => {
          if (response.status === HTTP_OK) {
            this.tokenAddress = response.data.address;
            clearTimeout(this.tokenAddressTimeout);
          }
        }, (error) => {
            this.notifyError('An error has occurred, please try again later');
        });
      }, 2000);
    },
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
      return this.tokenDeployed ? tokenDeploymentStatus.deployed :
             this.tokenPending ? tokenDeploymentStatus.pending :
             status;
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
