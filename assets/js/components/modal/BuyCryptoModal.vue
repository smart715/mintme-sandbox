<template>
    <div>
        <modal
            :visible="visible"
            :without-padding="true"
            @close="$emit('close')"
        >
            <template slot="header">
                <p class="word-break-all">{{ $t('wallet.buy_crypto') }}</p>
            </template>
            <template slot="body">
                <iframe
                    v-if="paramsLoaded"
                    :src="frameSrc"
                    width="100%"
                    height="450px"
                    class="border-0"
                    allow="camera"
                ></iframe>
                <div v-else class="p-5 d-flex justify-content-center">
                    <font-awesome-icon
                        icon="circle-notch"
                        spin
                        class="loading-spinner"
                        fixed-width
                    />
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import FaqItem from '../FaqItem';
import Modal from './Modal';
import {primaryColor} from '../../utils/constants';

export default {
    name: 'TokenEditModal',
    components: {
        FaqItem,
        Modal,
    },
    props: {
        visible: Boolean,
        uiUrl: String,
        partnerId: Number,
        cryptoCurrencies: Array,
        refreshToken: String,
        addresses: Object,
    },
    computed: {
        coinifyAddress: function() {
            return Object.keys(this.addresses)
                .filter((key) => this.cryptoCurrencies.includes(key))
                .map((key) => key + ':' + this.addresses[key]).join(',');
        },
        frameSrcParams: function() {
            return {
                partnerId: this.partnerId,
                cryptoCurrencies: this.cryptoCurrencies.join(','),
                primaryColor: primaryColor,
                fontColor: 'gray',
                address: this.coinifyAddress,
                refreshToken: encodeURIComponent(this.refreshToken),
            };
        },
        frameSrcQueryStr: function() {
            return Object.keys(this.frameSrcParams).map((key) => key + '=' + this.frameSrcParams[key]).join('&');
        },
        frameSrc: function() {
            return `${this.uiUrl}/widget?${this.frameSrcQueryStr}`;
        },
        paramsLoaded: function() {
            return Object.keys(this.addresses).length > 0 && this.refreshToken;
        },
    },
};
</script>
