<template>
    <a
        :href="getDirectMessageLink()"
        class="chat-envelope d-block text-white pl-2 pr-2"
    >
        <font-awesome-icon icon="envelope" size="lg" @click="checkDirectMessage" />
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
        checkDirectMessage: function() {
            this.notifyError('To send direct message you need to have 100 SuperCoins tokens');
        },
        getDirectMessageLink: function() {
            return this.$routing.generate('chat');
        },
    },
};
</script>
