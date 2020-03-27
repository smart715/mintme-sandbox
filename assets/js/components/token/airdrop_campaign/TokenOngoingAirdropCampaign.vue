<template>
    <div
        ref="ongoing-airdrop-campaign"
        class="col-md-10 bg-green">
        <div v-if="loaded" class="container">
            <div class="row">
                <div class="col-3 text-right">
                    <span class="font-size-h3 align-self-center mx-auto">Ongoing airdrop!</span>
                </div>
                <div class="col-6 align-middle">
                    <p class="m-0 text-white">
                        For first {{ airdropCampaign.participants }} participants
                        {{ airdropReward }} {{ tokenName }} for free.
                        Currently {{ actualParticipants }}/{{ airdropCampaign.participants }} participants.
                    </p>
                    <p
                        v-if="showEndDate"
                        class="m-0 text-white">
                        Airdrop ands on {{ andsDate }}
                    </p>
                </div>
                <div class="col-3 text-right align-self-center mx-auto">
                    <button
                        :disabled="btnDisabled"
                        @click="showModal = true"
                        class="btn btn-primary">
                        Participate
                    </button>
                    <confirm-modal
                            :visible="showModal"
                            :show-cancel-button="!alreadyClaimed"
                            @confirm="claimAirdropCampaign"
                            @close="showModal = false">
                        <p v-if="alreadyClaimed">
                            You already claimed tokens from this airdrop.
                        </p>
                        <p v-else class="text-white modal-title pt-2">
                            Are you sure you want to claim {{ tokenName }}?
                        </p>
                        <template v-if="alreadyClaimed" v-slot:confirm>OK</template>
                    </confirm-modal>
                </div>
            </div>
        </div>
        <div v-else class="text-center">
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
        tokenName: String,
        userAlreadyClaimed: Boolean,
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
        andsDate: function() {
            return moment(this.airdropCampaign.endDate).format('D MMMM YYYY');
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
        claimAirdropCampaign: function() {
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
    },
};
</script>

