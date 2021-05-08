<template>
    <div :id="post.id" class="post">
        <button v-if="showEdit"
                class="btn btn-link p-0 delete-icon float-right text-decoration-none text-reset"
                :disabled="deleteDisabled"
                @click="showModal"
        >
            <font-awesome-icon
                class="icon-default c-pointer align-middle"
                icon="trash"
                transform="shrink-4 up-1.5"
            />
        </button>
        <a v-if="showEdit"
           class="btn btn-link p-0 post-edit-icon float-right text-decoration-none text-reset"
           :href="$routing.generate('edit_post_page', {id: post.id})"
        >
            <font-awesome-icon
                class="icon-default c-pointer align-middle"
                icon="edit"
                transform="shrink-4 up-1.5"
            />
        </a>
        <template v-if="post.title">
            <h1 v-if="singlePage" class="post-title">
                {{ post.title }}
            </h1>
            <h2 v-else-if="recentPost">
                <a :href="singlePageUrl"
                   class="text-decoration-none text-white"
                   >{{ post.title }}
                </a>
                <b> {{ $t('by') }}</b>
                <a :href="$routing.generate('token_show', {name: post.token.name})" class="text-white">
                    <img :src="tokenAvatar"  class="rounded-circle d-inline-block" alt="avatar">
                </a>
                <small>{{ post.token.name }}</small>
            </h2>
            <a v-else :href="singlePageUrl"
               class="text-decoration-none"
               @click.prevent="$emit('go-to-post', post)"
            >
                <h2 class="post-title">
                    {{ post.title }}
                </h2>
            </a>
        </template>
        <div>
            <a :href="$routing.generate('profile-view', {nickname: post.author.nickname})" class="text-white">
                <img
                    :src="post.author.image.avatar_small"
                    class="rounded-circle d-inline-block"
                    alt="avatar"
                >
                {{ post.author.nickname }}
            </a>
            <a :href="singlePageUrl" class="text-decoration-none post-date">
                {{ date }}
            </a>
            <copy-link :content-to-copy="link" class="c-pointer ml-1">
              <font-awesome-icon :icon="['far', 'copy']"/>
            </copy-link>
        </div>
        <template>
            <p v-if="post.content" class="post-content my-2">
                <bbcode-view :value="post.content"/>
            </p>
            <p v-else>
              {{ $t('post.logged_in.1') }} <a href="#" @click.prevent="$emit('go-to-trade', post.amount)">{{post.amount | toMoney | formatMoney}} {{post.token.name}}</a> {{ $t('post.logged_in.2') }}
            </p>
        </template>
        <a :href="singlePageUrl" class="hover-icon text-decoration-none text-white">
            <font-awesome-icon
                class="c-pointer align-middle"
                icon="comment"
                transform="grow-1.5"
            />
          <span class="social-link ml-1">{{ post.commentsCount }} {{ $t('post.comments') }}</span>
        </a>
        <a href="#" @click="sharePost">
            {{ shareText }}
        </a>
        <p v-if="!post.content && singlePage" class="text-center">
            {{ commentsRestriction }}
        </p>
        <confirm-modal
            :visible="isModalVisible"
            @confirm="deletePost"
            @close="closeModal"
        >
            <p class="text-white modal-title pt-2">
                {{ $t('post.delete') }}
            </p>
        </confirm-modal>
        <confirm-modal
            :visible="showLoginModal"
            :show-image="false"
            @confirm="goToLogin"
            @cancel="goToSignup"
            @close="showLoginModal = false"
        >
            <p class="text-white modal-title pt-2">
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
    </div>
</template>

<script>
import BbcodeView from '../bbcode/BbcodeView';
import moment from 'moment';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash, faComment} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {MoneyFilterMixin, NotificationMixin, TwitterMixin, FiltersMixin} from '../../mixins';
import ConfirmModal from '../modal/ConfirmModal';
import Modal from '../modal/Modal';
import CopyLink from '../CopyLink';
import {formatMoney, openPopup, toMoney} from '../../utils';
import {mapGetters} from 'vuex';

library.add(faEdit);
library.add(faTrash);
library.add(faComment);

export default {
    name: 'Post',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
        TwitterMixin,
        FiltersMixin,
    ],
    components: {
        BbcodeView,
        ConfirmModal,
        FontAwesomeIcon,
        CopyLink,
        Modal,
    },
    props: {
        post: Object,
        index: {
            type: Number,
            default: null,
        },
        showEdit: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
        singlePage: Boolean,
        recentPost: Boolean,
    },
    data() {
        return {
            deleteDisabled: false,
            isModalVisible: false,
            showLoginModal: false,
            showTwitterSignInModal: false,
            showConfirmShareModal: false,
            showErrorModal: false,
        };
    },
    computed: {
        ...mapGetters('user', {loggedInUserId: 'getId'}),
        date() {
            return moment(this.post.createdAt).format('H:mm, MMM D, YYYY');
        },
        link() {
            return this.$routing.generate('token_show', {name: this.post.token.name, tab: 'posts'}, true) + '#' + this.post.id;
        },
        singlePageUrl() {
            return this.post.slug
                ? this.$routing.generate('new_show_post', {name: this.post.token.name, slug: this.post.slug}, true)
                : this.$routing.generate('show_post', {id: this.post.id}, true);
        },
        twitterMessageLink() {
            return 'https://twitter.com/intent/tweet?text=' + decodeURI(this.shareMessage);
        },
        shareMessage() {
            return this.$t('post.share.message', this.translationContext);
        },
        loginUrl() {
            return this.$routing.generate('login', {}, true);
        },
        signupUrl() {
            return this.$routing.generate('register', {}, true);
        },
        discordOrTelegram() {
          return this.post.token.discordUrl || this.post.token.telegramUrl;
        },
        hasReward() {
            return 0 !== parseFloat(this.post.shareReward);
        },
        reward() {
            return toMoney(this.post.shareReward);
        },
        commentsRestriction() {
          return this.$t('comment.min_amount', {token: this.post.token.name, amount: formatMoney(toMoney(this.post.amount))});
        },
        shareText() {
            return this.hasReward && !this.post.isUserAlreadyRewarded
                ? this.$t('post.share.reward', this.translationContext)
                : this.$t('post.share');
        },
        translationContext() {
            return {
                amount: this.reward,
                tokenName: this.post.token.name,
                title: this.post.title,
                url: this.singlePageUrl,
            };
        },
        isOwner() {
            return this.loggedInUserId === this.post.token.ownerId;
        },
        tokenAvatar() {
            return null !== this.post.image
                ? this.post.token.image.avatar_small
                : require('../../../img/' + this.post.token.cryptoSymbol + '_avatar.png');
        },
        tokenLink() {
            return this.$routing.generate('token_show', {name: this.post.token.name});
        },
    },
    methods: {
        deletePost() {
            this.deleteDisabled = true;
            this.$axios.single.post(this.$routing.generate('delete_post', {id: this.post.id}))
            .then((res) => {
               this.$emit('delete-post', this.index, this.post.id);
               this.notifySuccess(this.$t('post.deleted'));
            })
            .catch(() => {
                this.notifyError(this.$t('post.error.deleted'));
            })
            .finally(() => {
                this.deleteDisabled = false;
            });
        },
        showModal() {
            this.isModalVisible = true;
        },
        closeModal() {
            this.isModalVisible = false;
        },
        sharePost() {
            if (!this.hasReward || this.post.isUserAlreadyRewarded || this.isOwner) {
                openPopup(this.twitterMessageLink);
                return;
            }

            if (!this.loggedIn) {
                this.showLoginModal = true;
                return;
            }

            if (!this.isSignedInWithTwitter) {
                this.showTwitterSignInModal = true;
                return;
            }

            this.showConfirmShareModal = true;
        },
        doSharePost() {
            this.$axios.single.post(this.$routing.generate('share_post', {id: this.post.id}))
                .then(
                    () => {
                        this.$emit('update-post', {
                            ...this.post,
                            isUserAlreadyRewarded: true,
                        });
                        this.notifySuccess(this.$t('post.share.success', this.translationContext));
                    },
                    ({response}) => {
                        if ('invalid twitter token' === response.data.message) {
                            this.isSignedInWithTwitter = false;
                            this.showTwitterSignInModal = true;
                            return;
                        }

                        if ('not enough funds' === response.data.message) {
                            this.showErrorModal = true;
                            return;
                        }

                        this.notifyError(this.$t('toasted.error.try_later'));
                    }
                );
        },
        sharePostNotSignedIn() {
            this.signInWithTwitter().then(this.sharePost, (err) => this.notifyError(err.message));
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
