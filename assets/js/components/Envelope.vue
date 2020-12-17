<template>
    <a v-if="showEnvelope"
        :href="getDirectMessageLink"
        class="chat-envelope d-block text-white pl-2 pr-2"
        @click="checkDirectMessage"
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
        loggedIn: Boolean,
        isOwner: Boolean,
        dmMinAmount: String,
        getQuoteBalance: String,
        tokenName: String,
    },
    computed: {
        showEnvelope: function() {
            return this.loggedIn;
        },
        getDirectMessageLink: function() {
            if (this.isOwner) {
                return this.$routing.generate('chat');
            } else if (parseFloat(this.getQuoteBalance) >= parseFloat(this.dmMinAmount)) {
                return this.$routing.generate('chat', {tokenName: this.tokenName});
            } else {
                return null;
            }
        },
    },
    methods: {
        checkDirectMessage: function(e) {
            if (null === this.getDirectMessageLink) {
                e.preventDefault();
                this.notifyError(
                    'To send direct message you need to have '
                    + this.dmMinAmount
                    + ' '
                    + this.tokenName
                    +' tokens'
                );
            }
        },
    },
};
</script>
