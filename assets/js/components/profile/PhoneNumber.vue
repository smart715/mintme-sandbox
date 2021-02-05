<template>
    <div class="phone-number">
        <MazPhoneNumberInput
            @update="updatePhone"
            :translations="translations"
            dark
            showCodeOnList
        />
    </div>
</template>

<script>
import {MazPhoneNumberInput} from 'maz-ui';

export default {
    name: 'PhoneNumber',
    components: {
        MazPhoneNumberInput,
    },
    data() {
        return {
            phone: this.phoneNumber,
        };
    },
    props: {
        phoneNumber: String,
    },
    computed: {
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
            this.phoneNumberModel = data.e164;
        },
    },
};
</script>
