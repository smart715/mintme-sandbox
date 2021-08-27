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
                <div class="pl-2 pt-2">
                    <span>
                        {{ $t('wallet.buy_crypto.can_exchange', translationContext) }}
                    </span>
                    <div v-for="crypto in cryptoToExchangeWithMintme" :key="crypto.name">
                        <a :href="crypto.url" target="_blank" v-html="generatePairText(crypto.name)">
                        </a>
                    </div>
                </div>
                <iframe
                    v-if="paramsLoaded"
                    ref="coinifyIframe"
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Modal from './Modal';
import {primaryColor} from '../../utils/constants';
import {webSymbol, MINTME} from '../../utils/constants';

library.add(faCircleNotch);

const ADDRESS_CHANGED_EVENT = 'trade.receive-account-changed';
const RECEIVED_ACCOUNT_CONFIRMED_EVENT = 'trade.receive-account-confirmed';

export default {
    name: 'BuyCryptoModal',
    components: {
        Modal,
        FontAwesomeIcon,
    },
    props: {
        visible: Boolean,
        uiUrl: String,
        partnerId: Number,
        cryptoCurrencies: Array,
        refreshToken: String,
        addresses: Object,
        addressesSignature: Object,
        predefinedTokens: Array,
    },
    computed: {
        cryptoToExchangeWithMintme: function() {
            return this.predefinedTokens.filter((crypto) => crypto.cryptoSymbol !== webSymbol);
        },
        // example of return: "BNB, BTC, ETH and USDC"
        cryptoListForTranslation: function() {
            return this.cryptoToExchangeWithMintme.reduce((listStr, currentValue, index) => {
                return index === this.cryptoToExchangeWithMintme.length - 1
                    ? listStr + ' '+ this.$t('and') +' ' + currentValue.cryptoSymbol
                    : ('' === listStr
                        ? currentValue.cryptoSymbol
                        : listStr + ', ' + currentValue.cryptoSymbol);
            }, '');
        },
        translationContext: function() {
            return {
                cryptosList: this.cryptoListForTranslation,
            };
        },
        coinifyAddress: function() {
            return Object.keys(this.addresses)
                .filter((key) => this.cryptoCurrencies.includes(key))
                .map((key) => key + ':' + this.addresses[key]).join(',');
        },
        coinifyAddressSignature: function() {
            return Object.keys(this.addresses)
                .filter((key) => this.cryptoCurrencies.includes(key))
                .map((key) => key + ':' + this.addressesSignature[key]).join(',');
        },
        frameSrcParams: function() {
            return {
                partnerId: this.partnerId,
                cryptoCurrencies: this.cryptoCurrencies.join(','),
                primaryColor: primaryColor,
                fontColor: 'gray',
                address: this.coinifyAddress,
                addressSignature: this.coinifyAddressSignature,
                refreshToken: encodeURIComponent(this.refreshToken),
                addressConfirmation: true,
            };
        },
        frameSrcQueryStr: function() {
            return Object.keys(this.frameSrcParams).map((key) => key + '=' + this.frameSrcParams[key]).join('&');
        },
        frameSrc: function() {
            return `${this.uiUrl}/widget?${this.frameSrcQueryStr}`;
        },
        paramsLoaded: function() {
            return Object.keys(this.addresses).length > 0
                && Object.keys(this.addressesSignature).length > 0
                && this.refreshToken;
        },
    },
    methods: {
        generatePairText: function(name) {
            return `${MINTME.symbol}/${name}`;
        },
        listenForEvents: function() {
            window.addEventListener('message', (event) => {
                if (event.origin === this.uiUrl && ADDRESS_CHANGED_EVENT === event.data.event) {
                    this.handleAccountAddressChanged(event.data.context);
                }
            }, false);
        },
        handleAccountAddressChanged: function(context) {
            if (this.addresses[context.currency] === context.address) {
                const iframe = this.$refs.coinifyIframe;
                iframe.contentWindow.postMessage({
                    type: 'event',
                    event: RECEIVED_ACCOUNT_CONFIRMED_EVENT,
                    context: {
                        address: context.address,
                        status: 'accepted',
                    },
                }, '*');
            }
        },
    },
    mounted: function() {
        this.listenForEvents();
    },
};
</script>
