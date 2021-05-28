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
                <span
                    class="btn-cancel px-0 c-pointer m-1"
                    @click="showModal = true"
                >
                    {{ $t('airdrop.end') }}
                </span>
                <confirm-modal
                    :visible="showModal"
                    :show-image="false"
                    @confirm="deleteAirdropCampaign"
                    @close="showModal = false">
                    <p class="text-white modal-title pt-2 pb-4">
                        {{ $t('confirm_modal.body') }}
                    </p>
                    <template v-slot:confirm>{{ $t('yes') }}</template>
                    <template v-slot:cancel>{{ $t('no') }}</template>
                </confirm-modal>
            </div>
        </div>
        <div v-else class="airdrop-campaign">
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.twitterMessage"
                        type="checkbox"
                        id="twitter-message"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="twitter-message"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'twitter']" transform="right-3 down-1 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.twitter_message') }}
                        </span>
                    </label>
                </label>
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.twitterRetweet"
                        type="checkbox"
                        id="twitter-retweet"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="twitter-retweet"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'twitter']" transform="right-3 down-1 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.twitter_retweet') }}
                        </span>
                    </label>
                </label>
            </div>
            <div class="ml-4" v-if="actions.twitterRetweet">
                <input class="form-control token-name-input w-100 px-2"
                    type="text"
                    placeholder="URL to the tweet"
                    v-model="actionsData.twitterRetweet"
                >
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.facebookMessage"
                        type="checkbox"
                        id="facebook-message"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="facebook-message"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'facebook-f']" transform="right-3 down-1 shrink-1"/>
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
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="facebook-page"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'facebook-f']" transform="right-3 down-1 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.facebook_page') }}
                        </span>
                    </label>
                </label>
            </div>
            <token-facebook-address
                class="airdrop-campaign__token-facebook-address"
                :address="currentFacebook"
                :tokenName="tokenName"
                @saveFacebook="saveFacebook"
            />
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.facebookPost"
                        type="checkbox"
                        id="facebook-post"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="facebook-post"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'facebook-f']" transform="right-3 down-1 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.facebook_post') }}
                        </span>
                    </label>
                </label>
            </div>
            <div class="ml-4" v-if="actions.facebookPost">
                <input class="form-control token-name-input w-100 px-2"
                    type="text"
                    placeholder="URL to the post"
                    v-model="actionsData.facebookPost"
                >
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.linkedinMessage"
                        type="checkbox"
                        id="linkedin-message"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="linkedin-message"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'linkedin-in']" transform="right-3 down-1 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.linkedin_message') }}
                        </span>
                    </label>
                </label>
            </div>
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.youtubeSubscribe"
                        type="checkbox"
                        id="youtube-subscribe"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="youtube-subscribe"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon :icon="['fab', 'youtube']" transform="right-2 down-1 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.youtube_subscribe') }}
                        </span>
                    </label>
                </label>
            </div>
            <token-youtube-address
                class="airdrop-campaign__token-youtube-address"
                :channel-id="currentYoutube"
                :client-id="youtubeClientId"
                :editable="true"
                :tokenName="tokenName"
                @saveYoutube="saveYoutube"
            />
            <div>
                <label class="custom-control custom-checkbox pb-0 my-3">
                    <input
                        v-model="actions.postLink"
                        type="checkbox"
                        id="post-link"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="post-link"
                    >
                        <font-awesome-layers class="ml-2">
                            <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                            <font-awesome-icon icon="globe" transform="right-3 shrink-1"/>
                        </font-awesome-layers>
                        <span class="ml-3">
                            {{ $t('airdrop.actions.post_link') }}
                        </span>
                    </label>
                </label>
            </div>
            <div class="col-12 pb-3 px-0">
                <label for="tokensAmount" class="d-block text-left">
                    {{ $t('airdrop.amount_tokens') }}
                </label>
                <input
                    id="tokensAmount"
                    type="text"
                    v-model="tokensAmount"
                    :disabled="hasAirdropCampaign"
                    class="form-control token-name-input w-100 px-2"
                    @keypress="checkInput(precision)"
                    @paste="checkInput(precision)"
                    autocomplete="off"
                >
                <div v-if="balanceLoaded && insufficientBalance" class="w-100 mt-1 text-danger">
                    {{ $t('airdrop.insufficient_funds') }}
                </div>
                <div v-else-if="balanceLoaded && !isAmountValid" class="w-100 mt-1 text-danger">
                    {{ $t('airdrop.min_amount', {tokenName: tokenName, minTokensAmount: minTokensAmount}) }}
                    {{ tokenBalance | toMoney(precision, false) | formatMoney }}.
                </div>
            </div>
            <div class="col-12 pb-3 px-0">
                <label for="participantsAmount" class="d-block text-left">
                    {{ $t('airdrop.amount_participants') }}
                </label>
                <input
                    id="participantsAmount"
                    type="text"
                    v-model="participantsAmount"
                    :disabled="hasAirdropCampaign"
                    class="form-control token-name-input w-100 px-2"
                    @keypress="checkInput(false)"
                    @paste="checkInput(false)"
                    autocomplete="off"
                >
                <div v-show="!isParticipantsAmountValid" class="w-100 mt-1 text-danger">
                    {{ $t('airdrop.min_amount_participants', {minParticipantsAmount: minParticipantsAmount, maxParticipantsAmount: maxParticipantsAmount}) }}
                </div>
            </div>
            <div v-if="!hasAirdropCampaign" class="col-12 pb-3 px-0">
                <label class="custom-control custom-checkbox pb-0">
                    <input
                        v-b-toggle.collapse-end-date
                        v-model="showEndDate"
                        type="checkbox"
                        id="showEndDate"
                        ref="end-date-checkbox"
                        class="custom-control-input"
                    >
                    <label
                        class="custom-control-label pb-0"
                        for="showEndDate">
                      {{ $t('airdrop.add_end_date') }}
                    </label>
                </label>
            </div>
            <b-collapse id="collapse-end-date">
                <div class="w-60 pb-3 px-0">
                    <label for="endDate" class="d-block text-left">
                        {{ $t('airdrop.end_date') }}
                    </label>
                    <date-picker
                        v-model="endDate"
                        id="endDate"
                        :disabled="!showEndDate || hasAirdropCampaign"
                        :config="options">
                    </date-picker>
                </div>
            </b-collapse>
            <div class="col-12 px-0 clearfix">
                <div class="w-100 mb-3 text-danger">
                    {{ errorMessage }}
                </div>
                <button
                    class="btn btn-primary float-left"
                    :disabled="btnDisabled || insufficientBalance || allOptionsUnChecked"
                    @click="createAirdropCampaign"
                >
                    {{ $t('save') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import datePicker from '../../DatePicker';
import {BCollapse, VBToggle} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faCircle, faGlobe} from '@fortawesome/free-solid-svg-icons';
import {faTwitter, faFacebookF, faLinkedinIn, faYoutube} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import ConfirmModal from '../../modal/ConfirmModal';
import {LoggerMixin, NotificationMixin, MoneyFilterMixin} from '../../../mixins';
import {TOK, HTTP_BAD_REQUEST, HTTP_NOT_FOUND, AIRDROP_CREATED, AIRDROP_DELETED, tweetLink, facebookPostLink} from '../../../utils/constants';
import TokenFacebookAddress from '../facebook/TokenFacebookAddress';
import TokenYoutubeAddress from '../youtube/TokenYoutubeAddress';
import {requiredIf} from 'vuelidate/lib/validators';

library.add(
    faCircleNotch,
    faCircle,
    faGlobe,
    faTwitter,
    faFacebookF,
    faLinkedinIn,
    faYoutube
);

export default {
    name: 'TokenAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin, MoneyFilterMixin],
    components: {
        BCollapse,
        datePicker,
        ConfirmModal,
        FontAwesomeIcon,
        FontAwesomeLayers,
        TokenFacebookAddress,
        TokenYoutubeAddress,
    },
    directives: {
        'b-toggle': VBToggle,
    },
    props: {
        tokenName: String,
        airdropParams: Object,
        facebookUrl: String,
        youtubeChannelId: String,
        youtubeClientId: String,
    },
    data() {
        return {
            showModal: false,
            airdropCampaignId: null,
            airdropCampaignRemoved: false,
            tokenBalance: 0,
            balanceLoaded: false,
            loading: false,
            showEndDate: false,
            tokensAmount: null,
            participantsAmount: null,
            endDate: moment().add(1, 'hour').toDate(),
            options: {
                format: 'MM.DD.YYYY HH:mm',
                useCurrent: false,
                minDate: moment().add(1, 'hour').toDate(),
            },
            errorMessage: '',
            precision: TOK.subunit,
            actions: {
                twitterMessage: true,
                twitterRetweet: true,
                facebookMessage: true,
                facebookPage: true,
                facebookPost: true,
                linkedinMessage: true,
                youtubeSubscribe: true,
                postLink: true,
            },
            actionsData: {
                twitterRetweet: '',
                facebookPost: '',
            },
            currentFacebook: this.facebookUrl || '',
            currentYoutube: this.youtubeChannelId || '',
        };
    },
    mounted: function() {
        this.loadTokenBalance();
        this.loadAirdropCampaign();
    },
    computed: {
        allOptionsUnChecked: function() {
            return Object.values(this.actions)
                .every((item) => item === false);
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
            return parseInt(this.airdropCampaignId) > 0;
        },
        btnDisabled: function() {
            return !this.isAmountValid
                || !this.isParticipantsAmountValid
                || !this.isDateEndValid
                || this.insufficientBalance
                || this.$v.$invalid;
        },
        insufficientBalance: function() {
            if (this.balanceLoaded) {
                let balance = new Decimal(this.tokenBalance);

                let tokensAmount = new Decimal(this.tokensAmount || 0);

                return balance.lessThan(this.minTokensAmount) || balance.lessThan(tokensAmount.add(this.reward.dividedBy(2)));
            }

            return false;
        },
        isAmountValid: function() {
            if (this.tokensAmount > 0) {
                let tokensAmount = new Decimal(this.tokensAmount);

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
            let selectedDate = moment(this.endDate, 'MM.DD.YYYY HH:mm' ).toDate();
            return this.showEndDate && selectedDate.valueOf() > moment().valueOf();
        },
        isRewardValid: function() {
            return this.reward.greaterThanOrEqualTo(this.minTokenReward);
        },
        ...mapGetters('tokenStatistics', [
            'getTokenExchangeAmount',
        ]),
        tokenExchangeAmount: function() {
            return this.getTokenExchangeAmount;
        },
        /**
         * @return {Decimal}
         */
        reward() {
            if (this.tokensAmount > 0 && this.participantsAmount > 0) {
                let amount = new Decimal(this.tokensAmount);
                let participants = new Decimal(this.participantsAmount);
                return amount.dividedBy(participants);
            }

            return new Decimal(0);
        },
    },
    methods: {
        loadTokenBalance: function() {
            this.$axios.retry.get(this.$routing.generate('token_exchange_amount', {name: this.tokenName}))
                .then((res) => {
                    this.tokenBalance = res.data;
                    this.balanceLoaded = true;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Can not load token balance data', err);
                });
        },
        loadAirdropCampaign: function() {
            this.loading = true;
            this.$axios.retry.get(this.$routing.generate('get_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    if (result.data.airdrop !== null) {
                        this.airdropCampaignId = result.data.airdrop.id;
                    }

                    if (!this.hasAirdropCampaign) {
                        this.setDefaultValues();
                    }

                    this.loading = false;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Can not load airdrop campaign.', err);
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

            let data = {
                amount: this.tokensAmount,
                participants: this.participantsAmount,
                actions: this.actions,
                actionsData: this.actionsData,
            };

            if (this.isDateValid) {
                let selectedDate = moment(this.endDate, 'MM.DD.YYYY HH:mm' ).toDate();
                data.endDate = Math.round(selectedDate.getTime()/1000);
            }

            this.loading = true;
            return this.$axios.single.post(this.$routing.generate('create_airdrop_campaign', {
                tokenName: this.tokenName,
            }), data)
                .then((result) => {
                    this.airdropCampaignId = result.data.id;
                    this.loading = false;
                    this.notifySuccess(this.$t('airdrop.msg_created'));

                    if (this.airdropCampaignRemoved) {
                        this.airdropCampaignRemoved = false;
                    }

                    window.localStorage.removeItem(AIRDROP_CREATED);
                    window.localStorage.setItem(AIRDROP_CREATED, this.tokenName);
                    this.closeEditModal();
                    location.reload();
                    return;
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
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }

                    this.loading = false;
                    this.sendLogs('error', 'Can not create airdrop campaign.', err);
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
                    this.airdropCampaignId = null;
                    this.notifySuccess(this.$t('airdrop.msg_removed'));
                    window.localStorage.removeItem(AIRDROP_DELETED);
                    window.localStorage.setItem(AIRDROP_DELETED, this.tokenName);
                    this.closeEditModal();
                    location.reload();
                })
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                    this.sendLogs('error', 'Can not delete airdrop.', err);
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
        checkInput: function(precision) {
            let selectionStart = event.target.selectionStart;
            let selectionEnd = event.target.selectionEnd;
            let amount = event.srcElement.value;
            let regex = new RegExp(`^[0-9]{0,8}(\\.[0-9]{0,${precision}})?$`);
            let input = event instanceof ClipboardEvent
                ? event.clipboardData.getData('text')
                : String.fromCharCode(!event.charCode ? event.which : event.charCode);

            if (false === precision) {
                regex = new RegExp(`^[0-9]{0,8}?$`);
            }

            if (!regex.test(amount.slice(0, selectionStart) + input + amount.slice(selectionEnd))) {
                event.preventDefault();
                return false;
            }

            return true;
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
        isRewardValid: function() {
            if (this.isRewardValid && this.errorMessage) {
                this.errorMessage = '';
            }
        },
        tokenExchangeAmount: function() {
            this.tokenBalance = this.tokenExchangeAmount;
        },
        allOptionsUnChecked: function(value) {
            this.errorMessage = value ? this.$t('airdrop.actions.error_message') : '';
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
