<template>
    <div class="phone-number">
        <vue-phone-number-input
            v-model="phone"
            @update="updatePhone"
            :translations="translations"
            :default-country-code="countryCode"
            dark
            show-code-on-list
            no-validator-state
            clearable
            :border-radius="0"
            :countries-height="32"
        />
        <div v-if="showValidationMessage" class="text-danger text-center">
            {{ $t('phone_number.wrong') }}
        </div>
    </div>
</template>

<script>
import VuePhoneNumberInput from 'vue-phone-number-input';
import 'vue-phone-number-input/dist/vue-phone-number-input.css';

export default {
    name: 'PhoneNumber',
    components: {
        VuePhoneNumberInput,
    },
    data() {
        return {
            phone: this.phoneNumber,
            isValidNumber: null,
        };
    },
    props: {
        countryCode: String,
        phoneNumber: String,
    },
    computed: {
        showValidationMessage: function() {
            return !!this.phone && !this.isValidNumber;
        },
        phoneNumberModel: {
            set: function(phone) {
                this.phone = phone;
                this.$emit('phone-change', this.phone);
            },
            get: function() {
                return this.phone;
            },
        },
        translations() {
            return {
                countrySelectorLabel: this.$t('phone.country_selector.label'),
                countrySelectorError: this.$t('phone.country_selector.error'),
                phoneNumberLabel: this.$t('phone.phone_number.label'),
                example: this.$t('phone.example'),
            };
        },
    },
    methods: {
        updatePhone: function(data) {
            this.isValidNumber = data.isValid;
            this.$emit('is-valid-phone', this.isValidNumber);
            this.phoneNumberModel = data.e164 || data.phoneNumber;
        },
    },
};
</script>
