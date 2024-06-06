<template>
    <div>
        <create-post-modal
            v-if="!isHomePage"
            :visible="createPostModalVisible"
            :token-name="tokenName"
            :edit-post="activeModalPost"
            :subunit="subunit"
            :tokens="tokens"
            @close="onCreatePostModalClose"
            @save-success="onPostSaveSuccess"
        />
        <confirm-modal
            :visible="confirmDeletePostModalVisible"
            :submitting="isDeleting"
            :close-on-confirm="false"
            type="delete"
            :show-image="false"
            @confirm="deletePost"
            @close="closeDeletePostModal"
        >
            <p class="text-white modal-title pt-2">
                {{ $t('post.delete') }}
            </p>
            <template v-slot:confirm>
                {{ $t('confirm_modal.delete') }}
            </template>
        </confirm-modal>

        <confirm-modal
            :visible="showLoginModal"
            :show-image="false"
            @confirm="goToLogin"
            @cancel="goToSignup"
            @close="showLoginModal = false"
        >
            <p class="text-white modal-title text-break pt-2">
                {{ $t('post.reward.not_logged_in', translationContext) }}
            </p>
            <template v-slot:confirm>{{ $t('log_in') }}</template>
            <template v-slot:cancel>{{ $t('sign_up') }}</template>
        </confirm-modal>

        <confirm-modal
            :visible="showTwitterSignInModal"
            :show-image="false"
            @confirm="sharePostNotSignedIn"
            @cancel="showTwitterSignInModal = false"
            @close="showTwitterSignInModal = false"
        >
            {{ $t('post.share.sign_in_twitter') }}
            <template v-slot:confirm>{{ $t('twitter.sign_in') }}</template>
        </confirm-modal>

        <confirm-modal
            :visible="showConfirmShareModal"
            :show-image="false"
            @confirm="doSharePost"
            @cancel="showConfirmShareModal = false"
            @close="showConfirmShareModal = false"
        >
            <p>
                {{ $t('post.share.confirm_modal', translationContext) }}
            </p>
            <p>
                "{{ shareMessage }}"
            </p>
            <template v-slot:confirm>{{ $t('post.share.accept') }}</template>
        </confirm-modal>

        <modal
            :visible="showErrorModal"
            @close="showErrorModal = false"
        >
            <template v-slot:body>
                <p>{{ $t('post.share.not_enough_funds') }}</p>
                <p v-if="discordOrTelegram">
                    {{ $t('post.share.try_here') }}
                    <a :href="discordOrTelegram">
                        {{ discordOrTelegram }}
                    </a>
                </p>
            </template>
        </modal>

        <add-phone-alert-modal
            :visible="showNoPhoneNumberModal"
            :message="addPhoneModalMessage"
            @close="showNoPhoneNumberModal = false"
            @phone-verified="onPhoneVerified"
        />
    </div>
</template>

<script>
import CreatePostModal from '../modal/CreatePostModal';
import ConfirmModal from '../modal/ConfirmModal';
import {
    AddPhoneAlertMixin,
    MoneyFilterMixin,
    NotificationMixin,
    StringMixin,
    TwitterMixin,
} from '../../mixins';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';
import {openPopup, toMoney} from '../../utils';
import {mapGetters, mapMutations} from 'vuex';
import Modal from '../modal/Modal';
import moment from 'moment';
import {HTTP_UNAUTHORIZED, HTTP_INTERNAL_SERVER_ERROR, TWEET_URL} from '../../utils/constants';

export default {
    name: 'PostActions',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
        TwitterMixin,
        StringMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        CreatePostModal,
        ConfirmModal,
        AddPhoneAlertModal,
        Modal,
    },
    props: {
        subunit: Number,
        tokenName: String,
        loggedIn: Boolean,
        isOwner: Boolean,
        isHomePage: {
            type: Boolean,
            default: false,
        },
        tokens: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            createPostModalVisible: false,
            confirmDeletePostModalVisible: false,
            activeModalPost: null,
            isDeleting: false,
            showLoginModal: false,
            showTwitterSignInModal: false,
            showConfirmShareModal: false,
            showErrorModal: false,
            showNoPhoneNumberModal: false,
            addPhoneModalMessageType: 'post_share',
        };
    },
    computed: {
        ...mapGetters('user', {
            userNickname: 'getNickname',
            hasPhoneVerified: 'getHasPhoneVerified',
        }),
        ...mapGetters('posts', {
            postRewardsCollectableDays: 'getPostRewardsCollectableDays',
            isAuthorizedForReward: 'getIsAuthorizedForReward',
        }),
        singlePageUrl() {
            if (!this.activeModalPost) {
                return '';
            }

            return this.activeModalPost.slug
                ? this.$routing.generate(
                    'token_show_post',
                    {
                        name: this.dashedString(this.activeModalPost.token.name),
                        slug: this.activeModalPost.slug,
                    },
                    true)
                : this.$routing.generate('show_post', {id: this.activeModalPost.id}, true);
        },
        shareMessage() {
            return this.$t('post.share.message', this.translationContext);
        },
        twitterMessageLink() {
            return `${TWEET_URL}?text=${decodeURI(this.shareMessage)}`;
        },
        loginUrl() {
            return this.$routing.generate('login', {}, true);
        },
        signupUrl() {
            return this.$routing.generate('register', {}, true);
        },
        discordOrTelegram() {
            return this.activeModalPost
                && (this.activeModalPost.token.discordUrl || this.activeModalPost.token.telegramUrl);
        },
        reward() {
            return this.activeModalPost ? toMoney(this.activeModalPost.shareReward): '0';
        },
        translationContext() {
            return {
                amount: this.reward,
                tokenName: this.activeModalPost?.token.name,
                title: this.activeModalPost?.title,
                url: this.singlePageUrl,
            };
        },
    },
    mounted() {
        this.addPhoneModalProfileNickName = this.userNickname;
    },
    methods: {
        ...mapMutations('tradeBalance', ['setQuoteFullBalance']),
        openCreateModal() {
            this.activeModalPost = null;
            this.openCreatePostModal();
        },
        openEditModal(post) {
            this.activeModalPost = post;
            this.openCreatePostModal();
        },
        openDeleteModal(post) {
            this.activeModalPost = post;
            this.confirmDeletePostModalVisible = true;
        },
        deletePost() {
            this.isDeleting = true;
            this.$axios.single.post(this.$routing.generate('delete_post', {id: this.activeModalPost.id}))
                .then(() => {
                    this.$emit('post-deleted', this.activeModalPost);
                    this.activeModalPost = null;
                    this.notifySuccess(this.$t('post.deleted'));
                    this.closeDeletePostModal();
                })
                .catch(() => this.notifyError(this.$t('post.error.deleted')))
                .finally(() => this.isDeleting = false);
        },
        closeDeletePostModal() {
            this.confirmDeletePostModalVisible = false;
        },
        openCreatePostModal() {
            this.createPostModalVisible = true;
        },
        onCreatePostModalClose() {
            this.activeModalPost = null;
            this.createPostModalVisible = false;
        },
        onPostSaveSuccess(event) {
            if (event.isNew) {
                this.$emit('post-created', event.post);
            } else {
                this.activeModalPost = null;
                this.$emit('post-edited', event.post);
            }
        },
        sharePost(post) {
            this.activeModalPost = post;

            const hasReward = 0 !== parseFloat(post.shareReward);
            const daysFromPostCreation = moment(moment()).diff(post.createdAt, 'days');
            const isPostRewardOutdated = daysFromPostCreation >= this.postRewardsCollectableDays;

            if (!hasReward || this.activeModalPost.isUserAlreadyRewarded || this.isOwner || isPostRewardOutdated) {
                openPopup(this.twitterMessageLink);
                return;
            }

            if (!this.loggedIn) {
                this.showLoginModal = true;
                return;
            }

            if (!this.isAuthorizedForReward && !this.hasPhoneVerified) {
                this.showNoPhoneNumberModal = true;
                return;
            }

            if (!this.isSignedInWithTwitter) {
                this.showTwitterSignInModal = true;
                return;
            }

            this.showConfirmShareModal = true;
        },
        doSharePost() {
            this.$axios.single.post(this.$routing.generate('share_post', {id: this.activeModalPost.id}))
                .then(({data}) => {
                    this.activeModalPost.isUserAlreadyRewarded = true;
                    this.setQuoteFullBalance(data.balance ?? 0);
                    this.notifySuccess(this.$t('post.share.success', this.translationContext));
                })
                .catch((err) => {
                    if (HTTP_UNAUTHORIZED === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                        return;
                    }

                    if (HTTP_INTERNAL_SERVER_ERROR === err.response.status) {
                        this.notifyError(this.$t('toasted.error.service_unavailable'));
                        return;
                    }

                    if ('invalid twitter token' === err.response.data.message) {
                        this.isSignedInWithTwitter = false;
                        this.showTwitterSignInModal = true;
                        return;
                    }

                    if ('not enough funds' === err.response.data.message) {
                        this.showErrorModal = true;
                        return;
                    }

                    if ('post reward outdated' === err.response.data.message) {
                        openPopup(this.twitterMessageLink);
                        this.notifyError(this.$t('post.share.toasted.post_reward_outdated'));
                        return;
                    }

                    if ('post removed' === err.response.data.message) {
                        this.notifyError(this.$t('post.removed'));
                        return;
                    }

                    this.notifyError(this.$t('toasted.error.try_later'));
                });
        },
        onAddPhoneAlertModalConfirm() {
            this.showNoPhoneNumberModal = false;
            openPopup(this.twitterMessageLink);
        },
        sharePostNotSignedIn() {
            this.signInWithTwitter()
                .then(() => this.sharePost(this.activeModalPost))
                .catch((err) => this.notifyError(err.message));
        },
        onPhoneVerified() {
            this.showNoPhoneNumberModal = false;

            if (this.activeModalPost) {
                this.sharePost(this.activeModalPost);
            }
        },
        goToLogin() {
            location.href = this.loginUrl;
        },
        goToSignup() {
            location.href = this.signupUrl;
        },
    },
};
</script>
