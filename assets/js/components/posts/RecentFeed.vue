<template>
    <div id="posts-container" ref="postsContainer" class="w-100 d-flex flex-column">
        <div v-if="loading" class="d-flex justify-content-center p-3">
            <span class="spinner-border spinner-border-md">
                <span class="sr-only"> {{ $t('loading') }} </span>
            </span>
        </div>
        <template v-else-if="hasItems">
            <div v-for="(n, i) in itemsToShow" :key="i">
                <post
                    v-if="itemsToShow[i].title"
                    :post="itemsToShow[i]"
                    :recent-post="true"
                    :logged-in="loggedIn"
                    :is-authorized-for-reward="isAuthorizedForReward"
                    :view-only="viewOnly"
                    :redirect="true"
                    :is-home-page="true"
                    :show-edit="false"
                    @go-to-post="openPost(itemsToShow[i])"
                    @save-like="onSaveLike(itemsToShow[i])"
                />
                <comment
                    v-else
                    :comment-prop="itemsToShow[i]"
                    :logged-in="loggedIn"
                    :user-has-deployed-tokens="hasDeployedTokens"
                    :redirect="true"
                    :is-home-page="true"
                    @tip="onCommentTip(itemsToShow[i])"
                    @go-to-post="openPost(itemsToShow[i].postId, itemsToShow[i])"
                />
            </div>
        </template>
        <div
            v-show="!hasItems && !loading"
            v-html-sanitize="noRecentPostsAndCommentsMessage"
            class="text-content-primary text-center pb-3"
        ></div>
        <div
            class="justify-content-center py-3 d-none"
            :class="{'invisible': !loading}"
        >
            <span class="spinner-border spinner-border-md">
                <span class="sr-only"> {{ $t('loading') }} </span>
            </span>
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
import {mapMutations} from 'vuex';
import Comment from './Comment';
import Post from './Post';
import {NotificationMixin} from '../../mixins';
import axios from 'axios';
import TipCommentModal from '../modal/TipCommentModal';

export default {
    name: 'RecentFeed',
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
        ownDeployedTokens: Array,
        min: Number,
        max: Number,
        showMore: {
            type: Boolean,
            default: false,
        },
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
            page: 0,
            cancelTokenSource: null,
            tipModalVisible: false,
            activeTipComment: null,
        };
    },
    methods: {
        ...mapMutations('posts', [
            'setPostRewardsCollectableDays',
            'setIsAuthorizedForReward',
            'setComments',
        ]),
        async fetchRecentPostsAndComments() {
            this.cancelTokenSource = axios.CancelToken.source();
            this.loading = true;

            try {
                const res = await this.$axios.single.get(this.$routing.generate(
                    'recent_posts_and_comments',
                    {page: this.page}
                ), {params: {all: true, max: this.max}, cancelToken: this.cancelTokenSource.token});

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
        proceedResponse(data) {
            this.comments = data.comments;
            this.setComments(this.comments);
            this.posts = data.posts;
            this.items = this.posts.concat(this.comments);
            this.items.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
        },
        openPost(post, comment = null) {
            window.location.href = null === comment
                ? this.$routing.generate('token_show_post', {name: post.token.name, slug: post.slug}, true)
                : this.$routing.generate('show_post', {id: post}, true) + '#comment-' + comment.id;
        },
        onSaveLike(post) {
            post.likes += post.isUserAlreadyLiked ? -1 : 1;
            post.isUserAlreadyLiked = !post.isUserAlreadyLiked;
        },
        onCommentTip(comment) {
            this.activeTipComment = comment;
            this.tipModalVisible = true;
        },
    },
    computed: {
        hasItems() {
            return 0 < this.items.length;
        },
        translationsContext() {
            return {
                tradingUrl: this.$routing.generate('trading', {type: 'tokens'}),
            };
        },
        noRecentPostsAndCommentsMessage() {
            return this.$t('page.pair.no_recent_feed', this.translationsContext);
        },
        hasDeployedTokens() {
            return this.ownDeployedTokens && 0 !== this.ownDeployedTokens.length;
        },
        offset() {
            return this.showMore ? this.max : this.min;
        },
        itemsToShow() {
            return this.items.slice(0, this.offset);
        },
    },
    async mounted() {
        await this.fetchRecentPostsAndComments();
    },
};
</script>
