<template>
    <div></div>
</template>

<script>
import {mapMutations} from 'vuex';

export default {
    name: 'PostsInit',
    props: {
        postRewardsCollectableDays: {
            type: Number,
            default: 30,
        },
        commentTipCost: {
            type: Number,
        },
        commentTipMinAmount: {
            type: Number,
        },
        commentTipMaxAmount: {
            type: Number,
        },
        isAuthorizedForReward: {
            type: Boolean,
            default: false,
        },
    },
    created() {
        this.setPostRewardsCollectableDays(this.postRewardsCollectableDays);
        this.setCommentTipCost(this.commentTipCost);
        this.setCommentTipMinAmount(this.commentTipMinAmount);
        this.setCommentTipMaxAmount(this.commentTipMaxAmount);
        this.setIsAuthorizedForReward(this.isAuthorizedForReward);
    },
    methods: {
        ...mapMutations('posts', [
            'setPostRewardsCollectableDays',
            'setCommentTipCost',
            'setCommentTipMinAmount',
            'setCommentTipMaxAmount',
            'setIsAuthorizedForReward',
        ]),
    },
};
</script>
