<template>
    <div class="row">
        <div class="col text-truncate">
            <span
                id="channel-link"
                class="c-pointer text-white"
                @click="addProfile"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block mr-2">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'square-x-twitter'}"
                        size="lg"
                    />
                </span>
                <a href="#" class="highlight link text-reset text-nowrap">
                    {{ computedProfile }}
                </a>
            </span>
        </div>
        <div class="col-auto">
            <a
                v-if="currentProfile"
                @click.prevent="deleteProfile"
            >
              <font-awesome-icon
                  icon="times"
                  class="text-danger c-pointer ml-2"
              />
            </a>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faSquareXTwitter} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin, NotificationMixin} from '../../../mixins';
import {HTTP_OK, TWITTER_URL} from '../../../utils/constants';
import {initializeApp} from 'firebase/app';
import {
    TwitterAuthProvider,
    getAuth,
    signInWithPopup,
} from 'firebase/auth';
library.add(faSquareXTwitter, faTimes);

export default {
    name: 'TokenTwitterAddress',
    components: {
        FontAwesomeIcon,
    },
    mixins: [
        FiltersMixin,
        NotificationMixin,
    ],
    props: {
        address: String,
        tokenName: String,
    },
    data() {
        return {
            currentProfile: this.address,
            submitting: false,
            updateUrl: this.$routing.generate('token_update', {
                name: this.tokenName,
            }),
        };
    },
    computed: {
        computedProfile: function() {
            return this.currentProfile || this.$t('token.twitter.empty_address');
        },
    },
    mounted() {
        this.initFireBase();
        this.initTwitterAuthPopUp();
    },
    methods: {
        initFireBase: function() {
            initializeApp(window.firebaseConfig);
        },
        initTwitterAuthPopUp: function() {
            window.twitterProvider = new TwitterAuthProvider();
            window.auth = getAuth();
            window.auth.useDeviceLanguage();
            window.signInWithPopup = signInWithPopup;
        },
        addProfile: async function() {
            if (this.currentProfile) {
                return;
            }

            try {
                const result = await window.signInWithPopup(window.auth, window.twitterProvider);
                const user = result.user;
                this.currentProfile = `${TWITTER_URL}/${user.reloadUserInfo.screenName}`;
                await this.saveTwitterProfile(this.currentProfile);
            } catch (error) {
                if (error.response) {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.$logger.error('Can not save Twitter profile', error);
                } else {
                    this.notifyInfo(this.$t('toasted.info.operation_canceled'));
                }
            }
        },
        deleteProfile: function() {
            this.currentProfile = '';
            this.saveTwitterProfile(this.currentProfile);
        },
        saveTwitterProfile: async function(twitterUrl) {
            if (this.submitting) {
                return;
            }

            this.submitting = true;

            try {
                const response = await this.$axios.single.patch(this.updateUrl, {twitterUrl});

                if (HTTP_OK === response.status) {
                    this.currentProfile = twitterUrl;
                    const message = twitterUrl
                        ? this.$t('toasted.success.twitter.added')
                        : this.$t('toasted.success.twitter.deleted');
                    this.notifySuccess(message);
                    this.$emit('saveTwitter', twitterUrl);
                }
            } catch (error) {
                if (!error.response) {
                    this.notifyError(this.$t('toasted.error.network'));
                    this.$logger.error('Save twitter address network error', error);
                } else if (error.response.data.message) {
                    this.notifyError(error.response.data.message);
                    this.$logger.error('Can not save twitter', error);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.$logger.error('An error has occurred, please try again later', error);
                }
            }

            this.submitting = false;
        },
    },
};
</script>
