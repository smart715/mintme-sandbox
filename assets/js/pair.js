import BalanceInit from './components/trade/BalanceInit';
import CreatePost from './components/posts/CreatePost';
import Donation from './components/donation/Donation';
import Posts from './components/posts/Posts';
import Post from './components/posts/Post';
import Comments from './components/posts/Comments';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenOngoingAirdropCampaign from './components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import TokenSocialMediaIcons from './components/token/TokenSocialMediaIcons';
import TokenAvatar from './components/token/TokenAvatar';
import TopHolders from './components/trade/TopHolders';
import {NotificationMixin, LoggerMixin} from './mixins/';
import Trade from './components/trade/Trade';
import {tokenDeploymentStatus, HTTP_OK} from './utils/constants';
import {mapGetters, mapMutations} from 'vuex';
import Avatar from './components/Avatar';
import Envelope from './components/chat/Envelope';
import i18n from './utils/i18n/i18n';
import TokenCreatedModal from './components/modal/TokenCreatedModal';
import TokenDeployedModal from './components/modal/TokenDeployedModal';
import {tabs} from './utils/constants';

new Vue({
  el: '#token',
  mixins: [NotificationMixin, LoggerMixin],
  i18n,
  data() {
    return {
      tabIndex: 0,
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
      showCreatedModal: true,
      singlePost: null,
      comments: null,
      showDeployedOnBoard: null,
      tokenDeployedDate: null,
      tokenTxHashAddress: null,
    };
  },
  components: {
    Avatar,
    Envelope,
    BalanceInit,
    CreatePost,
    Comments,
    Donation,
    Post,
    Posts,
    TokenAvatar,
    TokenCreatedModal,
    TokenDeployedModal,
    TokenIntroductionDescription,
    TokenIntroductionStatistics,
    TokenOngoingAirdropCampaign,
    TokenSocialMediaIcons,
    TopHolders,
    Trade,
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
        this.notifySuccess(this.$t('page.pair.token_created'));
    }

    let tokenName = this.tokenName;
    if (tokenName) {
      tokenName = tokenName.replace(/\s/g, '-');
      document.addEventListener('DOMContentLoaded', () => {
        let introLink = document.querySelectorAll('a.token-intro-tab-link')[0];
        introLink.href = this.$routing.generate('token_show', {name: tokenName, tab: tabs[0]});
        let donateLink = document.querySelectorAll('a.token-buy-tab-link')[0];
        donateLink.href = this.$routing.generate('token_show', {name: tokenName, tab: tabs[1]});
        let postsLink = document.querySelectorAll('a.token-posts-tab-link')[0];
        postsLink.href = this.$routing.generate('token_show', {name: tokenName, tab: tabs[2]});
        let tradeLink = document.querySelectorAll('a.token-trade-tab-link')[0];
        tradeLink.href = this.$routing.generate('token_show', {name: tokenName, tab: tabs[3]});
      });
    }
  },
  methods: {
    ...mapMutations('tradeBalance', [
      'setUseBuyMarketPrice',
      'setBuyAmountInput',
      'setSubtractQuoteBalanceFromBuyAmount',
    ]),
    closeDeployedModal: function() {
        this.showDeployedOnBoard = false;
        this.$axios.single.patch(this.$routing.generate('token_update_deployed_modal', {tokenName: this.tokenName}));
    },
    fetchAddress: function() {
        this.$axios.single.get(this.$routing.generate('token_address', {name: this.tokenName}))
        .then((response) => {
          if (response.status === HTTP_OK) {
            this.tokenAddress = response.data.address;
          }
        }, (error) => {
            this.notifyError(this.$t('toasted.error.try_later'));
        });
    },
    getTxHash: function() {
      this.$axios.retry.get(this.$routing.generate('token_tx_hash', {
        name: this.tokenName,
      }))
          .then(({data}) => {
            this.tokenTxHashAddress = data.txHash;
          }).catch((err) => {
        this.sendLogs('error', 'Can not get token tx_hash', err);
      });
    },
    getDeployedDate: function() {
      this.$axios.retry.get(this.$routing.generate('token_deployed_date', {
        name: this.tokenName,
      }))
          .then(({data}) => {
            this.tokenDeployedDate = {
                date: data.deployedDate,
            };
          }).catch((err) => {
        this.sendLogs('error', 'Can not get token deployed date', err);
      });
    },
    checkTokenDeployment: function() {
      clearInterval(this.deployInterval);
      this.deployInterval = setInterval(() => {
          this.$axios.single.get(this.$routing.generate('token_deployment_status', {name: this.tokenName}))
          .then((response) => {
            if (response.data.status === tokenDeploymentStatus.deployed) {
                this.tokenDeployed = true;
                this.tokenPending = false;
                this.showDeployedOnBoard = true;
                this.fetchAddress();
                this.getTxHash();
                this.getDeployedDate();
                clearInterval(this.deployInterval);
            }
            this.retryCount++;
            if (this.retryCount >= this.retryCountLimit) {
                this.notifyError(this.$t('toasted.error.can_not_be_deployed'));
                this.tokenPending = false;
                this.tokenDeployed = false;
                clearInterval(this.deployInterval);
            }
          })
          .catch((error) => {
            this.notifyError(this.$t('toasted.error.try_later'));
          });
      }, 60000);
    },
    descriptionUpdated: function(val) {
      this.tokenDescription = val;
    },
    tabUpdated: function(i) {
      if (window.history.replaceState) {
        // prevents browser from storing history with each change:
        let url = '';
        switch (i) {
          case 2:
            url = this.$routing.generate('new_show_post', {
              name: this.tokenName,
              slug: null,
            });

            break;
          case 4:
            url = this.$routing.generate('new_show_post', {
              name: this.tokenName,
              slug: this.singlePost.slug,
            });

            break;
          default:
            url = this.$routing.generate('token_show', {
              name: this.tokenName,
              tab: tabs[i],
            });
        }

        window.history.replaceState({}, '', url);
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
    updatePost: function({post, i}) {
      this.$set(this.posts, i, post);
    },
    updateComment: function({comment, i}) {
      this.$set(this.comments, i, comment);
    },
    updatePosts: function() {
      if (!this.tokenName) {
        return;
      }
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
    goToTrade: function(amount) {
      this.tabIndex= 3;
      this.setUseBuyMarketPrice(true);
      this.setBuyAmountInput(amount);
      this.setSubtractQuoteBalanceFromBuyAmount(true);
    },
    deleteComment: function(index) {
      this.comments.splice(index, 1);
    },
    newComment: function(comment) {
      this.comments.unshift(comment);
    },
    goToPost: function(post) {
      this.singlePost = post;
      this.tabIndex = 4;
      this.comments = [];

      this.loadComments(post.id);
    },
    loadComments: function(postId) {
      this.$axios.single.get(this.$routing.generate('get_post_comments', {id: postId}))
        .then((res) => this.comments = res.data)
        .catch((err) => {
          this.notifyError($t('comment.load_error'));
          this.sendLogs('error', err);
        });
    },
    deleteSinglePost: function(index, postId) {
      this.posts = this.posts.filter((post) => post.id !== postId);
      this.goToPosts();
    },
  },
  computed: {
    ...mapGetters('tradeBalance', [
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
