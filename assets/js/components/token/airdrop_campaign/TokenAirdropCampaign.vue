<template>
    <div>
        <div
            v-if="loading"
            class="text-center"
        >
            <font-awesome-icon
                icon="circle-notch"
                spin
                class="loading-spinner"
                fixed-width
            />
        </div>
        <div v-else-if="hasAirdropCampaign">
            <div>
                <p>{{ $t('airdrop.embed_title') }}</p>
                <copy-link
                    class="copy-container d-block p-2"
                    :content-to-copy="embedCode"
                >{{ embedCode }}</copy-link>
                <div class="d-flex align-items-center mt-3">
                    <m-button
                        type="primary"
                        :disabled="!balanceLoaded || serviceUnavailable"
                        @click="showModal = true"
                    >
                        {{ $t('airdrop.end') }}
                    </m-button>
                    <font-awesome-icon
                        v-if="!balanceLoaded && !serviceUnavailable"
                        icon="circle-notch"
                        spin
                        class="loading-spinner ml-2"
                        fixed-width
                    />
                    <span v-else-if="serviceUnavailable" class="text-danger ml-2">
                        {{ this.$t('toasted.error.service_unavailable_short') }}
                    </span>
                </div>
                <confirm-modal
                    :visible="showModal"
                    :show-image="false"
                    no-title
                    type="warning"
                    @confirm="deleteAirdropCampaign"
                    @close="showModal = false">
                    <p class="text-white modal-title text-break pt-2 pb-4">
                        {{ $t('airdrop.end_campaign.confirm') }}
                    </p>
                    <template v-slot:confirm>{{ $t('confirm_modal.delete') }}</template>
                </confirm-modal>
            </div>
        </div>
        <div v-else class="airdrop-campaign">
            <div>
                {{ $t('airdrop.tooltip.information_adding_airdrops') }}
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.twitterMessage"
                        type="checkbox"
                        id="twitter-message"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="twitter-message"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'x-twitter']"
                                size="lg"
                                transform="right-3 down-1 shrink-1"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.twitter_message') }}
                        </span>
                    </label>
                </label>
            </div>
            <!-- Hide the retweet option temporarily -->
            <!-- <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.twitterRetweet"
                        type="checkbox"
                        id="twitter-retweet"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="twitter-retweet"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'x-twitter']"
                                size="lg"
                                transform="right-3 down-1 shrink-1"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.twitter_retweet') }}
                        </span>
                    </label>
                </label>
            </div>
            <div class="ml-4 pl-2" v-if="actions.twitterRetweet">
                <m-input
                    :label="tweetPlaceHolderUrl"
                    v-model="actionsData.twitterRetweet"
                    :invalid="$v.actionsData.twitterRetweet.$anyError"
                >
                    <template v-slot:errors>
                        <div v-if="actionsData.twitterRetweet.length > 0 && !$v.actionsData.twitterRetweet.validUrl">
                            {{ $t('airdrop_backend.invalid_twitter_url') }}
                        </div>
                        <div v-if="!$v.actionsData.twitterRetweet.required">
                            {{ $t('form.required') }}
                        </div>
                    </template>
                </m-input>
            </div> -->
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.facebookMessage"
                        type="checkbox"
                        id="facebook-message"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="facebook-message"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'facebook-f']"
                                size="lg"
                                transform="right-3 down-1 shrink-1"
                                class="facebook-icon"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.facebook_message') }}
                        </span>
                    </label>
                </label>
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.facebookPage"
                        type="checkbox"
                        id="facebook-page"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="facebook-page"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'facebook-f']"
                                size="lg"
                                transform="right-3 down-1 shrink-1"
                                class="facebook-icon"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.facebook_page') }}
                        </span>
                    </label>
                </label>
            </div>
            <token-facebook-address
                class="airdrop-campaign__token-facebook-address ml-3"
                :address="currentFacebook"
                :tokenName="tokenName"
                :isAirdrop="true"
                @saveFacebook="saveFacebook"
            />
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.facebookPost"
                        type="checkbox"
                        id="facebook-post"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="facebook-post"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'facebook-f']"
                                size="lg"
                                transform="right-3 down-1 shrink-1"
                                class="facebook-icon"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.facebook_post') }}
                        </span>
                    </label>
                </label>
            </div>
            <div class="ml-4 pl-2" v-if="actions.facebookPost">
                <m-input
                    :label="postPlaceHolderUrl"
                    v-model="actionsData.facebookPost"
                    :invalid="$v.actionsData.facebookPost.$anyError"
                >
                    <template v-slot:errors>
                        <div v-if="isFacebookUrlInvalid">
                            <span>
                                {{ $t('airdrop_backend.invalid_facebook_url') }}
                            </span>
                        </div>
                        <div v-if="!$v.actionsData.facebookPost.required">
                            {{ $t('form.required') }}
                        </div>
                    </template>
                </m-input>
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.linkedinMessage"
                        type="checkbox"
                        id="linkedin-message"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="linkedin-message"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'linkedin-in']"
                                size="lg"
                                transform="right-3 down-1 shrink-1"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.linkedin_message') }}
                        </span>
                    </label>
                </label>
            </div>
            <!-- Hide youtube subscribe option temporarily -->
            <!-- <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.youtubeSubscribe"
                        type="checkbox"
                        id="youtube-subscribe"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="youtube-subscribe"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                :icon="['fab', 'youtube']"
                                size="lg"
                                transform="right-2 down-1 shrink-1"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.youtube_subscribe') }}
                        </span>
                    </label>
                </label>
            </div>
            <token-youtube-address
                class="airdrop-campaign__token-youtube-address ml-3"
                :channel-id="currentYoutube"
                :client-id="youtubeClientId"
                :editable="true"
                :tokenName="tokenName"
                :isAirdrop="true"
                @saveYoutube="saveYoutube"
            /> -->
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.postLink"
                        type="checkbox"
                        id="post-link"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="post-link"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                icon="globe"
                                size="lg"
                                transform="right-3 shrink-1"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.post_link') }}
                        </span>
                    </label>
                </label>
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.visitExternalUrl"
                        type="checkbox"
                        id="visit-external-url"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="visit-external-url"
                    >
                        <font-awesome-layers>
                            <font-awesome-icon
                                icon="globe"
                                size="lg"
                                transform="right-3 shrink-1"
                            />
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.visit_external_url') }}
                        </span>
                    </label>
                </label>
            </div>
            <div class="ml-4 pl-2" v-if="actions.visitExternalUrl">
                <m-input
                    :label="visitExternalUrlPlaceHolderUrl"
                    v-model="actionsData.visitExternalUrl"
                    :invalid="$v.actionsData.visitExternalUrl.$anyError"
                >
                    <template v-slot:errors>
                        <div v-if="actionsData.visitExternalUrl.length > 0
                            && !$v.actionsData.visitExternalUrl.validUrl"
                        >
                            {{ $t('airdrop.actions.external_link_invalid') }}
                        </div>
                        <div v-if="!$v.actionsData.visitExternalUrl.required">
                            {{ $t('form.required') }}
                        </div>
                        <div v-if="!$v.actionsData.visitExternalUrl.maxLength">
                            {{ errorUrlTooLong }}
                        </div>
                    </template>
                </m-input>
            </div>
            <div class="col-12 mt-4 px-0">
                <m-input
                    :label="$t('airdrop.amount_tokens')"
                    v-model="tokensAmount"
                    :disabled="hasAirdropCampaign"
                    @keyup="checkInputDot"
                    @keypress="checkInput(2)"
                    @paste="checkInput(2)"
                    autocomplete="off"
                    :invalid="balanceLoaded && (insufficientBalance || !isAmountValid)"
                >
                    <template v-slot:hint>
                        <span v-b-tooltip="tooltipConfig">
                            <span v-html="$t('airdrop.min_amount', translationsContext)" />
                            {{ tokenBalance | toMoney(precision, false) | formatMoney }}.
                        </span>
                    </template>
                    <template v-slot:errors>
                        <div v-if="balanceLoaded && insufficientBalance">
                            {{ $t('airdrop.insufficient_funds') }}
                        </div>
                        <div v-else-if="balanceLoaded && !isAmountValid">
                            <span v-b-tooltip="tooltipConfig">
                                <span v-html="$t('airdrop.min_amount', translationsContext)" />
                                {{ tokenBalance | toMoney(precision, false) | formatMoney }}.
                            </span>
                        </div>
                    </template>
                </m-input>
            </div>
            <div class="col-12 px-0">
                <m-input
                    :label="$t('airdrop.amount_participants')"
                    v-model="participantsAmount"
                    :disabled="hasAirdropCampaign"
                    @keyup="checkInputDot"
                    @keypress="checkInput(0)"
                    @paste="checkInput(0)"
                    autocomplete="off"
                    :invalid="!isParticipantsAmountValid"
                >
                    <template v-slot:hint>
                        <span v-html="$t('airdrop.min_amount_participants', translationsContext)" />
                    </template>
                    <template v-slot:errors>
                        <div v-if="!isParticipantsAmountValid">
                            <span v-html="$t('airdrop.min_amount_participants', translationsContext)" />
                        </div>
                    </template>
                </m-input>
            </div>
            <div class="col-12 pb-3 px-0">
                <label class="text-left">
                    {{ $t('airdrop.reward') }}
                </label>
                <span class="text-nowrap">
                    {{ reward | toMoney(tokSubunit) | formatMoney }}
                    <span v-b-tooltip="tooltipConfig">
                        <coin-avatar
                            :image="tokenAvatar"
                            :is-user-token="true"
                        />
                        {{ truncatedTokenName }}
                    </span>
                </span>
            </div>
            <div class="col-12 px-0 clearfix">
                <div class="w-100 mb-3 text-danger">
                    {{ errorMessage }}
                </div>
            </div>
            <div v-if="!hasAirdropCampaign" class="col-12 pb-3 px-0">
                <label class="custom-control custom-checkbox pb-0 d-flex align-items-center">
                    <input
                        v-b-toggle.collapse-end-date
                        v-model="showEndDate"
                        type="checkbox"
                        id="showEndDate"
                        ref="end-date-checkbox"
                        class="custom-control-input custom-control-input-dark"
                        tabindex="0"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="showEndDate"
                    >
                        {{ $t('airdrop.add_end_date') }}
                        <guide>
                            <template slot="body">
                                {{ $t('airdrop.tooltip.add_end_date') }}
                            </template>
                        </guide>
                    </label>
                </label>
            </div>
            <b-collapse id="collapse-end-date">
                <div class="form-control-container">
                    <div class="form-control-field">
                        <div class="outline">
                            <div class="left-outline"></div>
                                <div class="label-outline">
                                    <label for="endDate">
                                        {{ $t('airdrop.end_date') }}
                                    </label>
                                </div>
                            <div class="right-outline"></div>
                        </div>
                        <date-picker
                            id="endDate"
                            class="form-control"
                            v-model="endDate"
                            :disabled="!showEndDate || hasAirdropCampaign"
                            :config="options"
                        />
                    </div>
                </div>
            </b-collapse>
            <div class="col-12 px-0 mt-3 clearfix">
                <button
                    class="btn btn-primary"
                    :disabled="btnDisabled || allOptionsUnChecked"
                    @click="createAirdropCampaign"
                >
                    {{ $t('save') }}
                </button>
                <font-awesome-icon
                    v-if="!balanceLoaded && !serviceUnavailable"
                    icon="circle-notch"
                    spin
                    class="loading-spinner ml-2"
                    fixed-width
                />
                <span v-else-if="serviceUnavailable" class="text-danger ml-2">
                    {{ this.$t('toasted.error.service_unavailable_short') }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import datePicker from '../../DatePicker';
import {BCollapse, VBToggle, VBTooltip} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faCircle, faGlobe} from '@fortawesome/free-solid-svg-icons';
import {faXTwitter, faFacebookF, faLinkedinIn, faYoutube} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import ConfirmModal from '../../modal/ConfirmModal';
import {
    CheckInputMixin,
    NotificationMixin,
    MoneyFilterMixin,
    FiltersMixin,
} from '../../../mixins';
import {
    TOK,
    HTTP_BAD_REQUEST,
    HTTP_NOT_FOUND,
    AIRDROP_CREATED,
    AIRDROP_DELETED,
    tweetLink,
    facebookPostLink,
    lengthUrl,
    GENERAL,
    HTTP_ACCESS_DENIED,
} from '../../../utils/constants';
import {isValidUrl, generateCoinAvatarHtml} from '../../../utils';
import TokenFacebookAddress from '../facebook/TokenFacebookAddress';
// import TokenYoutubeAddress from '../youtube/TokenYoutubeAddress'; // disable youtube subscribe temporarily
import {requiredIf, maxLength} from 'vuelidate/lib/validators';
import CopyLink from '../../CopyLink';
import {MButton, MInput} from '../../UI';
import Guide from '../../Guide';
import CoinAvatar from '../../CoinAvatar';

library.add(
    faCircleNotch,
    faCircle,
    faGlobe,
    faXTwitter,
    faFacebookF,
    faLinkedinIn,
    faYoutube
);

export default {
    name: 'TokenAirdropCampaign',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
        MoneyFilterMixin,
        FiltersMixin,
    ],
    components: {
        Guide,
        CopyLink,
        BCollapse,
        datePicker,
        ConfirmModal,
        FontAwesomeIcon,
        FontAwesomeLayers,
        TokenFacebookAddress,
        // TokenYoutubeAddress, // disable youtube subscribe temporarily
        MButton,
        MInput,
        CoinAvatar,
    },
    directives: {
        'b-toggle': VBToggle,
        'b-tooltip': VBTooltip,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        airdropParams: Object,
        facebookUrl: String,
        youtubeChannelId: String,
        youtubeClientId: String,
        currentLocale: String,
    },
    data() {
        return {
            tokSubunit: TOK.subunit,
            showModal: false,
            airdrop: null,
            airdropCampaignRemoved: false,
            loading: false,
            showEndDate: false,
            tokensAmount: 100,
            participantsAmount: 100,
            endDate: moment().add(1, 'hour').format(GENERAL.dateTimeFormatPicker),
            options: {
                format: GENERAL.dateTimeFormatPicker,
                useCurrent: false,
                minDate: moment().add(1, 'hour'),
                locale: this.currentLocale,
            },
            errorMessage: '',
            errorUrlTooLong: '',
            precision: TOK.subunit,
            actions: {
                twitterMessage: true,
                twitterRetweet: false, // disable retweets temporarily
                facebookMessage: true,
                facebookPage: true,
                facebookPost: true,
                linkedinMessage: true,
                youtubeSubscribe: false, // disable youtube subscribe temporarily
                postLink: true,
                visitExternalUrl: true,
            },
            actionsData: {
                twitterRetweet: '',
                facebookPost: '',
                visitExternalUrl: 'https://',
            },
            currentFacebook: this.facebookUrl || '',
            currentYoutube: this.youtubeChannelId || '',
        };
    },
    mounted: function() {
        this.loadAirdropCampaign();
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        balanceLoaded: function() {
            return null !== this.balances;
        },
        tokenBalance: function() {
            return this.balanceLoaded
                ? this.balances[this.tokenName]?.available ?? '0'
                : '0';
        },
        translationsContext: function() {
            return {
                tokenName: this.tokenName,
                minTokensAmount: this.minTokensAmount,
                minParticipantsAmount: this.minParticipantsAmount,
                maxParticipantsAmount: this.maxParticipantsAmount,
                avatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
                tokenName: this.truncatedTokenName,
            };
        },
        airdropCampaignId: function() {
            return this.airdrop ? this.airdrop.id : null;
        },
        embedCode: function() {
            const src = this.$routing.generate('airdrop_embeded', {
                name: this.tokenName,
                airdropId: this.airdropCampaignId,
            }, true);

            return `<iframe src="${src}" width="500px" height="500px" style="border: none;" scrolling="no"></iframe>`;
        },
        allOptionsUnChecked: function() {
            return Object.values(this.actions)
                .every((item) => false === item);
        },
        minTokensAmount: function() {
            return this.airdropParams.min_tokens_amount || 0;
        },
        minParticipantsAmount: function() {
            return this.airdropParams.min_participants_amount || 0;
        },
        maxParticipantsAmount: function() {
            return this.airdropParams.max_participants_amount || 0;
        },
        minTokenReward: function() {
            return this.airdropParams.min_token_reward || 0;
        },
        hasAirdropCampaign: function() {
            return 0 < parseInt(this.airdropCampaignId);
        },
        btnDisabled: function() {
            return !this.isRewardValid
                || !this.isAmountValid
                || !this.isParticipantsAmountValid
                || !this.isDateEndValid
                || this.insufficientBalance
                || !this.balanceLoaded
                || this.serviceUnavailable
                || this.$v.$invalid;
        },
        insufficientBalance: function() {
            if (this.balanceLoaded) {
                const balance = new Decimal(this.tokenBalance);

                const tokensAmount = new Decimal(this.tokensAmount || 0);

                return balance.lessThan(this.minTokensAmount)
                    || balance.lessThan(tokensAmount.add(this.reward.dividedBy(2)));
            }

            return false;
        },
        isAmountValid: function() {
            if (0 < this.tokensAmount) {
                const tokensAmount = new Decimal(this.tokensAmount);

                return tokensAmount.greaterThanOrEqualTo(this.minTokensAmount);
            }

            return false;
        },
        isParticipantsAmountValid: function() {
            return this.participantsAmount >= this.minParticipantsAmount
                && this.participantsAmount <= this.maxParticipantsAmount;
        },
        isDateEndValid: function() {
            return !this.showEndDate || this.isDateValid;
        },
        isDateValid: function() {
            const selectedDate = moment(this.endDate, this.options.format).toDate();
            return this.showEndDate && selectedDate.valueOf() > moment().valueOf();
        },
        isRewardValid: function() {
            return this.reward.greaterThanOrEqualTo(this.minTokenReward);
        },
        /**
         * @return {Decimal}
         */
        reward() {
            if (0 < this.tokensAmount && 0 < this.participantsAmount) {
                const amount = new Decimal(this.tokensAmount);
                const participants = new Decimal(this.participantsAmount);
                return amount.dividedBy(participants);
            }

            return new Decimal(0);
        },
        tweetPlaceHolderUrl() {
            return this.$t('airdrop.tweet_placeholder_url');
        },
        postPlaceHolderUrl() {
            return this.$t('airdrop.post_placeholder_url');
        },
        visitExternalUrlPlaceHolderUrl() {
            return this.$t('airdrop.visit_external_url_placeholder_url');
        },
        isUrlTooLong() {
            return !this.$v.actionsData.visitExternalUrl.maxLength;
        },
        isFacebookUrlInvalid() {
            return 0 < this.actionsData.facebookPost.length && !this.$v.actionsData.facebookPost.validUrl;
        },
        shouldTruncate: function() {
            return this.tokenName
                ? 10 < this.tokenName.length
                : false;
        },
        truncatedTokenName: function() {
            return this.shouldTruncate
                ? this.truncateFunc(this.tokenName, 10)
                : this.tokenName;
        },
        tooltipConfig: function() {
            return this.shouldTruncate
                ? {
                    title: this.tokenName,
                    customClass: 'tooltip-custom',
                    variant: 'light',
                }
                : null;
        },
    },
    methods: {
        loadAirdropCampaign: function() {
            this.loading = true;
            this.$axios.retry.get(this.$routing.generate('get_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    if (null !== result.data.airdrop) {
                        this.airdrop = result.data.airdrop;
                    }

                    if (!this.hasAirdropCampaign) {
                        this.setDefaultValues();
                    }

                    this.loading = false;
                })
                .catch((err) => {
                    this.$logger.error('Can not load airdrop campaign.', err);
                });
        },
        createAirdropCampaign: function() {
            if (this.btnDisabled || this.insufficientBalance || this.$v.$invalid) {
                return;
            }

            if (!this.isRewardValid) {
                this.errorMessage = this.$t('airdrop.error_message', {
                    minTokenReward: this.minTokenReward,
                    tokenName: this.tokenName,
                });

                return;
            }

            const data = {
                amount: new Decimal(this.tokensAmount).toString(),
                participants: new Decimal(this.participantsAmount).toString(),
                actions: this.actions,
                actionsData: this.actionsData,
            };

            if (this.isDateValid) {
                const selectedDate = moment(this.endDate, this.options.format).toDate();
                data.endDate = Math.round(selectedDate.getTime()/1000);
            }

            this.loading = true;
            return this.$axios.single.post(this.$routing.generate('create_airdrop_campaign', {
                tokenName: this.tokenName,
            }), data)
                .then((result) => {
                    this.airdrop = result.data;
                    this.loading = false;
                    this.notifySuccess(this.$t('airdrop.msg_created'));

                    if (this.airdropCampaignRemoved) {
                        this.airdropCampaignRemoved = false;
                    }

                    window.localStorage.removeItem(AIRDROP_CREATED);
                    window.localStorage.setItem(AIRDROP_CREATED, this.tokenName);
                    this.$emit('reload');
                })
                .catch((err) => {
                    if (HTTP_BAD_REQUEST === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);

                        setTimeout(()=> {
                            this.closeEditModal();
                            location.reload();
                        }, 700);
                    } else if (HTTP_NOT_FOUND === err.response.status && err.response.data.message) {
                        location.href = this.$routing.generate('token_create');
                    } else if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }

                    this.loading = false;
                    this.$logger.error('Can not create airdrop campaign.', err);
                });
        },
        deleteAirdropCampaign: function() {
            if (!this.hasAirdropCampaign) {
                return;
            }

            this.loading = true;
            return this.$axios.single.delete(this.$routing.generate('delete_airdrop_campaign', {
                id: this.airdropCampaignId,
            }))
                .then(() => {
                    this.airdrop = null;
                    this.notifySuccess(this.$t('airdrop.msg_removed'));
                    window.localStorage.removeItem(AIRDROP_DELETED);
                    window.localStorage.setItem(AIRDROP_DELETED, this.tokenName);
                    this.closeEditModal();
                    location.reload();
                })
                .catch((err) => {
                    if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }
                    this.$logger.error('Can not delete airdrop.', err);
                });
        },
        setDefaultValues: function(hideDate) {
            if (this.showEndDate && hideDate) {
                this.showEndDate = false;
                this.$refs['end-date-checkbox'].click();
            }

            this.tokensAmount = 100;
            this.participantsAmount = 100;
            this.endDate = moment().add(1, 'hour').toDate();
        },
        closeEditModal: function() {
            this.$emit('close');
        },
        saveFacebook: function(newFacebook) {
            this.currentFacebook = newFacebook;
            this.$emit('updated-facebook', newFacebook);
        },
        saveYoutube: function(newChannelId) {
            this.currentYoutube = newChannelId;
            this.$emit('updated-youtube', newChannelId);
        },
    },
    beforeDestroy() {
        if (!this.hasAirdropCampaign && this.airdropCampaignRemoved) {
            location.reload();
        }
    },
    watch: {
        isRewardValid: function(value) {
            const translationsContext = {
                minTokenReward: this.minTokenReward,
                tokenName: this.tokenName,
            };

            this.errorMessage = (!value && this.isAmountValid && this.isParticipantsAmountValid)
                ? this.$t('airdrop.error_message', translationsContext)
                : '';
        },
        allOptionsUnChecked: function(value) {
            this.errorMessage = value ? this.$t('airdrop.actions.error_message') : '';
        },
        isUrlTooLong: function() {
            this.errorUrlTooLong = this.isUrlTooLong
                ? this.$t('airdrop.actions.error_url_too_long', {maxCharacters: lengthUrl.max})
                : '';
        },
    },
    validations() {
        return {
            actionsData: {
                twitterRetweet: {
                    required: (val) => requiredIf(() => this.actions.twitterRetweet)(val.trim()),
                    validUrl: (val) => !this.actions.twitterRetweet || tweetLink(val.trim()),
                },
                facebookPost: {
                    required: (val) => requiredIf(() => this.actions.facebookPost)(val.trim()),
                    validUrl: (val) => !this.actions.facebookPost || facebookPostLink(val.trim()),
                },
                visitExternalUrl: {
                    required: (val) => requiredIf(() => this.actions.visitExternalUrl)(val.trim()),
                    validUrl: () => !this.actions.visitExternalUrl || isValidUrl(this.actionsData.visitExternalUrl),
                    maxLength: maxLength(lengthUrl.max),
                },
            },
            currentYoutube: {
                required: (val) => requiredIf(() => this.actions.youtubeSubscribe)(val.trim()),
            },
            currentFacebook: {
                required: (val) => requiredIf(() => this.actions.facebookPage)(val.trim()),
            },
        };
    },
};
</script>
