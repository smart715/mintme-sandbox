<template>
    <div>
        <div class="col-12 pb-3 px-0">
            <label for="tokensAmount" class="d-block text-left">
                Amount of tokens for airdrop:
            </label>
            <input
                id="tokensAmount"
                type="text"
                v-model="tokensAmount"
                class="token-name-input w-100 px-2"
                @keypress="checkInput(4)"
                @paste="checkInput(4)"
            >
        </div>
        <div class="col-12 pb-3 px-0">
            <label for="participantsAmount" class="d-block text-left">
                Amount of participants:
            </label>
            <input
                id="participantsAmount"
                type="text"
                v-model="participantsAmount"
                class="token-name-input w-100 px-2"
                @keypress="checkInput(false)"
                @paste="checkInput(false)"
            >
        </div>
        <div class="col-12 pb-3 px-0">
            <label class="custom-control custom-checkbox pb-0">
                <input
                    v-b-toggle.collapse-and-date
                    v-model="showEndDate"
                    type="checkbox"
                    id="showEndDate"
                    class="custom-control-input"
                >
                <label
                    class="custom-control-label pb-0"
                    for="showEndDate">
                    Add end date
                </label>
            </label>
        </div>
        <b-collapse id="collapse-and-date">
            <div class="col-12 pb-3 px-0">
                <label for="endDate" class="d-block text-left">
                    End date:
                </label>
                <date-picker
                    v-model="endDate"
                    id="endDate"
                    :disabled="!showEndDate"
                    :config="options">
                </date-picker>
            </div>
        </b-collapse>
        <div class="col-12 pt-2 px-0 clearfix">
            <button
                v-if="hasAirdropCampaign"
                class="btn btn-primary float-left"
                @click="deleteCampaign"
            >
                Delete
            </button>
            <button
                v-else
                class="btn btn-primary float-left"
                :disabled="btnDisabled"
                @click="createCampaign"
            >
                Save
            </button>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import 'bootstrap/dist/css/bootstrap.css';
import 'pc-bootstrap4-datetimepicker/build/css/bootstrap-datetimepicker.css';
import datePicker from 'vue-bootstrap-datetimepicker';
import {LoggerMixin, NotificationMixin, MoneyFilterMixin} from '../../../mixins';
import {GENERAL} from '../../../utils/constants';

export default {
    name: 'TokenAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin, MoneyFilterMixin],
    components: {
        datePicker,
    },
    props: {
        tokenName: String,
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
    },
    data() {
        return {
            airdropCampaign: null,
            tokenBalance: 0,
            minTokenReward: '0.0001',
            submitting: false,
            showEndDate: false,
            tokensAmount: 100,
            participantsAmount: 100,
            endDate: moment().add(30, 'days').toDate(),
            options: {
                format: GENERAL.dateFormat,
                useCurrent: false,
                minDate: moment(),
            },
        };
    },
    mounted: function() {
        this.loadTokenBalance();
        this.loadAirdropCampaign();
    },
    computed: {
        hasAirdropCampaign: function() {
            return this.airdropCampaign !== null && parseInt(this.airdropCampaign.id) > 0;
        },
        btnDisabled: function() {
            return !(this.isAmountValid && this.isParticipantsAmountValid && this.isDateEndValid);
        },
        isAmountValid: function() {
            return this.tokensAmount > 0
                && (new Decimal(this.tokensAmount)).lessThan(this.tokenBalance);
        },
        isParticipantsAmountValid: function() {
            return this.participantsAmount > 0;
        },
        isDateEndValid: function() {
            return !this.showEndDate || this.isDateValid;
        },
        isDateValid: function() {
            return this.showEndDate && moment(this.endDate).isValid();
        },
        isRewardValid: function() {
            if (this.isAmountValid && this.isParticipantsAmountValid) {
                let amount = new Decimal(this.tokensAmount);
                let participants = new Decimal(this.participantsAmount);
                let res = amount.dividedBy(participants);

                return res.greaterThan(this.minTokenReward);
            }

            return false;
        },
    },
    watch: {},
    methods: {
        loadTokenBalance: function() {
            this.$axios.retry.get(this.$routing.generate('token_exchange_amount', {name: this.tokenName}))
                .then((res) => this.tokenBalance = res.data)
                .catch((err) => {
                    this.notifyError('Can not load token balance data. Try again later');
                    this.sendLogs('error', 'Can not load token balance data', err);
                });
        },
        loadAirdropCampaign: function() {
            this.$axios.retry.get(this.$routing.generate('get_airdrop_campaign', {
                tokenName: this.tokenName,
            }))
                .then((result) => {
                    this.airdropCampaign = result.data;
                    if (typeof this.airdropCampaign === 'object') {
                        this.tokensAmount = this.airdropCampaign.amount;
                        this.participantsAmount = this.airdropCampaign.participants;

                        if (this.airdropCampaign.endDate) {
                            this.endDate = this.airdropCampaign.endDate;
                        }
                    }
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load airdrop campaign.', err);
                });
        },
        createCampaign: function() {
            if (this.btnDisabled) {
                return;
            }

            let data = {
                    amount: this.tokensAmount,
                    participants: this.participantsAmount,
                };

            if (this.isDateValid) {
                data.endDate = this.endDate;
            }

            return this.$axios.single.post(this.$routing.generate('create_airdrop_campaign', {
                tokenName: this.tokenName,
            }), data)
                .then((res) => this.clients.push(res.data))
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not create API Client', err);
                });
        },
        deleteCampaign: function() {
            if (!this.hasAirdropCampaign) {
                return;
            }

            return this.$axios.single.delete(this.$routing.generate('delete_airdrop_campaign', {
                id: this.airdropCampaign.id,
            }))
                .then(() => {
                    this.airdropCampaign = null;
                    this.tokensAmount = 100;
                    this.participantsAmount = 100;
                    this.endDate = moment().add(30, 'days').toDate();
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not delete airdrop.', err);
                });
        },
        checkInput: function(precision) {
            let selectionStart = event.target.selectionStart;
            let selectionEnd = event.target.selectionEnd;
            let amount = event.srcElement.value;
            let regex = new RegExp(`^[0-9]{0,8}(\\.[0-9]{0,${precision}})?$`);
            let input = event instanceof ClipboardEvent
                ? event.clipboardData.getData('text')
                : String.fromCharCode(!event.charCode ? event.which : event.charCode);

            if (precision === false) {
                regex = new RegExp(`^[0-9]{0,8}?$`);
            }

            if (!regex.test(amount.slice(0, selectionStart) + input + amount.slice(selectionEnd))) {
                event.preventDefault();
                return false;
            }

            return true;
        },
    },
};
</script>

