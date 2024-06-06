<template>
    <comments
        v-if="!isLoading"
        :post="post"
        :comments="comments"
        :logged-in="loggedIn"
        :own-deployed-tokens="ownDeployedTokensArray"
        :is-owner="isOwner"
        :comment-min-amount="commentMinAmount"
        :top-holders="topHolders"
        @delete-comment="onCommentDelete"
        @add-comment="onCommentAdd"
        @update-comment="onCommentUpdate"
    />
    <div v-else class="p-3 d-flex justify-content-center align-items-center">
        <div class="spinner-border spinner-border-sm" role="status"></div>
    </div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import Comments from './Comments';

export default {
    name: 'SinglePostComments',
    components: {
        Comments,
    },
    props: {
        loggedIn: Boolean,
        isLoading: Boolean,
        commentMinAmount: Number,
        isOwner: Boolean,
        topHolders: Array,
    },
    computed: {
        ...mapGetters('posts', {
            post: 'getSinglePost',
            comments: 'getComments',
        }),
        ...mapGetters('user', {
            ownDeployedTokens: 'getOwnDeployedTokens',
        }),
        ownDeployedTokensArray() {
            const tokens = Array.isArray(this.ownDeployedTokens)
                ? this.ownDeployedTokens
                : Object.values(this.ownDeployedTokens);
            return tokens;
        },
    },
    methods: {
        ...mapMutations('posts', [
            'addComment',
            'editComment',
            'removeCommentById',
        ]),
        onCommentDelete(comment) {
            this.removeCommentById(comment.id);
        },
        onCommentAdd(comment) {
            this.addComment(comment);
        },
        onCommentUpdate(comment) {
            this.editComment(comment);
        },
    },
};
</script>
