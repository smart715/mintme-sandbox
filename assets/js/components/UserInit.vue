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
        userNickname: String,
        hasPhoneVerified: Boolean,
        isPhoneVerificationPending: Boolean,
        depositPhoneRequired: Boolean,
        withdrawalPhoneRequired: Boolean,
    },
    created() {
        this.setIsSignedInWithTwitter(this.isSignedInWithTwitter);
        this.setId(this.userId);
        this.setNickname(this.userNickname);
        this.setHasPhoneVerified(this.hasPhoneVerified);
        this.setIsPhoneVerificationPending(this.isPhoneVerificationPending);
        this.setDepositPhoneRequired(this.depositPhoneRequired);
        this.setWithdrawalPhoneRequired(this.withdrawalPhoneRequired);
    },
    methods: {
        ...mapMutations('user', [
            'setIsSignedInWithTwitter',
            'setId',
            'setNickname',
            'setHasPhoneVerified',
            'setIsPhoneVerificationPending',
            'setDepositPhoneRequired',
            'setWithdrawalPhoneRequired',
        ]),
    },
};
</script>
