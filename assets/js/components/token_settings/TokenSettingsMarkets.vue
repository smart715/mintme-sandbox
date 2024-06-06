<template>
    <div>
        <token-market-edit
            is-owner
            :token-name="getTokenName"
            :disabled-services-config="disabledServicesConfig"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
        />
    </div>
</template>

<script>
import {mapGetters} from 'vuex';
import TokenMarketEdit from '../token/TokenMarketEdit';

export default {
    name: 'TokenSettingsMarkets',
    props: {
        disabledServicesConfig: String,
        isCreatedOnMintmeSite: Boolean,
    },
    components: {
        TokenMarketEdit,
    },
    computed: {
        ...mapGetters('tokenSettings', [
            'getTokenName',
        ]),
    },
};
</script>
