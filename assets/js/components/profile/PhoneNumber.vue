<template>
    <div class="phone-number">
        <MazPhoneNumberInput
            @update="updatePhone"
            dark
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
    },
    methods: {
        updatePhone: function(data) {
            this.phoneNumberModel = data.e164;
        },
    },
};
</script>
