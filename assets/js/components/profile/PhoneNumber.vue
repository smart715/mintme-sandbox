<template>
    <div class="phone-number">
        <MazPhoneNumberInput
            v-model="phoneNumber"
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
    props: {
        phoneNumber: String,
    },
};
</script>
