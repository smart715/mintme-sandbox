<template>
    <div class="buy-crypto d-flex justify-content-center py-2">
        <div class="d-flex align-items-center">
            <button
                class="btn btn-primary d-flex align-items-center px-4 py-2"
                :disabled="viewOnly"
                @click="buyCrypto"
            >
                <font-awesome-icon
                    class="ml-2 credit-card-icon"
                    :icon="{prefix: 'fas', iconName: 'credit-card'}"
                />
                <span class="ml-2 button-text">
                    {{ $t('wallet.buy_crypto') }}
                </span>
            </button>
            <font-awesome-icon
                class="ml-2"
                :icon="{prefix: 'fab', iconName: 'cc-visa'}"
                size="3x"
            />
            <font-awesome-icon
                class="ml-2"
                :icon="{prefix: 'fab', iconName: 'cc-mastercard'}"
                size="3x"
            />
            <img
                class="ml-2 img-rounded"
                src="../../../img/sepa-icon.svg"
                alt="visa icon"
            />
        </div>
        <buy-crypto-modal
            :visible="modalVisible"
            :service-unavailable="serviceUnavailable"
            :ui-url="coinifyUiUrl"
            :partner-id="coinifyPartnerId"
            :crypto-currencies="coinifyCryptoCurrencies"
            :addresses="depositAddresses"
            :addresses-signature="addressesSignature"
            :refresh-token="refreshToken"
            :predefined-tokens="predefinedTokens"
            :trading-url="tradingUrl"
            @close="modalVisible = false"
        />
    </div>
</template>

<script>
import BuyCryptoModal from '../modal/BuyCryptoModal';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCcVisa, faCcMastercard} from '@fortawesome/free-brands-svg-icons';
import {faCreditCard} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faCcVisa, faCcMastercard, faCreditCard);

export default {
    name: 'BuyCrypto',
    components: {
        BuyCryptoModal,
        FontAwesomeIcon,
    },
    props: {
        coinifyUiUrl: String,
        coinifyPartnerId: Number,
        coinifyCryptoCurrencies: Array,
        predefinedTokens: Array,
        mintmeExchangeMailSent: Boolean,
        viewOnly: Boolean,
        tradingUrl: String,
    },
    data() {
        return {
            serviceUnavailable: false,
            modalVisible: false,
            refreshToken: null,
            isExchangeMailSent: false,
            depositAddresses: {},
            addressesSignature: {},
        };
    },
    methods: {
        buyCrypto: function() {
            this.modalVisible = true;
            this.getRefreshToken();

            if (!this.mintmeExchangeMailSent && !this.isExchangeMailSent) {
                this.$axios.single.post(this.$routing.generate('send_exchange_mintme_mail'))
                    .then(() => {
                        this.isExchangeMailSent = true;
                    })
                    .catch((err) => {
                        this.$logger.error('Can not send exchange cryptos mail', err);
                    });
            }

            this.$axios.retry.get(this.$routing.generate('deposit_addresses_signature'))
                .then((res) => {
                    this.depositAddresses = res.data.addresses;
                    this.addressesSignature = res.data.signatures;
                })
                .catch((err) => {
                    this.$logger.error('Service unavailable. Can not update deposit data now.', err);
                    this.serviceUnavailable = true;
                });
        },
        getRefreshToken: function() {
            if (this.refreshToken) {
                return;
            }

            this.$axios.retry.get(this.$routing.generate('refresh_token'))
                .then((res) => this.refreshToken = res.data);
        },
    },
};
</script>
