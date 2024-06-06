<template>
    <div
        v-if="post.token"
        :id="post.id"
        class="post mb-3 p-3"
        :class="cardClass"
        ref="post"
    >
        <div
            class="d-flex c-pointer"
            :class="[isSinglePost ? 'align-items-center' : 'align-items-start']"
            ref="post-link"
            @click.prevent="goToPost(post)"
        >
            <div
                :class="avatarClass"
                @click.stop.prevent="goToIntro(post)"
            >
                <img :src="tokenAvatar" class="avatar-img rounded-circle">
            </div>
            <div class="flex-fill">
                <div class="font-weight-semibold font-size-2 d-flex justify-content-between align-items-center">
                    <a class="link">{{ post.title }}</a>
                    <div class="post-icons">
                        <a
                            v-if="showEdit"
                            class="delete-icon mr-3"
                            @click.stop="$emit('delete-post', post)"
                        >
                            <font-awesome-icon
                                class="icon-default c-pointer align-middle"
                                icon="trash"
                            />
                        </a>
                        <a
                            v-if="showEdit"
                            class="post-edit-icon"
                            @click.stop="$emit('edit-post', post)"
                        >
                            <font-awesome-icon
                                class="icon-default c-pointer align-middle"
                                icon="edit"
                            />
                        </a>
                    </div>
                </div>
                <div
                    class="font-italic text-subtitle c-pointer"
                    ref="post-link"
                    :class="{'font-size-12 mt-n1': isHomePage}"
                    @click.prevent="goToPost(post)"
                >
                    {{ date }}
                </div>
                <div v-if="!isSinglePost">
                    <p v-if="post.content" class="post-content-short">
                        <plain-text-view :text="postContent" />
                    </p>
                    <p v-else>
                        {{ $t('post.logged_in.1') }}
                        <span
                            v-b-tooltip="modalTooltip"
                            class="link c-pointer d-inline-block"
                            @click.stop="goToTrade"
                        >
                            <span
                                class="link highlight d-inline-block"
                            >
                                {{ post.amount | toMoney | formatMoney }}
                            </span>
                            <coin-avatar
                                :image="tokenAvatar"
                                :is-user-token="true"
                            />
                            <span
                                class="link highlight d-inline-block"
                            >
                                {{ post.token.name | truncate(tokenTruncateLength) }}
                            </span>
                        </span>
                        {{ $t('post.logged_in.2') }}
                        <span
                            class="link highlight c-pointer d-inline-block"
                            @click.stop="goToTrade"
                        >
                            {{ $t('post.logged_in.trade') }}
                        </span>
                        {{ $t('post.logged_in.3') }}
                    </p>
                </div>
            </div>
        </div>
        <div
            v-if="!isSinglePost"
            class="d-flex flex-column flex-sm-row justify-content-between align-items-start mt-3 ml-2"
        >
            <div class="d-flex align-items-center">
                <div
                    class="d-flex align-items-center mr-3 font-size-2 c-pointer"
                    ref="post-link"
                    @click.prevent="goToPost(post)"
                >
                    <font-awesome-icon
                        :icon="['far', 'comment']"
                        class="mr-2"
                        transform="up-1.5"
                    />
                    {{ post.commentsCount }}
                </div>
                <post-likes
                    :is-liked="isUserAlreadyLiked"
                    :likes="postLikes"
                    :is-logged-in="loggedIn"
                    @like="toggleLike"
                />
            </div>
            <div v-if="showSeeMoreButton" class="d-flex justify-content-center mx-auto">
                <a
                    class="btn btn-secondary-rounded"
                    :href="singlePageUrl"
                >
                    {{ $t('see_more') }}
                </a>
            </div>
            <div class="d-flex justify-content-end">
                <a class="link" @click.stop="sharePost">
                    <span v-if="isReward">
                        {{ $t('post.share.reward', translationContext) }}
                        <coin-avatar
                            :is-user-token="true"
                            :image="tokenAvatar"
                        />
                        {{ post.token.name | truncate(tokenTruncateLength) }}
                    </span>
                    <span v-else>
                        {{ $t('post.share') }}
                    </span>
                </a>
            </div>
        </div>
        <div v-if="isSinglePost" class="mt-4 text-subtitle">
            <plain-text-view v-if="post.content" :text="postContent" />
            <p v-else>
                {{ $t('post.logged_in.1') }}
                <span
                    v-b-tooltip="modalTooltip"
                    class="link c-pointer d-inline-block"
                    @click.stop="goToTrade"
                >
                    <span
                        class="link highlight d-inline-block"
                    >
                        {{ post.amount | toMoney | formatMoney }}
                    </span>
                    <coin-avatar
                        :image="tokenAvatar"
                        :is-user-token="true"
                    />
                    <span
                        class="link highlight d-inline-block"
                    >
                        {{ post.token.name | truncate(tokenTruncateLength) }}
                    </span>
                </span>
                {{ $t('post.logged_in.2') }}
                <span
                    class="link highlight c-pointer d-inline-block"
                    @click.stop="goToTrade"
                >
                    {{ $t('post.logged_in.trade') }}
                </span>
                {{ $t('post.logged_in.3') }}
            </p>
        </div>
        <div v-if="isSinglePost" class="d-flex justify-content-between align-items-center">
            <single-post-status :is-logged-in="loggedIn" :is-single-post="isSinglePost" />
            <a
                v-if="!viewOnly"
                href="#"
                v-b-tooltip="modalTooltip"
                @click.stop="sharePost"
            >
                <span v-if="isReward">
                    {{ $t('post.share.reward', translationContext) }}
                    <coin-avatar
                        :is-user-token="true"
                        :image="tokenAvatar"
                    />
                    {{ post.token.name | truncate(tokenTruncateLength) }}
                </span>
                <span v-else>
                    {{ $t('post.share') }}
                </span>
                <guide v-if="hasReward && !isOwner" class="tooltip-center">
                    <template slot="header">
                        {{ $t('post.tooltip.share_post') }}
                    </template>
                </guide>
            </a>
        </div>
        <post-actions
            :subunit="post.token.subunit"
            :token-name="post.token.name"
            :logged-in="loggedIn"
            :is-owner="isOwner"
            :is-home-page="isHomePage"
            ref="postActions"
            @post-created="onCreatePostSuccess($event)"
            @post-edited="onEditPostSuccess($event)"
            @post-deleted="onDeletePostSuccess($event)"
        />
    </div>
</template>

<script>

import {VBTooltip} from 'bootstrap-vue';
import moment from 'moment';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {
    MoneyFilterMixin,
    NotificationMixin,
    FiltersMixin,
    StringMixin,
} from '../../mixins';
import {toMoney} from '../../utils';
import {mapGetters} from 'vuex';
import {
    GENERAL,
    TOKEN_NAME_TRUNCATE_LENGTH,
    CONTENT_TRUNCATE_LENGTH,
} from '../../utils/constants';
import Guide from '../Guide';
import PostLikes from './PostLikes';
import PostActions from './PostActions';
import SinglePostStatus from './SinglePostStatus';
import PlainTextView from '../UI/PlainTextView';
import CoinAvatar from '../CoinAvatar';

library.add(faEdit, faTrash);

const maxAllowedHeight = 575;

export default {
    name: 'Post',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
        FiltersMixin,
        StringMixin,
    ],
    components: {
        Guide,
        FontAwesomeIcon,
        PostLikes,
        SinglePostStatus,
        PlainTextView,
        PostActions,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        post: {
            type: Object,
            default: () => ({}),
        },
        index: {
            type: Number,
            default: null,
        },
        showEdit: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
        recentPost: Boolean,
        isSinglePost: Boolean,
        isOwner: {
            type: Boolean,
            default: false,
        },
        viewOnly: Boolean,
        redirect: Boolean,
        isHomePage: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            postActionsRef: null,
            showSeeMoreButton: false,
            requesting: false,
            tokenTruncateLength: TOKEN_NAME_TRUNCATE_LENGTH,
        };
    },
    computed: {
        ...mapGetters('user', {
            loggedInUserId: 'getId',
            userNickname: 'getNickname',
        }),
        ...mapGetters('posts', {
            postRewardsCollectableDays: 'getPostRewardsCollectableDays',
            isAuthorizedForReward: 'getIsAuthorizedForReward',
        }),
        date() {
            return moment(this.post.createdAt).format(GENERAL.dateTimeFormat);
        },
        singlePageUrl() {
            return this.post.slug
                ? this.$routing.generate(
                    'token_show_post',
                    {
                        name: this.dashedString(this.post.token.name),
                        slug: this.post.slug,
                    },
                    true)
                : this.$routing.generate('show_post', {id: this.post.id}, true);
        },
        hasReward() {
            return 0 !== parseFloat(this.post.shareReward);
        },
        isPostRewardOutdated() {
            return -moment(this.post.createdAt).diff(moment(), 'days') >= this.postRewardsCollectableDays;
        },
        reward() {
            return toMoney(this.post.shareReward);
        },
        isReward() {
            return this.hasReward
                && !this.post.isUserAlreadyRewarded
                && !this.isPostRewardOutdated
                && !this.isOwner;
        },
        translationContext() {
            return {
                amount: this.reward,
                tokenName: this.truncateFunc(this.post.token.name, TOKEN_NAME_TRUNCATE_LENGTH),
                title: this.post.title,
                url: this.singlePageUrl,
            };
        },
        postContent() {
            return this.isHomePage && this.post.content.length > CONTENT_TRUNCATE_LENGTH
                ? this.truncateFunc(this.post.content, CONTENT_TRUNCATE_LENGTH)
                : this.post.content;
        },
        tokenAvatar() {
            if (this.post.token.image) {
                return this.isHomePage
                    ? this.post.token.image.avatar_small
                    : this.post.token.image.avatar_large;
            }

            return require('../../../img/' + this.post.token.cryptoSymbol + '_avatar.png');
        },
        avatarClass() {
            return this.isSinglePost ? 'mr-4 ml-2' : 'mr-4 ml-2 mt-2';
        },
        postLikes() {
            return this.post.likes;
        },
        isUserAlreadyLiked() {
            return this.post.isUserAlreadyLiked;
        },
        tokenTradeTabLink() {
            return this.$routing.generate('token_show_trade', {name: this.post.token.name}, true);
        },
        shouldTruncateTokenName: function() {
            return this.post.token.name.length > this.tokenTruncateLength;
        },
        modalTooltip: function() {
            return this.shouldTruncateTokenName
                ? {
                    title: this.post.token.name,
                    boundary: 'viewport',
                    placement: 'bottom',
                }
                : null;
        },
        cardClass() {
            return this.isHomePage ? 'feed' : 'card';
        },
    },
    methods: {
        sharePost() {
            this.postActionsRef.sharePost(this.post);
            return false;
        },
        goToIntro(post) {
            location.href = this.$routing.generate('token_show_intro', {name: post.token.name});
        },
        goToPost(post) {
            this.$emit('go-to-post', post);
        },
        isMaxAllowedHeight() {
            return maxAllowedHeight <= this.$refs.post.clientHeight;
        },
        setSeeMoreButton() {
            setTimeout(() => {
                this.showSeeMoreButton = this.isMaxAllowedHeight();
            }, 0);
        },
        async toggleLike() {
            if (!this.loggedIn) {
                location.href = this.$routing.generate('login', {}, true);
                return;
            }

            if (this.requesting) {
                return;
            }

            this.requesting = true;
            try {
                this.saveLike();
                await this.$axios.single.post(this.$routing.generate('like_post', {id: this.post.id}));
            } catch (err) {
                this.saveLike();
                this.notifyError(err.response?.data?.message || this.$t('post.like.toggle'));
                this.$logger.error('error', 'Error during toggle like.', err);
            } finally {
                this.requesting = false;
            }
        },
        saveLike() {
            this.$emit('save-like', this.index);
        },
        goToTrade() {
            if (this.redirect) {
                window.location.href = this.tokenTradeTabLink;
            } else {
                this.$emit('go-to-trade', this.post.amount);
            }
        },
    },
    mounted() {
        this.postActionsRef = this.$refs['postActions'];
        this.setSeeMoreButton();
    },
    watch: {
        post: function() {
            this.setSeeMoreButton();
        },
    },
};
</script>
