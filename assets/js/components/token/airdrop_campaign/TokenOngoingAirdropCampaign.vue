<template>
    <div
        ref="ongoing-airdrop-campaign"
        class="airdrop-container card col-12 mb-3 px-0 py-lg-2">
        <div v-if="loaded" class="container">
            <div class="row py-2 py-md-2 py-xl-0">
                <div class="d-inline-block col-lg-10 col-md-12 pr-lg-0 align-self-center">
                    <span class="message">
                        <span class="text-bold">Ongoing airdrop!</span>
                        For first {{ airdropCampaign.participants }} participants {{ airdropReward }}
                    </span>
                    <span class="message">
                        {{ tokenName }} for free. Currently {{ actualParticipants }}/{{ airdropCampaign.participants }} participants.
                    </span>
                    <span
                        v-if="showEndDate"
                        class="m-0 message">
                        Airdrop ends on {{ endsDate }} at {{ endsTime }}({{ duration }}).
                    </span>
                </div>
                <div class="d-inline-block col-lg-2 col-md-12 pl-lg-0 text-lg-right align-self-center">
                    <button
                        :disabled="btnDisabled"
                        @click="showModal = true"
                        class="btn btn-primary">
                        Participate
                    </button>
                    <confirm-modal
                        :visible="showModal"
                        :show-cancel-button="!isOwner && !alreadyClaimed"
                        :show-image="false"
                        @confirm="modalOnConfirm"
                        @cancel="modalOnCancel"
                        @close="showModal = false">
                        <p class="text-white modal-title pt-2 pb-4">
                            {{ confirmModalMessage }}
                        </p>
                        <template v-if="!loggedIn" v-slot:cancel>Sign up</template>
                        <template v-if="!loggedIn || isOwner || alreadyClaimed" v-slot:confirm>
                            {{ confirmButtonText }}
                        </template>
                    </confirm-modal>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-1">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import ConfirmModal from '../../modal/ConfirmModal';
import {LoggerMixin, NotificationMixin} from '../../../mixins';
import {TOK, HTTP_BAD_REQUEST, HTTP_NOT_FOUND} from '../../../utils/constants';
import {toMoney} from '../../../utils';

export default {
    name: 'TokenOngoingAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin],
    components: {
        ConfirmModal,
    },
    props: {
        loggedIn: Boolean,
        isOwner: Boolean,
        tokenName: String,
        userAlreadyClaimed: Boolean,
        loginUrl: String,
        signupUrl: String,
    },
    data() {
        return {
            showModal: false,
            airdropCampaign: null,
            loaded: false,
            btnDisabled: false,
            alreadyClaimed: this.userAlreadyClaimed,
        };
    },
    mounted: function() {
        this.getAirdropCampaign();
        setInterval(() => {
            this.duration = moment.duration(this.duration - 1000, 'milliseconds');
        }, 1000);
    },
    computed: {
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
        duration: function() {
            return moment.duration(moment().diff(this.endsDateTime), 'milliseconds', true);
            // return moment.duration(moment().diff(this.endsDateTime)).asDays().format('HH:mm:ss');
        },
        confirmButtonText: function() {
            let button = '';

            if (!this.loggedIn) {
                button = 'Log In';
            }

            if (this.isOwner || this.alreadyClaimed) {
                button = 'OK';
            }

            return button;
        },
        confirmModalMessage: function() {
            if (!this.loggedIn) {
                return `You have to be logged in to claim ${this.airdropReward} ${this.tokenName}.`;
            }

            if (this.isOwner) {
                return 'Sorry, you can\'t participate in your own airdrop.';
            }

            if (this.alreadyClaimed) {
                return 'You already claimed tokens from this airdrop.';
            }

            return `Are you sure you want to claim ${this.airdropReward} ${this.tokenName}?`;
        },
    },
    methods: {
        getAirdropCampaign: function() {
            this.$axios.retry.get(this.$routing.generate('get_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    this.airdropCampaign = result.data;
                    this.loaded = true;
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load airdrop campaign.', err);
                });
        },
        modalOnConfirm: function() {
            if (!this.loggedIn) {
                window.location.replace(this.loginUrl);
                return;
            }

            if (this.isOwner || this.alreadyClaimed) {
                return;
            }

            this.btnDisabled = true;
            return this.$axios.single.post(this.$routing.generate('claim_airdrop_campaign', {
                tokenName: this.tokenName,
                id: this.airdropCampaign.id,
            }))
                .then(() => {
                    if (this.airdropCampaign.actualParticipants < this.airdropCampaign.participants) {
                        this.airdropCampaign.actualParticipants++;
                    }

                    this.alreadyClaimed = true;
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
                        this.notifyError('Something went wrong. Try to reload the page.');
                    }

                    this.sendLogs('error', 'Can not claim airdrop campaign.', err);
                })
                .then(() => this.btnDisabled = false);
        },
        modalOnCancel: function() {
            if (!this.loggedIn) {
                window.location.replace(this.signupUrl);
            }
        },
    },
};
</script>

