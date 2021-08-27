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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEnvelope} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {NotificationMixin} from '../../mixins';
import {mapGetters} from 'vuex';

library.add(faEnvelope);

export default {
    name: 'Envelope',
    components: {
        FontAwesomeIcon,
    },
    mixins: [NotificationMixin],
    props: {
        loggedIn: Boolean,
        isOwner: Boolean,
        dmMinAmount: Number,
        tokenName: String,
    },
    computed: {
        ...mapGetters('tradeBalance', [
            'getQuoteBalance',
        ]),
        showEnvelope: function() {
            return this.loggedIn;
        },
        getDirectMessageLink: function() {
            if (this.isOwner) {
                return this.$routing.generate('chat');
            } else if (this.getQuoteBalance >= this.dmMinAmount) {
                return this.$routing.generate('new_dm_message', {tokenName: this.tokenName});
            } else {
                return null;
            }
        },
        translationsContext: function() {
            return {
                currency: this.tokenName,
                amount: this.dmMinAmount,
            };
        },
    },
    methods: {
        checkDirectMessage: function(e) {
            if (!this.getDirectMessageLink) {
                e.preventDefault();
                this.notifyError(this.$t('chat.chat_box.min_amount_required_info', this.translationsContext));
            }
        },
    },
};
</script>
