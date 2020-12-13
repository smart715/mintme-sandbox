<template>
    <font-awesome-icon icon="envelope" size="lg" />
</template>

<script>

export default {
    name: 'Envelope',
    props: {
        isOwner: Boolean,
        dmMinAmount: String,
        getQuoteBalance: Boolean,
    },
};
</script>
