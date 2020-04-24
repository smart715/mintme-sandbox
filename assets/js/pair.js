import Trade from './components/trade/Trade';
import TokenPointsProgress from './components/token/TokenPointsProgress';
import TokenIntroductionProfile from './components/token/introduction/TokenIntroductionProfile';
import TokenIntroductionStatistics from './components/token/introduction/TokenIntroductionStatistics';
import TokenIntroductionDescription from './components/token/introduction/TokenIntroductionDescription';
import TokenName from './components/token/TokenName';
import TokenDeployIcon from './components/token/deploy/TokenDeployIcon';
import TopHolders from './components/trade/TopHolders';
import Posts from './components/posts/Posts';
import CreatePost from './components/posts/CreatePost';
import store from './storage';
import {tokenDeploymentStatus} from './utils/constants';

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
      editingName: false,
      tokenName: null,
      tokenPending: null,
      posts: null,
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
    TokenPointsProgress,
    Posts,
    CreatePost,
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
              tab: this.tabs[i],
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
    facebookUpdated: function(val) {
      this.tokenFacebook = val;
    },
    websiteUpdated: function(val) {
        this.tokenWebsite = val;
    },
    youtubeUpdated: function(val) {
        this.tokenYoutube = val;
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
  },
  store,
});
