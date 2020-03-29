<template>
    <div
        ref="ongoing-airdrop-campaign"
        class="card col-12 bg-green py-1">
        <div v-if="loaded">
            <div class="row">
                <div class="col-sm-3 align-self-center">
                    <h4 class="my-0 mx-auto line-height-1">Ongoing airdrop!</h4>
                </div>
                <div class="col-sm-7 align-middle">
                    <p class="m-0 text-white">
                        For first {{ airdropCampaign.participants }} participants
                        {{ airdropReward }} {{ tokenName }} for free.
                        Currently {{ actualParticipants }}/{{ airdropCampaign.participants }} participants.
                    </p>
                    <p
                        v-if="showEndDate"
                        class="m-0 text-white">
                        Airdrop ends on {{ endsDate }} at {{ endsTime }}
                    </p>
                </div>
                <div class="col-sm-2 text-right align-self-center">
                    <button
                        :disabled="btnDisabled"
                        @click="showModal = true"
                        class="btn btn-primary">
                        Participate
                    </button>
                    <confirm-modal
                            :visible="showModal"
                            :show-cancel-button="!isOwner && !alreadyClaimed"
                            @confirm="modalOnConfirm"
                            @cancel="modalOnCancel"
                            @close="showModal = false">
                        <p v-if="!loggedIn">
                            You have to be logged in to claim {{ airdropReward }} {{ tokenName }}
                        </p>
                        <p v-else-if="isOwner">
                            Sorry, you can't participate in your own airdrop.
                        </p>
                        <p v-else-if="alreadyClaimed">
                            You already claimed tokens from this airdrop.
                        </p>
                        <p v-else class="text-white modal-title pt-2">
                            Are you sure you want to claim {{ airdropReward }} {{ tokenName }}?
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
import {LoggerMixin, NotificationMixin, MoneyFilterMixin} from '../../../mixins';
import {TOK} from '../../../utils/constants';
import {toMoney} from '../../../utils';

export default {
    name: 'TokenOngoingAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin, MoneyFilterMixin],
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
    },
    computed: {
        actualParticipants: function() {
            return this.airdropCampaign.actualParticipants
                ? this.airdropCampaign.actualParticipants
                : 0;
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
            return moment(this.airdropCampaign.endDate).format('D MMMM YYYY');
        },
        endsTime: function() {
            return moment(this.airdropCampaign.endDate).format('HH:mm');
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
            }

            if (this.alreadyClaimed) {
                return;
            }

            this.btnDisabled = true;
            return this.$axios.single.post(this.$routing.generate('claim_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then(() => {
                    if (this.airdropCampaign.actualParticipants < this.airdropCampaign.participants) {
                        this.airdropCampaign.actualParticipants++;
                    }

                    this.alreadyClaimed = true;
                    this.btnDisabled = false;
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not create API Client', err);
                });
        },
        modalOnCancel: function() {
            if (!this.loggedIn) {
                window.location.replace(this.signupUrl);
            }
        },
    },
};
</script>

