<template>
    <div></div>
</template>

<script>
import {mapMutations} from 'vuex';

export default {
    name: 'UserInit',
    props: {
        isSignedInWithTwitter: Boolean,
        userId: Number,
    },
    created() {
        this.setIsSignedInWithTwitter(this.isSignedInWithTwitter);
        this.setId(this.userId);
    },
    methods: {
        ...mapMutations('user', [
            'setIsSignedInWithTwitter',
            'setId',
        ]),
    },
};
</script>
