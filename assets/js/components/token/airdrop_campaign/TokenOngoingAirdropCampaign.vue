<template>
    <div class="mx-3 mx-xl-0">
        <div
            ref="ongoing-airdrop-campaign"
            class="airdrop-container card col-12 mt-3"
            :class="{'h-100': embeded}"
        >
            <p v-if="storageError" class="p-3">
                {{ $t('browser.storage.error') }}
            </p>
            <div v-if="loaded">
                <div class="row py-2 justify-content-between">
                    <div class="d-inline-block col-lg-10 col-sm-9 col pr-sm-0 align-self-center">
                        <span class="message">
                            <span class="text-bold sm-responsive-text">{{ $t('ongoing_airdrop.title') }}</span>
                        </span>
                        <span class="message d-none d-sm-inline">
                            {{ $t('ongoing_airdrop.msg.1', translationsContext) }}
                            <coin-avatar
                                :image="tokenAvatar"
                                :is-user-token="true"
                            />
                            {{ tokenName | truncate }}
                            {{ $t('ongoing_airdrop.msg.2', translationsContext) }}
                        </span>
                        <span
                            v-if="showEndDate"
                            class="m-0 message"
                        >
                            <span class="d-none d-sm-inline">
                                {{ $t('ongoing_airdrop.ends', translationsContext) }}
                            </span>
                            <span v-if="showDuration">
                                <span class="d-none d-sm-inline">
                                    {{ durationValues }}
                                </span>
                                <span class="d-block d-sm-none sm-responsive-text">
                                    {{ airdropEndsMessage }}
                                </span>
                            </span>
                            <span v-if="timeElapsed">{{ $t('ongoing_airdrop.ended.2') }}</span>
                        </span>
                    </div>
                    <div class="d-inline-block col-lg-2 col-sm-3 col pl-sm-0 mt-0 text-right align-self-center">
                        <template v-if="!timeElapsed">
                            <span v-if="alreadyClaimed">
                                <button
                                    class="btn btn-primary sm-responsive-text"
                                    disabled
                                >
                                    {{ $t('ongoing_airdrop.claimed') }}
                                </button>
                            </span>
                            <span v-else>
                                <copy-link
                                    v-if="isOwner" :href="modalTokenUrl"
                                    class="btn btn-primary sm-responsive-text"
                                    :content-to-copy="modalTokenUrl"
                                >
                                    {{ $t('ongoing_airdrop.participate') }}
                                </copy-link>
                                <a
                                    v-else
                                    class="btn btn-primary sm-responsive-text"
                                    :href="modalTokenUrl"
                                    @click.prevent="showModalOnClick"
                                >
                                    {{ $t('ongoing_airdrop.participate') }}
                                </a>
                            </span>
                        </template>
                        <confirm-modal
                            :visible="showModal"
                            :button-disabled="buttonDisabled"
                            :show-cancel-button="showCancelButton"
                            :show-confirm-button="showConfirmButton"
                            :show-image="false"
                            :embeded="embeded"
                            @confirm="modalOnConfirm"
                            @close="closeModal"
                        >
                            <p v-if="alreadyClaimed">{{ $t('airdrop_backend.already_claimed') }}</p>
                            <p v-else-if="claim">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </p>
                            <div
                                v-else-if="airdropCampaign.status"
                                class="d-flex flex-column align-items-center"
                            >
                                <p
                                    class="text-white modal-title text-break pt-2 pb-4"
                                    v-html="confirmModalMessage"
                                />
                                <div
                                    v-if="!isOwner && actionsLength > 0 && !alreadyClaimed && loaded"
                                    class="w-75"
                                >
                                    <div
                                        v-if="airdropCampaign.actions.twitterMessage"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'x-twitter']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span
                                            class="ml-4 pl-2 c-pointer"
                                            @click.prevent="claimTwitterMessage"
                                        >
                                            {{ $t('airdrop.actions.twitter_message') }}
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.twitterMessage.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.twitterRetweet"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'x-twitter']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span
                                            class="ml-4 pl-2 c-pointer"
                                            @click.prevent="claimTwitterRetweet"
                                        >
                                            {{ $t('airdrop.actions.twitter_retweet') }}
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.twitterRetweet.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.facebookMessage"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'facebook-f']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span
                                            class="ml-4 pl-2 c-pointer"
                                            @click.prevent="openFacebookMessage"
                                        >
                                            {{ $t('airdrop.actions.facebook_message') }}
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.facebookMessage.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.facebookPage"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'facebook-f']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span class="ml-4 pl-2">
                                            <a
                                                class="text-decoration-none text-white"
                                                rel="noopener nofollow"
                                                target="_blank"
                                                :href="airdropCampaign.actionsData.facebookPage"
                                                @click="claimAction(actionsToServer.facebookPage)"
                                            >
                                                {{ $t('airdrop.actions.facebook_page') }}
                                            </a>
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.facebookPage.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.facebookPost"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'facebook-f']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span class="ml-4 pl-2">
                                            <a
                                                class="text-decoration-none text-white"
                                                rel="noopener nofollow"
                                                target="_blank"
                                                :href="airdropCampaign.actionsData.facebookPost"
                                                @click="claimAction(actionsToServer.facebookPost)"
                                            >
                                                {{ $t('airdrop.actions.facebook_post') }}
                                            </a>
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.facebookPost.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.linkedinMessage"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'linkedin-in']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span
                                            class="ml-4 pl-2 c-pointer"
                                            @click.prevent="claimLinkedin"
                                        >
                                            {{ $t('airdrop.actions.linkedin_message') }}
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.linkedinMessage.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.youtubeSubscribe"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                :icon="['fab', 'youtube']"
                                                size="lg"
                                                transform="right-3 down-1 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <span
                                            class="ml-4 pl-2 c-pointer"
                                            @click.prevent="claimYoutube"
                                        >
                                            {{ $t('airdrop.actions.youtube_subscribe') }}
                                        </span>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.youtubeSubscribe.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.postLink"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                icon="globe"
                                                size="lg"
                                                transform="right-3 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <div class="ml-4 pl-2 d-flex flex-column align-items-start w-75">
                                            <span>{{ $t('airdrop.actions.post_link') }}</span>
                                            <copy-link
                                                :content-to-copy="tokenUrl"
                                                class="c-pointer row mr-0 w-100"
                                            >
                                                <a class="truncate-block">
                                                    {{ tokenUrl }}
                                                </a>
                                                <font-awesome-icon :icon="['far', 'copy']"/>
                                            </copy-link>
                                        </div>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.postLink.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.postLink"
                                        class="d-flex flex-column my-3"
                                    >
                                        <div class="clearfix">
                                            <div class="float-left">
                                                <div
                                                    v-if="domainErrorMessage"
                                                    class="alert alert-danger alert-float"
                                                >
                                                    <font-awesome-icon icon="exclamation-circle"/>
                                                    {{ domainErrorMessage }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex">
                                            <input
                                                id="airdropDomain"
                                                class="form-control font-size-12"
                                                type="text"
                                                v-model="postLinkUrl"
                                                :placeholder="$t('ongoing_airdrop.post_link_placeholder')"
                                            >
                                            <button
                                                class="btn btn-primary text-nowrap ml-1"
                                                :disabled="postLinkUrlDisabled"
                                                @click="claimAction(actionsToServer.postLink)"
                                            >
                                                {{ $t('ongoing_airdrop.submit_url') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        v-if="airdropCampaign.actions.visitExternalUrl"
                                        class="d-flex my-3"
                                    >
                                        <font-awesome-layers class="mt-1">
                                            <font-awesome-icon
                                                icon="globe"
                                                size="lg"
                                                transform="right-3 shrink-1"
                                            />
                                        </font-awesome-layers>
                                        <div class="ml-4 pl-2 d-flex flex-column align-items-start w-75">
                                            <span>{{ $t('airdrop.actions.visit_external_url') }}</span>
                                                <div class="row mr-0 w-100">
                                                    <a
                                                        ref="externalLink"
                                                        class="col truncate-name pr-2 d-flex justify-content-start"
                                                        rel="nofollow noopener"
                                                        :href="airdropCampaign.actionsData.visitExternalUrl"
                                                        @click.stop.prevent="openLeaveSiteModal($event)"
                                                    >
                                                        <span
                                                            v-if="isLongUrl"
                                                            v-b-tooltip="airdropCampaign.actionsData.visitExternalUrl"
                                                        >
                                                            {{ externalUrlTruncated }}
                                                        </span>
                                                        <span
                                                            v-else
                                                        >
                                                            {{ airdropCampaign.actionsData.visitExternalUrl }}
                                                        </span>
                                                    </a>
                                                </div>
                                        </div>
                                        <span class="ml-auto">
                                            {{ airdropCampaign.actions.visitExternalUrl.done ? '1' : '0' }}/1
                                        </span>
                                    </div>
                                    <confirm-modal
                                        :visible="showLeaveSiteModal"
                                        :show-image="false"
                                        :button-disabled="viewOnly"
                                        @confirm="leaveSite"
                                        @cancel="showLeaveSiteModal = false"
                                        @close="showLeaveSiteModal = false"
                                    >
                                        <slot>
                                            <div>
                                                {{ $t('global_confirm_modal.external_url_warning_1') }}
                                                <span
                                                    v-if="isLongUrl"
                                                    v-b-tooltip="airdropCampaign.actionsData.visitExternalUrl"
                                                    class="text-primary-darker"
                                                >
                                                    {{ externalUrlTruncated }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-primary-darker"
                                                >
                                                    {{ airdropCampaign.actionsData.visitExternalUrl }}
                                                </span>
                                                {{ $t('global_confirm_modal.external_url_warning_2') }}
                                            </div>
                                            <div class="pt-3">
                                                {{ $t('global_confirm_modal.external_url_confirm') }}
                                            </div>
                                        </slot>
                                        <template v-slot:confirm>
                                            {{ $t('confirm_modal.continue') }}
                                        </template>
                                    </confirm-modal>
                                </div>
                                <div
                                    v-if="!embeded && loggedIn && !isOwner && loaded"
                                    class="align-self-start text-left mt-4 word-break"
                                >
                                    <div v-html="$t('ongoing_airdrop.referral', translationsContext)" />
                                    <div class="my-2">
                                        <copy-link
                                            class="c-pointer"
                                            :content-to-copy="referralLink"
                                        >
                                            <a
                                                :href="referralLink"
                                                class="link highlight"
                                            >
                                                {{ referralLink }}
                                            </a>
                                            <font-awesome-icon
                                                :icon="['far', 'copy']"
                                                class="icon-default"
                                            />
                                        </copy-link>
                                    </div>
                                </div>
                            </div>
                            <p
                                v-else
                                v-html="airdropEndedText"
                            />
                            <template
                                v-if="isOwner || timeElapsed"
                                v-slot:confirm
                            >
                                {{ confirmButtonText }}
                            </template>
                            <p v-if="embeded && isLoginTabOpen">
                                {{ $t('ongoing_airdrop.embeded.login_tab') }}
                                <br>
                                <a
                                    v-if="!isReloadingFrame"
                                    href="#"
                                    @click.prevent="reloadFrame"
                                >
                                    {{ $t('ongoing_airdrop.embeded.reload') }}
                                </a>
                                <span
                                    class="spinner-border spinner-border-sm"
                                    role="status"
                                />
                            </p>
                        </confirm-modal>
                    </div>
                </div>
            </div>
            <div
                v-else-if="!storageError"
                class="d-flex align-items-center justify-content-center py-2 airdrop-loading-container"
            >
                <div
                    class="spinner-border spinner-border-sm my-1"
                    role="status"
                />
            </div>
            <confirm-modal
                :visible="showConfirmTwitterMessageModal"
                :show-image="false"
                @confirm="claimAction(actionsToServer.twitterMessage)"
                @cancel="showConfirmTwitterMessageModal = false"
                @close="showConfirmTwitterMessageModal = false"
            >
                <p>
                    {{ $t('ongoing_airdrop.socialmedia_message.confirm', {socialMedia: 'twitter'}) }}
                </p>
                <p>
                    "{{ actionMessage }}"
                </p>
                <template v-slot:confirm>{{ $t('ongoing_airdrop.accept') }}</template>
            </confirm-modal>
            <confirm-modal
                :visible="showConfirmLinkedinMessageModal"
                :show-image="false"
                @confirm="claimAction(actionsToServer.linkedinMessage)"
                @cancel="showConfirmLinkedinMessageModal = false"
                @close="showConfirmLinkedinMessageModal = false"
            >
                <p>
                    {{ $t('ongoing_airdrop.socialmedia_message.confirm', {socialMedia: 'linkedin'}) }}
                </p>
                <p>
                    "{{ actionMessage }}"
                </p>
                <template v-slot:confirm>{{ $t('ongoing_airdrop.accept') }}</template>
            </confirm-modal>
            <add-phone-alert-modal
                :visible="addPhoneModalVisible"
                :message="addPhoneModalMessage"
                :embeded="embeded"
                @close="addPhoneModalVisible = false"
                @phone-verified="onPhoneVerified"
            />
            <modal
                :visible="loginShowModal"
                :embeded="embeded"
                @close="loginShowModal = false"
            >
                <div slot="header">
                    {{ $t('ongoing_airdrop.claim') }}
                </div>
                <div slot="body">
                    <p>{{ $t('ongoing_airdrop.claim.login_to_complete') }}</p>
                    <login-signup-switcher
                        :embeded="embeded"
                        :login-recaptcha-sitekey="loginRecaptchaSitekey"
                        :reg-recaptcha-sitekey="regRecaptchaSitekey"
                        :is-airdrop-referral="isAirdropReferral"
                    />
                </div>
            </modal>
        </div>
    </div>
</template>

<script>
import {VBTooltip} from 'bootstrap-vue';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faCircle, faGlobe, faExclamationCircle} from '@fortawesome/free-solid-svg-icons';
import {faXTwitter, faFacebookF, faLinkedinIn, faYoutube} from '@fortawesome/free-brands-svg-icons';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import LoginSignupSwitcher from '../../../components/LoginSignupSwitcher';
import moment from 'moment';
import Decimal from 'decimal.js';
import ConfirmModal from '../../modal/ConfirmModal';
import Modal from '../../modal/Modal';
import {
    AddPhoneAlertMixin,
    FiltersMixin,
    NotificationMixin,
    TwitterMixin,
    YoutubeMixin,
} from '../../../mixins';
import {
    TOK,
    HTTP_BAD_REQUEST,
    HTTP_NOT_FOUND,
    HTTP_ACCESS_DENIED,
    HTTP_UNAUTHORIZED,
    AIRDROP_CLAIM_ERROR,
    EXTERNAL_URL_TRUNCATE_LENGTH,
} from '../../../utils/constants';
import {toMoney, openPopup, openNewTab, generateCoinAvatarHtml} from '../../../utils';
import {required, url} from 'vuelidate/lib/validators';
import CopyLink from '../../CopyLink';
import AddPhoneAlertModal from '../../modal/AddPhoneAlertModal';
import {mapMutations} from 'vuex';
import CoinAvatar from '../../CoinAvatar';
import TruncateFilterMixin from '../../../mixins/filters/truncate';

library.add(
    faCircleNotch,
    faCircle,
    faGlobe,
    faExclamationCircle,
    faXTwitter,
    faFacebookF,
    faLinkedinIn,
    faYoutube,
    faCopy
);

export default {
    name: 'TokenOngoingAirdropCampaign',
    mixins: [
        NotificationMixin,
        FiltersMixin,
        TwitterMixin,
        AddPhoneAlertMixin,
        YoutubeMixin,
        TruncateFilterMixin,
    ],
    components: {
        ConfirmModal,
        LoginSignupSwitcher,
        Modal,
        CopyLink,
        AddPhoneAlertModal,
        FontAwesomeIcon,
        FontAwesomeLayers,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        loggedIn: Boolean,
        isOwner: Boolean,
        viewOnly: Boolean,
        tokenName: {
            type: String,
            default: '',
        },
        tokenAvatar: String,
        userAlreadyClaimed: Boolean,
        youtubeClientId: String,
        linkedinAppId: String,
        currentLocale: String,
        showAirdropModal: Boolean,
        profileNickname: String,
        loginRecaptchaSitekey: String,
        regRecaptchaSitekey: String,
        airdropCampaignProp: {
            type: Object,
            default: () => null,
        },
        referralCodeProp: {
            type: String,
            default: '',
        },
        embeded: {
            type: Boolean,
            default: false,
        },
        isAirdropReferral: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            showModal: false,
            loginShowModal: false,
            airdropCampaign: this.airdropCampaignProp,
            loaded: false,
            btnDisabled: false,
            alreadyClaimed: this.userAlreadyClaimed,
            claim: false,
            timeElapsed: false,
            showDuration: true,
            postLinkUrl: '',
            showConfirmTwitterMessageModal: false,
            showConfirmLinkedinMessageModal: false,
            checkingBlackListedDomain: true,
            blackListedDomain: false,
            checkDomainTimeout: null,
            referralCode: this.referralCodeProp,
            addPhoneModalMessageType: 'airdrop',
            storageError: false,
            isLoginTabOpen: false,
            isReloadingFrame: false,
            showLeaveSiteModal: false,
        };
    },
    mounted: function() {
        this.$emit('mounted');
        if (this.storageError) {
            return;
        }

        if (null !== this.currentLocale) {
            moment.locale(this.currentLocale);
        }
        this.showModal = this.showAirdropModal;
    },
    computed: {
        translationsContext() {
            return {
                participants: this.airdropCampaign.participants,
                airdropReward: this.airdropReward,
                actualParticipants: this.actualParticipants,
                endsDate: this.endsDate,
                endsTime: this.endsTime,
                endsDays: this.endsDays,
                tokenName: this.truncateFunc(this.tokenName, 30),
                tokenAvatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
                tasksAmount: this.actionsLength,
                halfReward: this.halfReward,
            };
        },
        actionsToServer() {
            return {
                twitterMessage: {
                    route: 'claim_share_twitter',
                    action: this.airdropCampaign.actions.twitterMessage,
                    data: null,
                    responseHandler: {
                        error: this.twitterErrorHandler,
                    },
                },
                twitterRetweet: {
                    route: 'claim_retweet',
                    action: this.airdropCampaign.actions.twitterRetweet,
                    data: null,
                    responseHandler: {
                        error: this.twitterErrorHandler,
                    },
                },
                facebookMessage: {
                    route: 'claim_fb_message_post',
                    action: this.airdropCampaign.actions.facebookMessage,
                    data: this.airdropCampaign.actionsData.facebookMessage,
                },
                facebookPage: {
                    route: 'claim_page_visit',
                    action: this.airdropCampaign.actions.facebookPage,
                    data: this.airdropCampaign.actionsData.facebookPage,
                },
                facebookPost: {
                    route: 'claim_page_visit',
                    action: this.airdropCampaign.actions.facebookPost,
                    data: this.airdropCampaign.actionsData.facebookPost,
                },
                linkedinMessage: {
                    route: 'claim_share_linkedin',
                    action: this.airdropCampaign.actions.linkedinMessage,
                    data: null,
                    responseHandler: {
                        error: this.linkedinErrorHandler,
                    },
                },
                youtubeSubscribe: {
                    route: 'claim_subscribe_youtube',
                    action: this.airdropCampaign.actions.youtubeSubscribe,
                    data: this.airdropCampaign.actionsData.youtubeSubscribe,
                },
                postLink: {
                    route: 'claim_post_link',
                    action: this.airdropCampaign.actions.postLink,
                    data: this.postLinkUrl,
                },
                visitExternalUrl: {
                    route: 'claim_page_visit',
                    action: this.airdropCampaign.actions.visitExternalUrl,
                    data: this.airdropCampaign.actionsData.visitExternalUrl,
                },
            };
        },
        externalUrlTooltip() {
            return {
                title: this.airdropCampaign.actionsData.visitExternalUrl,
                customClass: 'word-break w-75',
                id: 'visit-external-url',
                offset: '15',
            };
        },
        airdropEndedText() {
            return this.$t('ongoing_airdrop.ended_embeded', {
                mintmeUrl: this.$routing.generate('homepage'),
                extraAttributes: this.embeded ? 'target="_blank"' : '',
            });
        },
        actionsLength() {
            return Object.keys((this.airdropCampaign || {}).actions || {}).length;
        },
        actualParticipants: function() {
            return Math.ceil(this.airdropCampaign.actualParticipants || 0);
        },
        airdropReward: function() {
            if (this.loaded) {
                return toMoney(this.airdropCampaign.reward, TOK.subunit);
            }

            return 0;
        },
        halfReward: function() {
            if (this.loaded) {
                return toMoney(Decimal.div(this.airdropCampaign.reward, 2), TOK.subunit);
            }

            return 0;
        },
        showEndDate: function() {
            return null !== this.airdropCampaign.endDate && '' !== this.airdropCampaign.endDate;
        },
        endsDate: function() {
            return moment(this.airdropCampaign.endDate).format('Do MMMM YYYY');
        },
        endsTime: function() {
            return moment(this.airdropCampaign.endDate).format('HH:mm');
        },
        endsDays: function() {
            return parseInt(this.duration.asDays());
        },
        endsDateTime: function() {
            return moment(this.airdropCampaign.endDate).format('D MMMM YYYY HH:mm:ss');
        },
        duration: {
            get: function() {
                const now = moment();
                const format = 'D MMMM YYYY HH:mm:ss';
                return 0 >= moment
                    .duration(moment(this.endsDateTime, format)
                        .diff(moment(now, format)))
                    .asMilliseconds()
                    ? moment.duration(0)
                    : moment.duration(moment(this.endsDateTime, format).diff(moment(now, format)));
            },
            set: function(newDuration) {
                return newDuration;
            },
        },
        confirmButtonText: function() {
            let button = '';

            if (this.isOwner || this.timeElapsed) {
                button = 'OK';
            }

            return button;
        },
        confirmModalMessage: function() {
            if (this.isOwner) {
                return this.$t('ongoing_airdrop.confirm_message.cant_participate');
            }

            if (this.timeElapsed) {
                return this.$t('ongoing_airdrop.ended');
            }

            if (0 < this.actionsLength) {
                return this.$t('ongoing_airdrop.actions_message', this.translationsContext);
            }

            return this.$t('ongoing_airdrop.confirm_message', this.translationsContext);
        },
        actionMessage() {
            return this.$t('ongoing_airdrop.actions.message', {
                tokenName: this.tokenName,
                tokenUrl: this.referralLink,
            });
        },
        tokenUrl() {
            return this.$routing.generate('token_show_intro', {name: this.tokenName}, true);
        },
        twitterMessageLink() {
            return 'https://twitter.com/intent/tweet?text=' + decodeURI(this.actionMessage);
        },
        twitterRetweetLink() {
            return 'https://twitter.com/intent/retweet?tweet_id=' + this.airdropCampaign.actionsData.twitterRetweet;
        },
        linkedinAuthLink() {
            return 'https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id='
                + this.linkedinAppId
                + '&redirect_uri='
                + this.$routing.generate('linkedin_callback', null, true)
                + '&scope=r_liteprofile%20r_emailaddress%20w_member_social';
        },
        youtubeLink() {
            return 'https://www.youtube.com/channel/' + this.airdropCampaign.actionsData.youtubeSubscribe;
        },
        actionsCompleted() {
            return 0 < this.actionsLength
                ? Object.keys(this.airdropCampaign.actions).every((key) => this.airdropCampaign.actions[key].done)
                : true;
        },
        referralLink() {
            return this.$routing.generate(
                'airdrop_referral',
                {
                    tokenName: this.tokenName,
                    hash: this.referralCode,
                },
                true
            );
        },
        modalTokenUrl() {
            return this.$routing.generate('token_show_intro', {name: this.tokenName, modal: 'airdrop'}, true);
        },
        postLinkUrlDisabled() {
            return this.checkingBlackListedDomain
                || this.blackListedDomain
                || this.$v.postLinkUrl.$invalid;
        },
        domainErrorMessage() {
            if (this.$v.postLinkUrl.required && !this.$v.postLinkUrl.startsWith) {
                return this.$t('api.airdrop.url_start_with');
            }

            if (!this.$v.postLinkUrl.url) {
                return this.$t('api.airdrop.invalid_url');
            }

            if (this.blackListedDomain) {
                return this.$t('api.airdrop.forbidden_domain', {
                    domain: (new URL(this.postLinkUrl)).hostname,
                });
            }

            return '';
        },
        buttonDisabled() {
            return !this.isOwner
                && !this.userAlreadyClaimed
                && !this.actionsCompleted;
        },
        showCancelButton() {
            return !this.isOwner
                && !this.alreadyClaimed
                && !this.timeElapsed
                && !this.embeded;
        },
        showConfirmButton() {
            return !!this.airdropCampaign.status
                && !this.alreadyClaimed
                && !this.claim
                && !this.isLoginTabOpen;
        },
        airdropEndsMessage() {
            return this.endsDays
                ? this.$t('ongoing_airdrop.ends_sm.1', this.translationsContext)
                : this.$t('ongoing_airdrop.ends_sm.2');
        },
        durationValues() {
            const years = this.duration.years() ? this.duration.years() + this.$t('year_acronym') + ' ' : '';
            const months = this.duration.months() ? this.duration.months() + this.$t('month_acronym') + ' ' : '';
            const days = this.duration.days() ? this.duration.days() + this.$t('day_acronym') + ' ' : '';
            const hours = this.duration.hours() ? this.duration.hours() + this.$t('hour_acronym') + ' ' : '';
            const minutes = this.duration.minutes() ? this.duration.minutes() + this.$t('minute_acronym') + ' ' : '';
            const seconds = this.duration.seconds() ? this.duration.seconds() + this.$t('second_acronym') : '';
            const durationValues = '(' + years + months + days + hours + minutes + seconds + ')';

            return durationValues.trim();
        },
        isLongUrl() {
            return this.airdropCampaign.actionsData.visitExternalUrl?.length > EXTERNAL_URL_TRUNCATE_LENGTH;
        },
        externalUrlTruncated() {
            return this.truncateFunc(this.airdropCampaign.actionsData?.visitExternalUrl, EXTERNAL_URL_TRUNCATE_LENGTH);
        },
    },
    watch: {
        postLinkUrl: function() {
            clearTimeout(this.checkDomainTimeout);
            this.checkingBlackListedDomain = true;
            this.blackListedDomain = false;
            this.checkDomainTimeout = setTimeout(this.checkBlacklistedDomain, 500);
        },
    },
    methods: {
        ...mapMutations('tradeBalance', ['setQuoteFullBalance']),
        openLeaveSiteModal: function(event) {
            this.airdropCampaign.actionsData.visitExternalUrl = event.currentTarget.getAttribute('href');
            this.showLeaveSiteModal = true;
        },
        leaveSite: function() {
            window.open(this.airdropCampaign.actionsData.visitExternalUrl, '_blank');
            this.claimAction(this.actionsToServer.visitExternalUrl);
            this.showLeaveSiteModal = false;
        },
        reloadFrame: function() {
            if (!this.isReloadingFrame) {
                this.isReloadingFrame = true;
                location.reload();
            }
        },
        showModalOnClick: function() {
            this.showModal = !this.isOwner;
        },
        updateAirdropActionFromSession: function() {
            this.$axios.retry.get(this.$routing.generate('get_airdrop_completed_actions', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    if (!result.data) {
                        return;
                    }

                    const actions = this.airdropCampaign.actions;

                    for (const action in actions) {
                        if (actions.hasOwnProperty(action)) {
                            actions[action].done = result.data.includes(actions[action].id);
                        }
                    }
                })
                .catch((err) => {
                    this.handleInvalidTokenError(err, this.$t('toasted.error.try_reload'));
                    this.$logger.error('Can not load airdrop campaign.', err);
                });
        },
        showCountdown: function() {
            this.duration = moment.duration(this.duration - 1000, 'milliseconds');
            if (0 >= this.duration.asMilliseconds()) {
                this.timeElapsed = true;
                this.showDuration = false;
            }
        },
        countdownInterval: function() {
            return setInterval(() => {
                this.showCountdown;
            }, 1000);
        },
        getAirdropCampaign: function() {
            this.$axios.retry.get(this.$routing.generate('get_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    this.airdropCampaign = result.data.airdrop;
                    this.referralCode = result.data.referral_code;
                    this.loaded = true;
                    this.showCountdown();
                    this.countdownInterval();
                    if (!this.loggedIn) {
                        this.updateAirdropActionFromSession();
                    }
                })
                .catch((err) => {
                    if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }
                    this.$logger.error('Can not load airdrop campaign.', err);
                });
        },
        closeModal: function() {
            if (!this.embeded) {
                this.showModal = false;
            }
        },
        modalOnConfirm: function() {
            if (!this.loggedIn) {
                this.closeModal();

                if (this.embeded) {
                    if (!this.isLoginPageOpen) {
                        openNewTab(this.$routing.generate('login'));

                        this.isLoginTabOpen = true;
                    }

                    return;
                }

                this.loginShowModal = true;
                return;
            }

            if (this.isOwner || this.timeElapsed || !this.actionsCompleted) {
                return;
            }

            this.claim = true;

            return this.$axios.single.post(this.$routing.generate('claim_airdrop_campaign', {
                tokenName: this.tokenName,
                id: this.airdropCampaign.id,
            }))
                .then(({data}) => {
                    if (
                        data.hasOwnProperty('error') &&
                        data.hasOwnProperty('type')
                    ) {
                        this.errorType = data.type;
                        this.addPhoneModalVisible = true;
                        return;
                    }
                    if (this.airdropCampaign.actualParticipants < this.airdropCampaign.participants) {
                        this.airdropCampaign.actualParticipants++;
                    }

                    if (data.balance) {
                        this.setQuoteFullBalance(data.balance);
                    }

                    this.alreadyClaimed = true;
                })
                .catch((err) => {
                    const status = err.response.status;
                    const msg = err.response.data.message;

                    if (HTTP_BAD_REQUEST === status && msg) {
                        this.notifyError(msg);
                        setTimeout(()=> {
                            location.reload();
                        }, 1000);
                    } else if (HTTP_NOT_FOUND === status && msg) {
                        location.href = this.$routing.generate('trading');
                    } else if ((HTTP_UNAUTHORIZED === status || HTTP_ACCESS_DENIED === status) && msg) {
                        this.notifyError(msg);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }
                    this.$logger.error('Can not claim airdrop campaign.', err);
                })
                .then(() => this.claim = false);
        },
        claimAction(actionType) {
            if (actionType.action.done) {
                return;
            }

            return this.$axios.single.post(this.$routing.generate(actionType.route, {
                tokenName: this.tokenName,
                id: actionType.action.id,
            }), {data: actionType.data})
                .then((res) => {
                    actionType.action.done = true;
                })
                .catch((err) => {
                    if (actionType.responseHandler && actionType.responseHandler.error) {
                        return actionType.responseHandler.error(err);
                    }

                    this.handleInvalidTokenError(err, this.$t('ongoing_airdrop.actions.claim_error'));
                    this.$logger.error('error', 'Error claiming action', err);
                });
        },
        claimTwitterMessage() {
            if (this.airdropCampaign.actions.twitterMessage.done) {
                return openPopup(this.twitterMessageLink);
            }

            if (!this.isSignedInWithTwitter) {
                return this.signInWithTwitter().then(this.claimTwitterMessage, (err) => {
                    this.handleInvalidTokenError(err, this.$t('toasted.error.try_reload'));
                });
            }

            this.showConfirmTwitterMessageModal = true;
        },
        twitterErrorHandler(err) {
            if (err.response.data.message === AIRDROP_CLAIM_ERROR.TWITTER_INVALID_TOKEN) {
                return this.signInWithTwitter().then(
                    this.claimTwitterMessage,
                    (err) => {
                        this.notifyError('airdrop.actions.twitter_invalid_token');
                    }
                );
            }
        },
        linkedinErrorHandler(err) {
            this.handleInvalidTokenError(err, this.$t('ongoing_airdrop.actions.linkedin_share_unprocessed'));
        },
        claimTwitterRetweet() {
            if (this.airdropCampaign.actions.twitterRetweet.done) {
                return openPopup(this.twitterRetweetLink);
            }

            if (!this.isSignedInWithTwitter) {
                return this.signInWithTwitter()
                    .then(this.claimTwitterRetweet, (err) => this.notifyError(err.message));
            }

            this.claimAction(this.actionsToServer.twitterRetweet);
        },
        openFacebookMessage() {
            FB.ui({
                method: 'share',
                href: this.tokenUrl,
            }, (res) => {
                if (!res.error_message) {
                    this.claimAction(this.actionsToServer.facebookMessage);
                }
            });
        },
        claimLinkedin() {
            if (this.airdropCampaign.actions.linkedinMessage.done) {
                return;
            }
            openPopup(this.linkedinAuthLink)
                .then((res) => {
                    this.showConfirmLinkedinMessageModal = true;
                });
        },
        claimYoutube() {
            if (this.airdropCampaign.actions.youtubeSubscribe.done) {
                return openPopup(this.youtubeLink);
            }
            if (!this.isAuthorizedYoutube) {
                return this.authorizeYoutube()
                    .then(this.claimYoutube, (err) => this.notifyError(err.message));
            }
            this.claimAction(this.actionsToServer.youtubeSubscribe);
        },
        subscribeYoutube() {
            openPopup(this.youtubeLink);
        },
        checkBlacklistedDomain: function() {
            if (this.$v.postLinkUrl.$invalid) {
                this.blackListedDomain = false;
                this.checkingBlackListedDomain = false;
                return;
            }

            this.$axios.retry.get(
                this.$routing.generate('airdrop_domain_blacklist_check', {domain: this.postLinkUrl})
            ).then(({data}) => {
                this.blackListedDomain = data.blacklisted;
                this.checkingBlackListedDomain = false;
            }).catch((err) => {
                this.handleInvalidTokenError(err);
                this.$logger.error('airdrop_domain_blacklist_check', err);
            });
        },
        checkStorageError: function() {
            try {
                window.localStorage;
            } catch (e) {
                this.storageError = true;
            }
        },
        onPhoneVerified() {
            this.addPhoneModalVisible = false;
            this.showModalOnClick();
        },
        handleInvalidTokenError(err, message = null) {
            if (HTTP_UNAUTHORIZED === err.response.status) {
                this.notifyError(this.$t('toasted.error.enable_third_party_cookies'));
            } else if (null !== message) {
                this.notifyError(message);
            }
        },
    },
    updated() {
        setTimeout(()=>{
            if (this.$refs.externalLink) {
                if (this.$refs.externalLink.offsetWidth >= (this.$refs.externalLink.scrollWidth-7)) {
                    this.$root.$emit('bv::disable::tooltip', 'visit-external-url');
                }
            }
        }, 500);
    },
    created() {
        this.checkStorageError();

        if (this.storageError) {
            return;
        }

        if (!this.airdropCampaign) {
            this.getAirdropCampaign();
        } else {
            if (!this.loggedIn) {
                this.updateAirdropActionFromSession();
            }

            this.loaded = true;
        }
    },
    validations() {
        return {
            postLinkUrl: {
                required: (val) => required(val.trim()),
                startsWith: (val) => /^https?:\/\//.test(val),
                url,
            },
        };
    },
};
</script>
