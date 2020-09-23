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
                    End this airdrop
                </span>
                <confirm-modal
                    :visible="showModal"
                    :show-image="false"
                    @confirm="deleteAirdropCampaign"
                    @close="showModal = false">
                    <p class="text-white modal-title pt-2 pb-4">
                        Are you sure?
                    </p>
                    <template v-slot:confirm>Yes</template>
                    <template v-slot:cancel>No</template>
                </confirm-modal>
            </div>
        </div>
        <div v-else>
            <div class="col-12 pb-3 px-0">
                <label for="tokensAmount" class="d-block text-left">
                    Amount of tokens for airdrop:
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
                    Insufficient funds for airdrop campaign.
                </div>
                <div v-else-if="balanceLoaded && !isAmountValid" class="w-100 mt-1 text-danger">
                    Minimum amount of {{ tokenName }} {{ minTokensAmount }}, limit
                    {{ tokenBalance | toMoney(precision, false) | formatMoney }}.
                </div>
            </div>
            <div class="col-12 pb-3 px-0">
                <label for="participantsAmount" class="d-block text-left">
                    Amount of participants:
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
                    Minimum amount of participants {{ minParticipantsAmount }}, limit {{ maxParticipantsAmount }}.
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
                        Add end date
                    </label>
                </label>
            </div>
            <b-collapse id="collapse-end-date">
                <div class="w-60 pb-3 px-0">
                    <label for="endDate" class="d-block text-left">
                        End date:
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
                    :disabled="btnDisabled || insufficientBalance"
                    @click="createAirdropCampaign"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import datePicker from 'vue-bootstrap-datetimepicker';
import ConfirmModal from '../../modal/ConfirmModal';
import {LoggerMixin, NotificationMixin, MoneyFilterMixin} from '../../../mixins';
import {TOK, HTTP_BAD_REQUEST, HTTP_NOT_FOUND, AIRDROP_CREATED, AIRDROP_DELETED} from '../../../utils/constants';
import {mapGetters} from 'vuex';

export default {
    name: 'TokenAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin, MoneyFilterMixin],
    components: {
        datePicker,
        ConfirmModal,
    },
    props: {
        tokenName: String,
        airdropParams: Object,
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
            endDate: moment().add(30, 'days').toDate(),
            options: {
                format: 'MM.DD.YYYY HH:mm',
                useCurrent: false,
                minDate: moment().add(24, 'hours').toDate(),
            },
            errorMessage: '',
            precision: TOK.subunit,
        };
    },
    mounted: function() {
        this.loadTokenBalance();
        this.loadAirdropCampaign();
    },
    computed: {
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
            return !(this.isAmountValid && this.isParticipantsAmountValid && this.isDateEndValid);
        },
        insufficientBalance: function() {
            if (this.balanceLoaded) {
                let balance = new Decimal(this.tokenBalance);

                return balance.lessThan(this.minTokensAmount);
            }

            return false;
        },
        isAmountValid: function() {
            if (this.tokensAmount > 0) {
                let tokensAmount = new Decimal(this.tokensAmount);

                return tokensAmount.greaterThanOrEqualTo(this.minTokensAmount)
                    && tokensAmount.lessThanOrEqualTo(this.tokenBalance);
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
            return this.showEndDate && Date.parse(this.endDate) > Date.now();
        },
        isRewardValid: function() {
            if (this.isAmountValid && this.isParticipantsAmountValid) {
                let amount = new Decimal(this.tokensAmount);
                let participants = new Decimal(this.participantsAmount);
                let res = amount.dividedBy(participants);

                return res.greaterThanOrEqualTo(this.minTokenReward);
            }

            return false;
        },
        ...mapGetters('tokenStatistics', [
            'getTokenExchangeAmount',
        ]),
        tokenExchangeAmount: function() {
            return this.getTokenExchangeAmount;
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
                    this.notifyError('Can not load token balance data. Try again later');
                    this.sendLogs('error', 'Can not load token balance data', err);
                });
        },
        loadAirdropCampaign: function() {
            this.loading = true;
            this.$axios.retry.get(this.$routing.generate('get_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    if ('object' === typeof result.data) {
                        this.airdropCampaignId = result.data.id;
                    }

                    if (!this.hasAirdropCampaign) {
                        this.setDefaultValues();
                    }

                    this.loading = false;
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load airdrop campaign.', err);
                });
        },
        createAirdropCampaign: function() {
            if (this.btnDisabled || this.insufficientBalance) {
                return;
            }

            if (!this.isRewardValid) {
                this.errorMessage = `Reward can't be lower than ${this.minTokenReward} ${this.tokenName}.`
                    + ` Set higher amount of tokens for airdrop or lower amount of participants.`;
                return;
            }

            let data = {
                amount: this.tokensAmount,
                participants: this.participantsAmount,
            };

            if (this.isDateValid) {
                data.endDate = moment(this.endDate).utc().unix();
            }

            this.loading = true;
            return this.$axios.single.post(this.$routing.generate('create_airdrop_campaign', {
                tokenName: this.tokenName,
            }), data)
                .then((result) => {
                    this.airdropCampaignId = result.data.id;
                    this.loading = false;
                    this.notifySuccess('Your airdrop was created successfully.');

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
                        this.notifyError('Something went wrong. Try to reload the page.');
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
                    this.notifySuccess('Your airdrop was removed successfully.');
                    window.localStorage.removeItem(AIRDROP_DELETED);
                    window.localStorage.setItem(AIRDROP_DELETED, this.tokenName);
                    this.closeEditModal();
                    location.reload();
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
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
            this.endDate = moment().add(30, 'days').toDate();
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
    },
};
</script>
