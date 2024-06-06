<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="font-size-3 font-weight-semibold header-highlighting" v-html="title"></div>
        </div>
        <div class="posts-container p-0">
            <create-post-modal
                v-if="isOwner"
                class="mb-3 pl-3 pt-3"
                is-post-form
                :visible="false"
                :tokens="[token]"
                @save-success="onCreatePostSuccess"
            />
            <div v-if="hasPosts" class="posts overflow-hidden position-relative">
                <div
                    id="posts-container"
                    ref="postsContainer"
                    class="w-100"
                    @scroll.native="handleScroll"
                >
                    <post
                        v-for="(post, i) in localPosts"
                        :post="post"
                        :key="i"
                        :index="i"
                        :show-edit="showEdit"
                        :logged-in="loggedIn"
                        :viewOnly="viewOnly"
                        :is-owner="isOwner"
                        @edit-post="openEditModal"
                        @delete-post="openDeleteModal"
                        @share-post="sharePost($event)"
                        @go-to-trade="$emit('go-to-trade', $event)"
                        @go-to-post="$emit('go-to-post', $event)"
                        @save-like="onSaveLike($event)"
                    />
                </div>
                <div v-if="!loadedAllPosts" class="d-flex justify-content-center my-4">
                    <m-button
                        v-if="!infiniteScroll"
                        type="secondary-rounded"
                        @click="loadMore()"
                    >
                        {{ $t('see_more') }}
                    </m-button>
                    <div
                        v-if="loadingNextPosts"
                        class="spinner-border spinner-border-sm"
                        role="status"
                    ></div>
                </div>
            </div>
            <div v-else class="card h-100 d-flex align-items-center justify-content-center">
                <span class="text-center py-4">
                    {{ $t('post.not_any_post') }}
                </span>
            </div>
        </div>
        <post-actions
            :subunit="subunit"
            :token-name="token.name"
            :logged-in="loggedIn"
            :is-owner="isOwner"
            :tokens="[token]"
            ref="postActions"
            @post-created="onCreatePostSuccess($event)"
            @post-edited="onEditPostSuccess($event)"
            @post-deleted="onDeletePostSuccess($event)"
        />
    </div>
</template>

<script>
import Post from './Post';
import {MButton} from '../UI';
import {
    MoneyFilterMixin,
    NotificationMixin,
} from '../../mixins';
import debounce from 'lodash/debounce';
import PostActions from './PostActions';
import {mapGetters, mapMutations} from 'vuex';
import CreatePostModal from '../modal/CreatePostModal';

export default {
    name: 'Posts',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
    ],
    components: {
        Post,
        MButton,
        PostActions,
        CreatePostModal,
    },
    props: {
        subunit: Number,
        posts: {
            type: Array,
            default: () => [],
        },
        postsAmount: {
            type: Number,
            default: null,
        },
        token: Object,
        tokenPage: {
            type: Boolean,
            default: false,
        },
        showEdit: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
        isOwner: Boolean,
        title: String,
        isMobileScreen: Boolean,
        disableInfiniteScrollOnMobile: Boolean,
        viewOnly: Boolean,
    },
    data() {
        return {
            loadingNextPosts: false,
            handleDebouncedScroll: null,
            infiniteScroll: false,
            postActionsRef: null,
            postsContainerEl: null,
            maxPostsAmount: this.postsAmount,
        };
    },
    created() {
        this.setPosts(this.posts);
        this.setTokenPostsAmount(this.postsAmount);
    },
    mounted() {
        this.handleDebouncedScroll = debounce(this.handleScroll, 100);
        window.addEventListener('scroll', this.handleDebouncedScroll);

        this.postActionsRef = this.$refs['postActions'];
        this.postsContainerEl = this.$refs['postsContainer'];
    },
    beforeDestroy() {
        window.removeEventListener('scroll', this.handleDebouncedScroll);
    },
    computed: {
        ...mapGetters('posts', {
            localPosts: 'getPosts',
        }),
        readMoreUrl() {
            return this.$routing.generate('token_show_post', {name: this.token.name});
        },
        hasPosts() {
            return 0 < this.localPosts.length;
        },
        loadedAllPosts() {
            return this.localPosts.length >= this.maxPostsAmount;
        },
    },
    methods: {
        ...mapMutations('posts', [
            'setPosts',
            'addPost',
            'deletePost',
            'updatePost',
            'insertPosts',
            'setTokenPostsAmount',
        ]),
        postToShow(index) {
            return this.localPosts[index];
        },
        handleScroll() {
            if (this.loadingNextPosts || !this.infiniteScroll || this.loadedAllPosts) {
                return;
            }

            if (
                this.postsContainerEl
                && this.postsContainerEl.getBoundingClientRect().bottom < window.innerHeight - 100
            ) {
                this.fetchPosts();
            }
        },
        onEditPostSuccess(post) {
            this.updatePost(post);
        },
        onDeletePostSuccess(post) {
            this.maxPostsAmount--;

            this.deletePost(post);
        },
        onCreatePostSuccess(postInfo) {
            this.maxPostsAmount++;

            this.addPost(postInfo.post);
        },
        openEditModal(post) {
            this.postActionsRef.openEditModal(post);
        },
        openDeleteModal(post) {
            this.postActionsRef.openDeleteModal(post);
        },
        openCreateModal() {
            this.postActionsRef.openCreateModal();
        },
        loadMore() {
            this.infiniteScroll = true;

            this.fetchPosts();
        },
        async fetchPosts() {
            if (this.loadingNextPosts) {
                return;
            }

            this.loadingNextPosts = true;

            try {
                const res = await this.$axios.single.get(
                    this.$routing.generate('list_posts', {tokenName: this.token.name}),
                    {params: {offset: this.localPosts.length}},
                );

                if (!res || !res.data.length) {
                    return;
                }

                this.insertPosts(res.data);
            } catch (err) {
                this.notifyError(this.$t('toasted.error.try_reload'));
            } finally {
                this.loadingNextPosts = false;
            }
        },
        sharePost(post) {
            this.postActionsRef.sharePost(post);
        },
        onSaveLike(postIndex) {
            if (this.localPosts[postIndex].isUserAlreadyLiked) {
                this.localPosts[postIndex].likes--;
                this.localPosts[postIndex].isUserAlreadyLiked = false;
            } else {
                this.localPosts[postIndex].likes++;
                this.localPosts[postIndex].isUserAlreadyLiked = true;
            }
        },
    },
};
</script>
