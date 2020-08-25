import CreatePost from './components/posts/CreatePost';
import Donation from './components/donation/Donation';
import Posts from './components/posts/Posts';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenOngoingAirdropCampaign from './components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import TokenSocialMediaIcons from './components/token/TokenSocialMediaIcons';
import TokenAvatar from './components/token/TokenAvatar';
import TopHolders from './components/trade/TopHolders';
import {NotificationMixin} from './mixins/';
import Trade from './components/trade/Trade';
import store from './storage';
import {tokenDeploymentStatus, HTTP_OK} from './utils/constants';
import {mapGetters, mapMutations} from 'vuex';
import Avatar from './components/Avatar';

new Vue({
  el: '#token',
  mixins: [NotificationMixin],
  data() {
    return {
      tabIndex: 0,
      tabs: ['intro', 'trade', 'donate', 'posts'],
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
      posts: null,
      postFromUrl: null,
    };
  },
  components: {
    CreatePost,
    Donation,
    Posts,
    TokenIntroductionDescription,
    TokenAvatar,
    TokenIntroductionStatistics,
    TokenOngoingAirdropCampaign,
    TokenSocialMediaIcons,
    TopHolders,
    Trade,
    Avatar,
  },
  mounted: function() {
    let divEl = document.createElement('div');
    let tabsEl = document.querySelectorAll('.nav.nav-tabs');

    this.postFromUrl = (/(?:posts#)(\d+)/g.exec(window.location.href) || [])[1] || null;
    if (this.postFromUrl !== null) {
        // Prevent browser from restoring previous scroll height (if page was raloaded)
        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
        }
        document.getElementById(this.postFromUrl).scrollIntoView();
    }

    divEl.className = 'tabs-left-margin-container';
    document.getElementsByClassName('tabs-wrapper')[0].insertBefore(divEl, tabsEl[0]);

    let aux = this.$refs['tokenAvatar'];
    if (aux && aux.$attrs['showsuccess']) {
        this.notifySuccess('Token has been created successfully');
    }

    let tokenName = this.tokenName;
    tokenName = tokenName.replace(/\s/g, '-');
    document.addEventListener('DOMContentLoaded', () => {
        let introLink = document.querySelectorAll('a.token-intro-tab-link')[0];
        introLink.href = this.$routing.generate('token_show', {name: tokenName, tab: this.tabs[0]});
        let tradeLink = document.querySelectorAll('a.token-trade-tab-link')[0];
        tradeLink.href = this.$routing.generate('token_show', {name: tokenName, tab: this.tabs[1]});
        let donateLink = document.querySelectorAll('a.token-donate-tab-link')[0];
        donateLink.href = this.$routing.generate('token_show', {name: tokenName, tab: this.tabs[2]});
        let postsLink = document.querySelectorAll('a.token-posts-tab-link')[0];
        postsLink.href = this.$routing.generate('token_show', {name: tokenName, tab: this.tabs[3]});
    });
  },
  methods: {
    ...mapMutations('makeOrder', [
      'setUseBuyMarketPrice',
      'setBuyAmountInput',
      'setSubtractQuoteBalanceFromBuyAmount',
    ]),
    fetchAddress: function() {
        this.$axios.single.get(this.$routing.generate('token_address', {name: this.tokenName}))
        .then((response) => {
          if (response.status === HTTP_OK) {
            this.tokenAddress = response.data.address;
          }
        }, (error) => {
            this.notifyError('An error has occurred, please try again later');
        });
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
                this.fetchAddress();
            }
            this.retryCount++;
            if (this.retryCount >= this.retryCountLimit) {
                this.notifyError('The token could not be deployed, please try again later');
                this.tokenPending = false;
                this.tokenDeployed = false;
                clearInterval(this.deployInterval);
            }
          })
          .catch((error) => {
            this.notifyError('An error has occured, please try again later');
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
      this.checkTokenDeployment();
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
      this.tabIndex = 3;
    },
    deletePost: function(index) {
      this.posts.splice(index, 1);
    },
    coalesce: function(a, b) {
      return null !== a ? a : b;
    },
    goToTrade: function(amount) {
      this.tabIndex= 1;
      this.setUseBuyMarketPrice(true);
      this.setBuyAmountInput(amount);
      this.setSubtractQuoteBalanceFromBuyAmount(true);
    },
  },
  computed: {
    ...mapGetters('makeOrder', [
      'getQuoteBalance',
    ]),
  },
  watch: {
    getQuoteBalance: function() {
      this.updatePosts();
    },
  },
  store,
});
