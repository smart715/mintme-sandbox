<template>
    <div
        ref="ongoing-airdrop-campaign"
        class="airdrop-container card col-12 mb-3 px-0 py-lg-2">
        <div v-if="loaded" class="container">
            <div class="row py-2 py-md-2 py-xl-0">
                <div class="d-inline-block col-lg-10 col-md-12 pr-lg-0 align-self-center">
                    <span class="message">
                        <span class="text-bold">{{ $t('ongoing_airdrop.title') }}</span>
                        {{ $t('ongoing_airdrop.msg.1', {participants: airdropCampaign.participants, airdropReward: airdropReward}) }}
                    </span>
                    <span class="message">
                        {{ $t('ongoing_airdrop.msg.2', {tokenName: tokenName,actualParticipants: actualParticipants, participants: airdropCampaign.participants}) }}
                    </span>
                    <span
                        v-if="showEndDate"
                        class="m-0 message">
                        {{ $t('ongoing_airdrop.ends', {endsDate: endsDate, endsTime: endsTime}) }}
                        <span v-if="showDuration">
                            ({{ duration.years() }}y {{ duration.months() }}m {{ duration.days() }}d
                            {{ duration.hours() }}h {{ duration.minutes() }}m {{ duration.seconds() }}s).
                        </span>
                        <span v-if="timeElapsed">(Airdrop has ended!)</span>
                    </span>
                </div>
                <div class="d-inline-block col-lg-2 col-md-12 pl-lg-0 text-lg-right align-self-center">
                    <template v-if="!timeElapsed">
                        <span v-if="alreadyClaimed">
                            <button
                                :disabled="true"
                                class="btn btn-primary">
                                {{ $t('ongoing_airdrop.claimed') }}
                            </button>
                        </span>
                        <span v-else>
                            <copy-link :content-to-copy="modalTokenUrl"
                                class="btn btn-primary">
                                {{ $t('ongoing_airdrop.participate') }}
                            </copy-link>
                        </span>
                    </template>
                    <confirm-modal
                        :visible="showModal"
                        :button-disabled="loggedIn && !isOwner && !userAlreadyClaimed && !actionsCompleted"
                        :show-cancel-button="!isOwner && !alreadyClaimed && !timeElapsed"
                        :show-image="false"
                        @confirm="modalOnConfirm"
                        @cancel="modalOnCancel"
                        @close="showModal = false"
                    >
                        <div class="d-flex flex-column align-items-center">
                            <p class="text-white modal-title pt-2 pb-4">
                                {{ confirmModalMessage }}
                            </p>
                            <div class="w-75" v-if="loggedIn && !isOwner && actionsLength > 0 && !alreadyClaimed && loaded">
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.twitterMessage">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'twitter']" transform="right-3 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2 c-pointer" @click.prevent="claimTwitterMessage">
                                        {{ $t('airdrop.actions.twitter_message') }}
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.twitterMessage.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.twitterRetweet">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'twitter']" transform="right-3 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2 c-pointer" @click.prevent="claimTwitterRetweet">
                                        {{ $t('airdrop.actions.twitter_retweet') }}
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.twitterRetweet.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.facebookMessage">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'facebook-f']" transform="right-3 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2 c-pointer" @click.prevent="openFacebookMessage">
                                        {{ $t('airdrop.actions.facebook_message') }}
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.facebookMessage.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.facebookPage">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'facebook-f']" transform="right-3 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2">
                                        <a class="text-decoration-none text-white"
                                            :href="airdropCampaign.actionsData.facebookPage"
                                            target="_blank"
                                            @click="claimAction(airdropCampaign.actions.facebookPage)"
                                        >
                                            {{ $t('airdrop.actions.facebook_page') }}
                                        </a>
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.facebookPage.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.facebookPost">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'facebook-f']" transform="right-3 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2">
                                        <a class="text-decoration-none text-white"
                                            :href="airdropCampaign.actionsData.facebookPost"
                                            target="_blank"
                                            @click="claimAction(airdropCampaign.actions.facebookPost)"
                                        >
                                            {{ $t('airdrop.actions.facebook_post') }}
                                        </a>
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.facebookPost.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.linkedinMessage">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'linkedin-in']" transform="right-3 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2 c-pointer" @click.prevent="claimLinkedin">
                                        {{ $t('airdrop.actions.linkedin_message') }}
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.linkedinMessage.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.youtubeSubscribe">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon :icon="['fab', 'youtube']" transform="right-2 down-1 shrink-1"/>
                                    </font-awesome-layers>
                                    <span class="ml-4 pl-2 c-pointer" @click.prevent="claimYoutube">
                                        {{ $t('airdrop.actions.youtube_subscribe') }}
                                    </span>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.youtubeSubscribe.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.postLink">
                                    <font-awesome-layers class="mt-1">
                                        <font-awesome-icon icon="circle" size="lg" transform="grow-4" class="icon-blue"/>
                                        <font-awesome-icon icon="globe" transform="right-3 shrink-1"/>
                                    </font-awesome-layers>
                                    <div class="ml-4 pl-2 d-flex flex-column align-items-start w-75">
                                        <span>{{ $t('airdrop.actions.post_link') }}</span>
                                        <a :href="tokenUrl" class="truncate-name w-100">{{ tokenUrl }}</a>
                                    </div>
                                    <span class="ml-auto">
                                        {{ airdropCampaign.actions.postLink.done ? '1' : '0' }}/1
                                    </span>
                                </div>
                                <div class="d-flex my-3" v-if="airdropCampaign.actions.postLink">
                                    <input class="form-control font-size-12"
                                        type="text"
                                        v-model="postLinkUrl"
                                        :placeholder="$t('ongoing_airdrop.post_link_placeholder')"
                                    >
                                    <button class="btn btn-primary text-nowrap ml-1"
                                        :disabled="$v.postLinkUrl.$invalid"
                                        @click="claimPostLink"
                                    >
                                        {{ $t('ongoing_airdrop.submit_url') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <template v-if="!loggedIn" v-slot:cancel>Sign up</template>
                        <template v-if="!loggedIn || isOwner || timeElapsed" v-slot:confirm>
                            {{ confirmButtonText }}
                        </template>
                    </confirm-modal>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-1">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
        </div>
        <confirm-modal
            :visible="showConfirmTwitterMessageModal"
            :show-image="false"
            @confirm="doClaimTwitterMessage"
            @cancel="showConfirmTwitterMessageModal = false"
            @close="showConfirmTwitterMessageModal = false"
        >
            <p>
                {{ $t('ongoing_airdrop.twitter_message.confirm') }}
            </p>
            <p>
                "{{ actionMessage }}"
            </p>
            <template v-slot:confirm>{{ $t('ongoing_airdrop.accept') }}</template>
        </confirm-modal>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import ConfirmModal from '../../modal/ConfirmModal';
import {LoggerMixin, NotificationMixin, FiltersMixin, TwitterMixin} from '../../../mixins';
import {TOK, HTTP_BAD_REQUEST, HTTP_NOT_FOUND} from '../../../utils/constants';
import {toMoney, openPopup} from '../../../utils';
import gapi from 'gapi';
import {required, url} from 'vuelidate/lib/validators';
import CopyLink from '../../CopyLink';

const DISCOVERY_DOCS = ['https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest'];
const SCOPES = 'https://www.googleapis.com/auth/youtube.readonly';

export default {
    name: 'TokenOngoingAirdropCampaign',
    mixins: [
        NotificationMixin,
        LoggerMixin,
        FiltersMixin,
        TwitterMixin,
    ],
    components: {
        ConfirmModal,
        CopyLink,
    },
    props: {
        loggedIn: Boolean,
        isOwner: Boolean,
        tokenName: String,
        userAlreadyClaimed: Boolean,
        loginUrl: String,
        signupUrl: String,
        youtubeClientId: String,
        currentLocale: String,
        showAirdropModal: Boolean,
    },
    data() {
        return {
            showModal: false,
            airdropCampaign: null,
            loaded: false,
            btnDisabled: false,
            alreadyClaimed: this.userAlreadyClaimed,
            timeElapsed: false,
            showDuration: true,
            postLinkUrl: '',
            showConfirmTwitterMessageModal: false,
        };
    },
    mounted: function() {
        if (null !== this.currentLocale) {
            moment.locale(this.currentLocale);
        }

        this.showModal = this.showAirdropModal;
    },
    computed: {
        actionsLength() {
            return Object.keys((this.airdropCampaign || {}).actions || {}).length;
        },
        actualParticipants: function() {
            return this.airdropCampaign.actualParticipants || 0;
        },
        airdropReward: function() {
            if (this.loaded) {
                let airdropReward = new Decimal(this.airdropCampaign.amount)
                    .dividedBy(new Decimal(this.airdropCampaign.participants));

                return toMoney(airdropReward, TOK.subunit);
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
        endsDateTime: function() {
            return moment(this.airdropCampaign.endDate).format('D MMMM YYYY HH:mm:ss');
        },
        duration: {
            get: function() {
                let now = moment();
                let format = 'D MMMM YYYY HH:mm:ss';
                return moment.duration(moment(this.endsDateTime, format).diff(moment(now, format))).asMilliseconds() <= 0
                    ? moment.duration(0)
                    : moment.duration(moment(this.endsDateTime, format).diff(moment(now, format)));
            },
            set: function(newDuration) {
                return newDuration;
            },
        },
        confirmButtonText: function() {
            let button = '';

            if (!this.loggedIn) {
                button = this.$t('log_in');
            }

            if (this.isOwner || this.timeElapsed) {
                button = 'OK';
            }

            return button;
        },
        confirmModalMessage: function() {
            if (!this.loggedIn) {
                return this.$t('ongoing_airdrop.confirm_message.logged_in', {
                    airdropReward: this.airdropReward,
                    tokenName: this.tokenName,
                });
            }

            if (this.isOwner) {
                return this.$t('ongoing_airdrop.confirm_message.cant_participate');
            }

            if (this.timeElapsed) {
              return this.$t('ongoing_airdrop.ended');
            }

            if (this.actionsLength > 0) {
                return this.$t('ongoing_airdrop.actions_message', {
                    airdropReward: this.airdropReward,
                    tokenName: this.truncateFunc(this.tokenName, 30),
                    tasksAmount: this.actionsLength,
                });
            }

            return this.$t('ongoing_airdrop.confirm_message', {
                airdropReward: this.airdropReward,
                tokenName: this.tokenName,
            });
        },
        actionMessage() {
            return this.$t('ongoing_airdrop.actions.message', {
                tokenName: this.tokenName,
                tokenUrl: this.tokenUrl,
            });
        },
        tokenUrl() {
            return this.$routing.generate('token_show', {name: this.tokenName}, true);
        },
        twitterMessageLink() {
            return 'https://twitter.com/intent/tweet?text=' + decodeURI(this.actionMessage);
        },
        twitterRetweetLink() {
            return 'https://twitter.com/intent/retweet?tweet_id=' + this.airdropCampaign.actionsData.twitterRetweet;
        },
        linkedinLink() {
            return 'https://www.linkedin.com/sharing/share-offsite?url=' + this.tokenUrl;
        },
        youtubeLink() {
            return 'https://www.youtube.com/channel/' + this.airdropCampaign.actionsData.youtubeSubscribe + '?sub_confirmation=1';
        },
        actionsCompleted() {
            return this.actionsLength > 0
                ? Object.keys(this.airdropCampaign.actions).every((key) => this.airdropCampaign.actions[key].done)
                : true;
        },
        modalTokenUrl() {
            return this.$routing.generate('token_show', {name: this.tokenName, tab: 'intro', modal: 'airdrop'}, true);
        },
    },
    methods: {
        showCountdown: function() {
            this.duration = moment.duration(this.duration - 1000, 'milliseconds');
            if (this.duration.asMilliseconds() <= 0) {
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
                    this.airdropCampaign = result.data;
                    this.loaded = true;
                    this.showCountdown();
                    this.countdownInterval();
                })
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                    this.sendLogs('error', 'Can not load airdrop campaign.', err);
                });
        },
        modalOnConfirm: function() {
            if (!this.loggedIn) {
                window.location.replace(this.loginUrl);
                return;
            }

            if (this.isOwner || this.timeElapsed || !this.actionsCompleted) {
                return;
            }

            this.alreadyClaimed = true;

            return this.$axios.single.post(this.$routing.generate('claim_airdrop_campaign', {
                tokenName: this.tokenName,
                id: this.airdropCampaign.id,
            }))
                .then(() => {
                    if (this.airdropCampaign.actualParticipants < this.airdropCampaign.participants) {
                        this.airdropCampaign.actualParticipants++;
                    }
                })
                .catch((err) => {
                    if (HTTP_BAD_REQUEST === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                        setTimeout(()=> {
                            location.reload();
                        }, 1000);
                    } else if (HTTP_NOT_FOUND === err.response.status && err.response.data.message) {
                        location.href = this.$routing.generate('trading');
                    } else {
                        this.alreadyClaimed = false;
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }

                    this.sendLogs('error', 'Can not claim airdrop campaign.', err);
                });
        },
        modalOnCancel: function() {
            if (!this.loggedIn) {
                window.location.replace(this.signupUrl);
            }
        },
        claimAction(action) {
            if (action.done) {
                return;
            }

            return this.$axios.single.post(this.$routing.generate('claim_airdrop_action', {
                tokenName: this.tokenName,
                id: action.id,
            })).then(() => {
                action.done = true;
            }).catch((err) => {
                this.notifyError(this.$t('ongoing_airdrop.actions.claim_error'));
                this.sendLogs('error', 'Error claiming action', err);
            });
        },
        claimTwitterMessage() {
            if (this.airdropCampaign.actions.twitterMessage.done) {
                return openPopup(this.twitterMessageLink);
            }

            if (!this.isSignedInWithTwitter) {
                return this.signInWithTwitter().then(this.claimTwitterMessage, (err) => this.notifyError(err.message));
            }

            this.showConfirmTwitterMessageModal = true;
        },
        doClaimTwitterMessage() {
            this.$axios.single.post(this.$routing.generate('airdrop_share_twitter', {
                tokenName: this.tokenName,
            })).then(() => {
                this.claimAction(this.airdropCampaign.actions.twitterMessage);
            }).catch((err) => {
                if (err.response.data.message === 'invalid twitter token') {
                    return this.signInWithTwitter().then(this.claimTwitterMessage, (err) => this.notifyError(err.message));
                }
                this.notifyError(this.$t('ongoing_airdrop.actions.claim_error'));
                this.sendLogs('error', 'Error claiming twitter message action', err);
            });
        },
        claimTwitterRetweet() {
            if (this.airdropCampaign.actions.twitterRetweet.done) {
                return openPopup(this.twitterRetweetLink);
            }

            if (!this.isSignedInWithTwitter) {
                return this.signInWithTwitter().then(this.claimTwitterRetweet, (err) => this.notifyError(err.message));
            }

            this.$axios.single.post(this.$routing.generate('retweet_action', {
                tokenName: this.tokenName,
                id: this.airdropCampaign.actions.twitterRetweet.id,
            })).then(() => {
                this.claimAction(this.airdropCampaign.actions.twitterRetweet);
            }).catch((err) => {
                if (err.response.data.message === 'invalid twitter token') {
                    return this.signInWithTwitter().then(this.claimTwitterRetweet, (err) => this.notifyError(err.message));
                }
                this.notifyError(this.$t('ongoing_airdrop.actions.claim_error'));
                this.sendLogs('error', 'Error claiming twitter retweet action', err);
            });
        },
        openFacebookMessage() {
            FB.ui({
                method: 'share',
                href: this.tokenUrl,
            }, () => this.claimAction(this.airdropCampaign.actions.facebookMessage));
        },
        claimLinkedin() {
            openPopup(this.linkedinLink).then(() => this.claimAction(this.airdropCampaign.actions.linkedinMessage));
        },
        claimYoutube() {
            openPopup(this.youtubeLink).then(() => {
                this.signInYoutube()
                .then(this.checkIfSubscribed, () => Promise.reject(new Error(this.$t('ongoing_airdrop.youtube_authentication_required'))))
                .then(() => this.claimAction(this.airdropCampaign.actions.youtubeSubscribe), (err) => this.notifyError(err.message));
            });
        },
        claimPostLink() {
            this.$axios.single.post(this.$routing.generate('verify_post_link_action', {tokenName: this.tokenName}), {
                url: this.postLinkUrl,
            }).then((res) => {
                if (!res.data.verified) {
                    throw new Error();
                }

                return this.claimAction(this.airdropCampaign.actions.postLink);
            }).catch((err) => {
                if (err.response.data.message) {
                    this.notifyError(err.response.data.message);
                    return;
                }

                this.notifyError(this.$t('ongoing_airdrop.verification_failed'));
            });
        },
        loadYoutubeClient: function() {
            gapi.load('client:auth2', this.initYoutubeClient);
        },
        initYoutubeClient: function() {
            gapi.client.init({
                discoveryDocs: DISCOVERY_DOCS,
                clientId: this.youtubeClientId,
                scope: SCOPES,
            });
        },
        signInYoutube: function() {
            let options = new gapi.auth2.SigninOptionsBuilder();

            options.setPrompt('select_account');

            return gapi.auth2.getAuthInstance().signIn(options);
        },
        checkIfSubscribed: function() {
            return new Promise((resolve, reject) => {
                gapi.client.youtube.subscriptions.list({
                    part: 'snippet',
                    mine: true,
                    forChannelId: this.airdropCampaign.actionsData.youtubeSubscribe,
                })
                .then((response) => {
                    if (response.result.items.length > 0) {
                        resolve();
                    }
                    reject(new Error(this.$t('ongoing_airdrop.not_subscribed')));
                }).catch((err) => {
                    this.sendLogs('error', 'Can not check the subscription youtube channel.', err);
                    reject(new Error(this.$t('ongoing_airdrop.subscription_error')));
                });
            });
        },
    },
    created() {
        this.getAirdropCampaign();
        this.loadYoutubeClient();
    },
    validations() {
        return {
            postLinkUrl: {
                required: (val) => required(val.trim()),
                url,
            },
        };
    },
};
</script>

