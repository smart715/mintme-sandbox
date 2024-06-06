<template>
    <span class="d-inline-flex align-items-center">
        <coin-avatar
            v-if="crypto"
            is-crypto
            :symbol="crypto.symbol"
        />
        <coin-avatar
            v-else
            is-user-token
            :image="tokenImage"
            :deployed="tokenDeployed"
            :symbol="tokenCryptoSymbol"
        />
        <span class="ml-1">{{ name | rebranding }}</span>
    </span>
</template>

<script>
import {RebrandingFilterMixin} from '../mixins';
import {tokenDeploymentStatus} from '../utils/constants';
import CoinAvatar from './CoinAvatar';

export default {
    name: 'CoinAvatarName',
    components: {CoinAvatar},
    mixins: [RebrandingFilterMixin],
    props: {
        crypto: Object,
        token: Object,
    },
    computed: {
        tokenDeployed() {
            return this.token && tokenDeploymentStatus.deployed === this.token.deploymentStatus;
        },
        tokenImage() {
            return this.token?.image?.avatar_small;
        },
        tokenCryptoSymbol() {
            return this.tokenDeployed && this.token.networks?.length
                ? this.token.networks[0]
                : null;
        },
        name() {
            return this.token
                ? this.token.name
                : this.crypto;
        },
    },
};
</script>
