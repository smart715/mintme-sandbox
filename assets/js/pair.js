import CreatePost from './components/posts/CreatePost';
import Posts from './components/posts/Posts';
import TokenDeployIcon from './components/token/deploy/TokenDeployIcon';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenName from './components/token/TokenName';
import TokenOngoingAirdropCampaign from './components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import TokenPointsProgress from './components/token/TokenPointsProgress';
import TokenSocialMediaIcons from './components/token/TokenSocialMediaIcons';
import TopHolders from './components/trade/TopHolders';
import Trade from './components/trade/Trade';
import store from './storage';
import {tokenDeploymentStatus, HTTP_OK} from './utils/constants';

new Vue({
  el: '#token',
  data() {
    return {
      tabIndex: 0,
      tabs: ['intro', 'trade', 'posts'],
      tokenDescription: null,
      tokenWebsite: null,
      tokenFacebook: null,
      tokenYoutube: null,
      tokenDiscord: null,
      tokenTelegram: null,
      editingName: false,
      tokenName: null,
      tokenPending: null,
      tokenDeployed: null,
      deployInterval: null,
      retryCount: 0,
      retryCountLimit: 15,
      tokenAddress: null,
      tokenAddressTimeout: null,
      posts: null,
    };
  },
  components: {
    CreatePost,
    Posts,
    TokenDeployIcon,
    TokenIntroductionDescription,
    TokenIntroductionStatistics,
    TokenName,
    TokenOngoingAirdropCampaign,
    TokenPointsProgress,
    TokenSocialMediaIcons,
    TopHolders,
    Trade,
  },
  watch: {
    tokenPending: function() {
      this.checkTokenDeployment();
    },
  },
  mounted: function() {
    this.fetchAddress();
  },
  beforeUpdate: function() {
    if (this.tokenDeployed) {
      this.fetchAddress();
    }
  },
  methods: {
    fetchAddress: function() {
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
    checkTokenDeployment: function() {
      clearInterval(this.deployInterval);
      this.deployInterval = setInterval(() => {
          this.$axios.single.get(this.$routing.generate('is_token_deployed', {name: this.tokenName}))
          .then((response) => {
            if (response.data.deployed === tokenDeploymentStatus.deployed) {
                this.tokenDeployed = true;
                this.tokenPending = false;
                clearInterval(this.deployInterval);
            }
            this.retryCount++;
            if (this.retryCount >= this.retryCountLimit) {
                this.notifyError('The token could not be deployed, Please try again later');
                this.tokenPending = false;
                this.tokenDeployed = false;
                clearInterval(this.deployInterval);
            }
          }, (error) => {
              this.notifyError('An error has occurred, please try again later');
          });
      }, 60000);
    },
    descriptionUpdated: function(val) {
      this.tokenDescription = val;
    },
    tabUpdated: function(i) {
      if (window.history.replaceState) {
        // prevents browser from storing history with each change:
        window.history.replaceState(
            {}, document.title, this.$routing.generate('token_show', {
              name: this.tokenName,
              tab: this.tabs[i],
            })
        );
      }
    },
    setTokenPending: function() {
      this.tokenPending = true;
    },
    getTokenStatus: function(status) {
      return this.tokenDeployed ? tokenDeploymentStatus.deployed :
             (this.tokenPending ? tokenDeploymentStatus.pending :
             status);
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
    discordUpdated: function(val) {
      this.tokenDiscord = val;
    },
    telegramUpdated: function(val) {
      this.tokenTelegram = val;
    },
    updatePosts: function() {
      this.$axios.single.get(this.$routing.generate('list_posts', {tokenName: this.tokenName}))
      .then((res) => {
        this.posts = res.data;
      });
    },
    goToPosts: function() {
      this.tabIndex = 2;
    },
    deletePost: function(index) {
      this.posts.splice(index, 1);
    },
    coalesce: function(a, b) {
      return null !== a ? a : b;
    },
  },
  store,
});
