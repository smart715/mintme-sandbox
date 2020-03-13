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
                    ref="tokenNameInput"
                    class="token-name-input w-100 px-2"
                    :class="{ 'is-invalid': $v.$invalid }"
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
                ref="tokenNameInput"
                class="token-name-input w-100 px-2"
                :class="{ 'is-invalid': $v.$invalid }"
            >
        </div>
        <div class="col-12 pb-3 px-0">
            <label class="custom-control custom-checkbox pb-0">
                <input
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
        <div class="col-12 pb-3 px-0">
            <label for="endDate" class="d-block text-left">
                End date:
            </label>
            <input id="endDate">
        </div>
        <div class="col-12 pt-2 px-0 clearfix">
            <button
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
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
} from '../../../utils/constants';
import {LoggerMixin, NotificationMixin} from '../../../mixins';

export default {
    name: 'TokenAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin],
    components: {},
    props: {
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
    },
    data() {
        return {
            minLength: 4,
            maxLength: 60,
            submitting: false,
            showEndDate: false,
            tokensAmount: 100,
            participantsAmount: 100,
            endDate: '',
        };
    },
    computed: {
        btnDisabled: function() {
            return this.submitting || this.isTokenExchanged || !this.isTokenNotDeployed;
        },
    },
    watch: {
    },
    methods: {
        createCampaign: function() {

        },
    },
    validations() {
        return {
            newName: {
                required,
                validFirstChars: (value) => !tokenValidFirstChars(value),
                validLastChars: (value) => !tokenValidLastChars(value),
                noSpaceBetweenDashes: (value) => !tokenNoSpaceBetweenDashes(value),
                validChars: tokenNameValidChars,
                minLength: minLength(this.minLength),
                maxLength: maxLength(this.maxLength),
            },
        };
    },
};
</script>

