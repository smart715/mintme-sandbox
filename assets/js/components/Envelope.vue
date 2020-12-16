<template>
    <a
        :href="getDirectMessageLink()"
        class="chat-envelope d-block text-white pl-2 pr-2"
    >
        <font-awesome-icon icon="envelope" size="lg" />
    </a>
</template>

<script>

import {NotificationMixin} from '../mixins';

export default {
    name: 'Envelope',
    mixins: [NotificationMixin],
    props: {
        isOwner: Boolean,
        dmMinAmount: String,
        getQuoteBalance: Boolean,
        tokenName: String,
    },
    methods: {
        getDirectMessageLink: function() {
            if (isOwner) {
                return this.$routing.generate('chat');
            } else if (parseFloat(this.getQuoteBalance) >= parseFloat(this.dmMinAmount)) {
                return this.$routing.generate('chat', {tokenName: this.tokenName});
            } else {
                this.notifyError('To send direct message you need to have 100 SuperCoins tokens');
                return '';
            }
        },
    },
};
</script>
