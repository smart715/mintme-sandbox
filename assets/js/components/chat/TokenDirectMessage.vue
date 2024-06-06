<template>
    <div>
        <a
            :href="getDirectMessageLink"
            :class="{'btn btn-secondary btn-social': isMobileScreen}"
            @click="checkDirectMessage"
        >
            <font-awesome-icon
                v-if="isMobileScreen"
                :icon="['fas', 'envelope']"
            />
            <button v-else class="btn btn-primary shadow-none">
                {{ $t('chat.token.send_message') }}
            </button>
        </a>
        <modal
            id="auth-modal"
            :visible="showModal"
            dialog-class="modal-dialog"
            @close="closeModal"
        >
            <template slot="body">
                <login-signup-switcher
                    :login-recaptcha-sitekey="loginRecaptchaSitekey"
                    :reg-recaptcha-sitekey="regRecaptchaSitekey"
                    :embeded="true"
                    :showLabel="true"
                />
            </template>
        </modal>
    </div>
</template>

<script>
import {NotificationMixin} from '../../mixins';
import {mapGetters} from 'vuex';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEnvelope} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Modal from '../modal/Modal';

library.add(faEnvelope);

export default {
    name: 'TokenDirectMessage',
    components: {
        FontAwesomeIcon,
        Modal,
        LoginSignupSwitcher: () => import('../LoginSignupSwitcher').then((data) => data.default),
    },
    mixins: [NotificationMixin],
    props: {
        loggedIn: Boolean,
        isOwner: Boolean,
        isMobileScreen: Boolean,
        dmMinAmount: Number,
        tokenName: String,
        loginRecaptchaSitekey: String,
        regRecaptchaSitekey: String,
    },
    data() {
        return {
            showModal: false,
        };
    },
    computed: {
        ...mapGetters('tradeBalance', [
            'getQuoteFullBalance',
        ]),
        isEnoughUserFunds() {
            return this.getQuoteFullBalance >= this.dmMinAmount;
        },
        getDirectMessageLink: function() {
            if (this.isOwner) {
                return this.$routing.generate('chat');
            }

            if (this.isEnoughUserFunds) {
                return this.$routing.generate('new_dm_message', {tokenName: this.tokenName});
            }

            return null;
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
                this.loggedIn
                    ? this.notifyError(this.$t('chat.chat_box.min_amount_required_info', this.translationsContext))
                    : this.showModal = true;
            }
        },
        closeModal: function() {
            this.showModal = false;
        },
    },
};
</script>
