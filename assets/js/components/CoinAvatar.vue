<template>
    <span
        v-if="avatarImg"
        class="coin-avatar"
    >
        <img
            :src="avatarImg"
            class="rounded-circle"
            :class="[imageClass, {'coin-avatar-mintme': isWebSymbol}]"
        />
    </span>
</template>

<script>

import {
    WEB,
    MINTME,
    WEB_GREY_ICON_NAME,
} from '../utils/constants';
import {getUserTokenImg, getCoinAvatarAssetName} from '../utils';
export default {
    name: 'CoinAvatar',
    props: {
        image: {
            type: [String, Object],
            default: null,
        },
        symbol: {
            type: String,
            default: null,
        },
        isCrypto: {
            type: Boolean,
            default: false,
        },
        isDeployed: {
            type: Boolean,
            default: false,
        },
        isUserToken: {
            type: Boolean,
            default: false,
        },
        isWhiteColor: {
            type: Boolean,
            default: false,
        },
        isGreyColor: {
            type: Boolean,
            default: false,
        },
        imageClass: {
            type: String,
            default: 'coin-avatar-sm',
        },
    },
    computed: {
        avatarImg: function() {
            if (this.isCrypto) {
                return require(`../../img/${this.getTokenIconBySymbol(this.symbol)}`);
            }

            if (this.isUserToken) {
                return getUserTokenImg(this.image);
            }

            return this.getNotUserTokenImg();
        },
        isWebSymbol() {
            return WEB.symbol === this.symbol || MINTME.symbol === this.symbol;
        },
    },
    methods: {
        getNotUserTokenImg: function() {
            return this.isDeployed
                ? require(`../../img/${this.getTokenIconBySymbol(this.symbol)}`)
                : '';
        },
        getTokenIconBySymbol(symbol) {
            if ((WEB.symbol === symbol || MINTME.symbol == symbol) && this.isGreyColor) {
                return WEB_GREY_ICON_NAME;
            }

            return getCoinAvatarAssetName(symbol, this.isWhiteColor);
        },
    },
};
</script>
