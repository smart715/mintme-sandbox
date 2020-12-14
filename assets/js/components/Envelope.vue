<template>
    <font-awesome-icon icon="envelope" size="lg" @click="checkDirectMessage" />
</template>

<script>

import {NotificationMixin} from '../../mixins';

    export default {
    name: 'Envelope',
    mixins: [NotificationMixin],
    props: {
        isOwner: Boolean,
        dmMinAmount: String,
        getQuoteBalance: Boolean,
    },
    methods: {
        checkDirectMessage: function() {
            this.notifyError('To send direct message you need to have 100 SuperCoins tokens');
        },
    },
};
</script>
