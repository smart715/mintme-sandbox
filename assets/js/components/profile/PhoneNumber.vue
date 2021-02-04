<template>
    <div>
        <MazPhoneNumberInput
            v-model="phoneNumber"
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
            phoneNumber: null,
        };
    },
}
</script>
