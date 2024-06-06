<template>
    <div id="posts-container" ref="postsContainer" class="w-100 d-flex flex-column">
        <div v-if="loading && isZeroPage" class="d-flex justify-content-center p-3">
            <span class="spinner-border spinner-border-md">
                <span class="sr-only"> {{ $t('loading') }} </span>
            </span>
        </div>
        <template
            v-else-if="hasItems"
        >
            <div
                v-for="(n, i) in itemsToShow"
                :key="i"
            >
                <post
                    v-if="itemsToShow[i].title"
                    :post="itemsToShow[i]"
                    :recent-post="true"
                    :logged-in="loggedIn"
                    :is-authorized-for-reward="isAuthorizedForReward"
                    :view-only="viewOnly"
                    :redirect="true"
                    @go-to-post="openPost(itemsToShow[i])"
                    @update-post="updatePost($event, i)"
                    @save-like="onSaveLike(itemsToShow[i])"
                />
                <comment
                    v-else
                    :comment-prop="itemsToShow[i]"
                    :logged-in="loggedIn"
                    :user-has-deployed-tokens="hasDeployedTokens"
                    :redirect="true"
                    @tip="onCommentTip(itemsToShow[i])"
                    @go-to-post="openPost(itemsToShow[i].postId, itemsToShow[i])"
                />
            </div>
        </template>
        <div
            v-show="!hasItems && !loading"
            class="text-content-primary text-center pb-3"
        ><div v-html-sanitize="noRecentPostsAndCommentsMessage"></div></div>
        <div
            v-if="!isZeroPage"
            class="justify-content-center py-3 d-none"
            :class="{'invisible': !loading || isZeroPage, 'd-flex': hasMoreItems && (loading || !lazyLoadingDisabled)}"
        >
            <span class="spinner-border spinner-border-md">
                <span class="sr-only"> {{ $t('loading') }} </span>
            </span>
        </div>
        <div v-if="lazyLoadingDisabled && !loading && hasMoreItems" class="d-flex justify-content-center">
            <button class="btn btn-lg button-secondary rounded-pill" @click="loadMore">
                <span class="pt-2 pb-2 pl-3 pr-3">
                    {{ $t('load_more') }}
                </span>
            </button>
        </div>
        <tip-comment-modal
            v-if="hasDeployedTokens"
            :visible="tipModalVisible"
            :deployed-tokens="ownDeployedTokens"
            :comment="activeTipComment"
            @close="tipModalVisible = false"
        />
    </div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import debounce from 'lodash/debounce';
import Comment from './Comment';
import Post from './Post';
import {NotificationMixin} from '../../mixins';
import axios from 'axios';
import TipCommentModal from '../modal/TipCommentModal';

const ITEMS_BATCH_SIZE = 10;

export default {
    name: 'UserFeed',
    mixins: [NotificationMixin],
    components: {
        Post,
        Comment,
        TipCommentModal,
    },
    props: {
        isAuthorizedForReward: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
        viewOnly: Boolean,
        hashtag: String,
        isAllTab: Boolean,
        ownDeployedTokens: {
            type: () => [Object, Array],
            default: () => {},
        },
        lazyLoadingDisabled: Boolean,
        firstPagePostsAmount: Number,
    },
    created() {
        this.setPostRewardsCollectableDays(this.postRewardsCollectableDays);
        this.setIsAuthorizedForReward(this.isAuthorizedForReward);
    },
    data() {
        return {
            posts: [],
            comments: [],
            items: [],
            loading: true,
            hasMoreItems: false,
            page: 0,
            handleDebouncedScroll: null,
            cancelTokenSource: null,
            tipModalVisible: false,
            activeTipComment: null,
            fetchItemsDebounced: debounce(this.fetchItems, 500),
            firstPageExpanded: false,
        };
    },
    methods: {
        ...mapMutations('posts', [
            'setPostRewardsCollectableDays',
            'setIsAuthorizedForReward',
            'setComments',
        ]),
        async fetchFeedByHashtag(hashtag) {
            this.cancelTokenSource = axios.CancelToken.source();
            this.loading = true;

            try {
                const response = await this.$axios.single.get(this.$routing.generate(
                    'feed_by_hashtag',
                    {page: this.page, hashtag},
                ), {cancelToken: this.cancelTokenSource.token});

                this.proceedResponse(response.data);

                this.loading = false;
            } catch (err) {
                if (axios.isCancel(err)) {
                    return;
                }

                this.notifyError(this.$t('toasted.error.try_later'));
                this.$logger.error('Error while loading feed by hashtag', err);
            }
        },
        async fetchRecentPostsAndComments(isAllTab) {
            this.cancelTokenSource = axios.CancelToken.source();
            this.loading = true;

            try {
                const res = await this.$axios.single.get(this.$routing.generate(
                    'recent_posts_and_comments',
                    {page: this.page}
                ), {params: {all: isAllTab}, cancelToken: this.cancelTokenSource.token});

                this.proceedResponse(res.data);

                this.loading = false;
            } catch (err) {
                if (axios.isCancel(err)) {
                    return;
                }

                this.notifyError(this.$t('toasted.error.try_later'));
                this.$logger.error('Error while loading recent posts and comments', err);
            }
        },
        async fetchItems() {
            if (0 < this.items?.length && this.firstPagePostsAmount && !this.firstPageExpanded) {
                this.firstPageExpanded = true;

                return;
            }

            return this.hashtag
                ? this.fetchFeedByHashtag(this.hashtag)
                : this.fetchRecentPostsAndComments(this.isAllTab);
        },
        refetchItems() {
            if (this.cancelTokenSource) {
                this.cancelTokenSource.cancel();
            }

            this.loading = true;
            this.page = 0;

            this.fetchItemsDebounced();
        },
        proceedResponse(data) {
            this.comments = this.isZeroPage ? data.comments : this.comments.concat(data.comments);
            this.setComments(this.comments);
            this.posts = this.isZeroPage ? data.posts : this.posts.concat(data.posts);
            this.items = this.posts.concat(this.comments);
            this.items.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
            this.page += 1;
            this.hasMoreItems = data?.posts?.length >= ITEMS_BATCH_SIZE
                || data?.comments?.length >= ITEMS_BATCH_SIZE;
        },
        openPost(post, comment = null) {
            window.location.href = null === comment
                ? this.$routing.generate('token_show_post', {name: post.token.name, slug: post.slug}, true)
                : this.$routing.generate('show_post', {id: post}, true) + '#comment-' + comment.id;
        },
        updatePost(post, i) {
            this.$set(this.posts, i, post);
        },
        onSaveLike(post) {
            post.likes += post.isUserAlreadyLiked ? -1 : 1;
            post.isUserAlreadyLiked = !post.isUserAlreadyLiked;
        },
        onScrollDown() {
            const container = this.$refs.postsContainer;

            if (!container) {
                return;
            }

            const bottomOfContainer = container.scrollTop + container.clientHeight >= container.scrollHeight;

            if (bottomOfContainer && !this.loading && this.hasMoreItems) {
                this.fetchItems();
            }
        },
        onCommentTip(comment) {
            this.activeTipComment = comment;
            this.tipModalVisible = true;
        },
        loadMore() {
            this.fetchItems();
        },
    },
    computed: {
        ...mapGetters('posts', {
            localPosts: 'getPosts',
        }),
        hasItems() {
            return 0 < this.items.length;
        },
        translationsContext() {
            return {
                tradingUrl: this.$routing.generate('trading'),
            };
        },
        noRecentPostsAndCommentsMessage() {
            return this.$t('page.pair.no_recent_feed', this.translationsContext);
        },
        hasDeployedTokens() {
            return this.ownDeployedTokens && 0 !== this.ownDeployedTokens.length;
        },
        isZeroPage() {
            return 0 === this.page;
        },
        itemsToShow() {
            if (!this.firstPagePostsAmount || this.firstPageExpanded) {
                return this.items;
            }

            return this.items.slice(0, this.firstPagePostsAmount);
        },
    },
    mounted() {
        if (!this.lazyLoadingDisabled) {
            this.handleDebouncedScroll = debounce(this.onScrollDown, 100);

            window.addEventListener('scroll', () => {
                this.handleDebouncedScroll.cancel();
                this.handleDebouncedScroll();
            });
        }

        this.refetchItems();
    },
    beforeDestroy() {
        window.removeEventListener('scroll', this.handleDebouncedScroll);
    },
    watch: {
        hashtag() {
            this.refetchItems();
        },
        isAllTab() {
            this.refetchItems();
        },
        localPosts() {
            this.items.unshift(this.localPosts[0]);
        },
    },
};
</script>
