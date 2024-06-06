<template>
    <div>
        <post
            :post="post"
            :show-edit="showEdit"
            :logged-in="loggedIn"
            :is-owner="isOwner"
            is-single-post
            @edit-post="openEditModal"
            @delete-post="openDeleteModal"
            @share-post="sharePost($event)"
            @go-to-trade="$emit('go-to-trade', $event)"
        />
        <post-actions
            :subunit="subunit"
            :token-name="token.name"
            :tokens="[token]"
            :logged-in="loggedIn"
            :is-owner="isOwner"
            ref="postActions"
            @post-created="onCreatePostSuccess($event)"
            @post-edited="onEditPostSuccess($event)"
            @post-deleted="onDeletePostSuccess($event)"
        />
    </div>
</template>

<script>
import Post from './Post';
import {
    MoneyFilterMixin,
    NotificationMixin,
} from '../../mixins';
import PostActions from './PostActions';
import {mapGetters, mapMutations} from 'vuex';

export default {
    name: 'SinglePost',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
    ],
    components: {
        Post,
        PostActions,
    },
    props: {
        subunit: Number,
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
    },
    data() {
        return {
            postActions: null,
        };
    },
    mounted() {
        this.postActions = this.$refs['postActions'];
    },
    computed: {
        ...mapGetters('posts', {
            post: 'getSinglePost',
        }),
    },
    methods: {
        ...mapMutations('posts', [
            'setSinglePost',
            'updatePost',
            'deletePost',
        ]),
        onEditPostSuccess(post) {
            this.setSinglePost(post);
            this.updatePost(post);
        },
        onDeletePostSuccess() {
            this.$emit('post-deleted');
            this.deletePost(this.post);
        },
        openEditModal(post) {
            this.postActions.openEditModal(post);
        },
        openDeleteModal(post) {
            this.postActions.openDeleteModal(post);
        },
        sharePost(post) {
            this.postActions.sharePost(post);
        },
    },
};
</script>
