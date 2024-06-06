<template>
    <div :ref="commentId">
        <div class="comment p-3 mb-4" :class="cardClass">
            <div class="d-flex">
                <div class="mr-4 ml-2 mb-3 mt-2">
                    <img
                        :src="authorAvatar"
                        class="rounded-circle avatar-img"
                        alt="avatar"
                    />
                </div>
                <div class="flex-fill">
                    <div class="font-weight-semibold font-size-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex flex-column flex-sm-row justify-content-start align-items-start">
                            <img
                                v-if="medalImg"
                                :src="medalImg"
                                class="medal mr-1"
                                alt="medal"
                            />
                            <a :href="profileUrl" class="font-weight-semibold font-size-2">
                                {{ comment.author.profile.nickname }}
                            </a>
                            <div
                                class="font-weight-normal font-size-sm ml-sm-2 comment-mb-n c-pointer comment-date"
                                @click.prevent="goToPost"
                            >
                                <i>{{ date }}</i>
                            </div>
                            <div class="tips-wrp ml-1 d-flex pr-2">
                                <a v-for="tip in tips" :key="tip.tokenName" :href="getTipTokenLink(tip)">
                                    <img
                                        class="tip-avatar"
                                        v-b-tooltip.hover.v-white
                                        :title="getTipHint(tip.amount)"
                                        :src="tip.tokenImage.avatar_small"
                                    />
                                </a>
                                <div class="overflow-limiter"></div>
                            </div>
                            <div
                                v-if="extraTipsAmount > 0"
                                class="text-subtitle font-weight-normal font-size-sm text-nowrap mr-1 comment-mb-n"
                            >
                                {{$t('comment.tip.list_exceed', {amount: extraTipsAmount})}}
                            </div>
                        </div>
                        <div v-if="!isHomePage">
                            <a
                                v-if="comment.deletable"
                                class="delete-icon mr-3"
                                @click="deleteComment"
                            >
                                <font-awesome-icon
                                    class="icon-default c-pointer align-middle"
                                    icon="trash"
                                />
                            </a>
                            <a
                                v-if="comment.editable"
                                class="comment-edit-icon"
                                @click="editing = true"
                            >
                                <font-awesome-icon
                                    class="icon-default c-pointer align-middle"
                                    icon="edit"
                                />
                            </a>
                        </div>
                    </div>
                    <div class="text-subtitle">
                        <div v-if="!editing">
                            <p
                                v-if="comment.content"
                                v-html="commentContent"
                                class="text-break plain-text-content"
                            ></p>
                            <p v-else>
                                {{ $t('comment.logged_in.1') }}
                                <span
                                    v-b-tooltip="modalTooltip"
                                    class="link c-pointer d-inline-block"
                                    @click.stop="goToTrade"
                                >
                                    <span class="link highlight d-inline-block">
                                        {{ comment.postAmount | toMoney | formatMoney }}
                                    </span>
                                    <coin-avatar
                                        :image="tokenAvatar"
                                        :is-user-token="true"
                                    />
                                    <span class="link highlight d-inline-block">
                                        {{ comment.token.name | truncate(tokenTruncateLength) }}
                                    </span>
                                </span>
                                {{ $t('comment.logged_in.2') }}
                                <span
                                    class="link highlight c-pointer d-inline-block"
                                    @click.stop="goToTrade"
                                >
                                    {{ $t('comment.logged_in.trade') }}
                                </span>
                                {{ $t('comment.logged_in.3') }}
                            </p>
                        </div>
                        <div v-else class="mt-4 mx-2">
                            <comment-form
                                :logged-in="loggedIn"
                                :prop-content="comment.content"
                                :api-url="apiUrl"
                                :comment-min-amount="commentMinAmount"
                                :is-owner="isOwner"
                                :is-edit="editing"
                                @submitted="onEditCommentSuccess"
                                @error="notifyError('Error editing comment.')"
                                @cancel="cancelEditing"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <comment-status
                class="my-2 mx-3"
                :is-logged-in="loggedIn"
                :comment="comment"
                :user-has-deployed-tokens="userHasDeployedTokens"
                @tip="$emit('tip')"
            />
        </div>
    </div>
</template>

<script>
import CommentForm from './CommentForm';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import {MoneyFilterMixin, NotificationMixin, FiltersMixin} from '../../mixins';
import CommentStatus from './CommentStatus';
import {
    GENERAL,
    HTTP_ACCESS_DENIED,
    CONTENT_TRUNCATE_LENGTH,
    TOKEN_NAME_TRUNCATE_LENGTH,
} from '../../utils/constants';
import {VBTooltip} from 'bootstrap-vue';
import {toMoney, getRankMedalSrcByNickname, addHtmlHashtagsToText} from '../../utils';
import CoinAvatar from '../CoinAvatar';

library.add(faEdit, faTrash);

const TIPS_TO_SHOW_AMOUNT = 20;

export default {
    name: 'Comment',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
        FiltersMixin,
    ],
    components: {
        FontAwesomeIcon,
        CommentForm,
        CommentStatus,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        commentProp: Object,
        index: Number,
        loggedIn: Boolean,
        commentMinAmount: Number,
        isOwner: Boolean,
        userHasDeployedTokens: Boolean,
        redirect: Boolean,
        topHolders: {
            type: Array,
            default: () => [],
        },
        isHomePage: {
            type: Boolean,
            default: false,
        },
    },
    mounted() {
        this.$nextTick(() => {
            this.handleHashChange();
        });
    },
    data() {
        return {
            editing: false,
            liking: false,
            isConfirmVisible: false,
            comment: this.commentProp,
            tokenTruncateLength: TOKEN_NAME_TRUNCATE_LENGTH,
        };
    },
    computed: {
        date() {
            return moment(this.comment.createdAt).format(GENERAL.dateTimeFormat);
        },
        apiUrl() {
            return this.$routing.generate('edit_comment', {commentId: this.comment.id});
        },
        profileUrl() {
            return this.$routing.generate('profile-view', {nickname: this.comment.author.profile.nickname});
        },
        tips() {
            return this.comment?.tips || [];
        },
        extraTipsAmount() {
            return this.tips.length - TIPS_TO_SHOW_AMOUNT;
        },
        medalImg() {
            return getRankMedalSrcByNickname(this.topHolders, this.comment.author.profile.nickname);
        },
        commentId() {
            return 'comment-' + this.comment.id;
        },
        isCommentOnFeedPage() {
            return window.location.pathname.includes(this.$routing.generate('homepage', true));
        },
        commentContent() {
            const commentContent = this.isHomePage && this.comment?.content.length > CONTENT_TRUNCATE_LENGTH
                ? this.truncateFunc(this.comment.content, CONTENT_TRUNCATE_LENGTH)
                : this.comment.content;

            return this.proceedHashtags(commentContent || '');
        },
        authorAvatar() {
            return this.isHomePage
                ? this.comment.author.profile.image.avatar_small
                : this.comment.author.profile.image.avatar_large;
        },
        cardClass() {
            return this.isHomePage ? 'feed' : 'card';
        },
        tokenAvatar() {
            if (this.comment.token.image) {
                return this.isHomePage
                    ? this.comment.token.image.avatar_small
                    : this.comment.token.image.avatar_large;
            }

            return require('../../../img/' + this.comment.token.cryptoSymbol + '_avatar.png');
        },
        tokenTradeTabLink() {
            return this.$routing.generate('token_show_trade', {name: this.comment.token.name}, true);
        },
        shouldTruncateTokenName: function() {
            return this.comment.token.name.length > this.tokenTruncateLength;
        },
        modalTooltip: function() {
            return this.shouldTruncateTokenName
                ? {
                    title: this.comment.token.name,
                    boundary: 'viewport',
                    placement: 'bottom',
                }
                : null;
        },
    },
    methods: {
        onEditCommentSuccess(comment) {
            this.comment = comment;
            this.$emit('update-comment', comment);
            this.cancelEditing();
        },
        cancelEditing() {
            this.editing = false;
        },
        likeComment() {
            if (!this.loggedIn) {
                location.href = this.$routing.generate('login', {}, true);
                return;
            }
            if (this.liking) {
                return;
            }
            this.liking = true;
            this.$axios.single.post(this.$routing.generate('like_comment', {commentId: this.comment.id}))
                .then(() => {
                    this.$emit('update-comment', {
                        ...this.comment,
                        likeCount: this.comment.likeCount + (this.comment.liked ? -1 : 1),
                        liked: !this.comment.liked,
                    });
                })
                .catch((error) => {
                    if (HTTP_ACCESS_DENIED === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('Error liking comment.');
                    }
                })
                .finally(() => this.liking = false);
        },
        deleteComment() {
            this.$emit('delete-comment', this.comment);
        },
        getTipHint(amount) {
            return this.$t('comment.tip.tooltip', {amount: toMoney(amount)});
        },
        getTipTokenLink(tip) {
            if (!tip.tokenName) {
                return '';
            }

            return this.$routing.generate('token_show_intro', {name: tip.tokenName});
        },
        goToPost() {
            history.replaceState(null, null, '#' + this.commentId);
            this.$emit('go-to-post');
        },
        handleHashChange() {
            const hashFragment = window.location.hash;

            if (!hashFragment) {
                return;
            }

            const commentIdToScrollTo = hashFragment.slice(1);
            const targetComment = this.$refs[commentIdToScrollTo];

            if (!targetComment) {
                return;
            }

            const scrollOffset = this.isCommentOnFeedPage
                ? targetComment.offsetHeight
                : -targetComment.offsetHeight;

            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'manual';
            }

            window.scrollTo({
                top: targetComment.offsetTop + scrollOffset,
                behavior: 'smooth',
            });
        },
        proceedHashtags(text) {
            return addHtmlHashtagsToText(text, `${this.$routing.generate('homepage')}?hashtag=$1`);
        },
        goToTrade() {
            if (this.redirect) {
                window.location.href = this.tokenTradeTabLink;
            } else {
                this.$emit('go-to-trade', this.comment.postAmount);
            }
        },
    },
};
</script>
