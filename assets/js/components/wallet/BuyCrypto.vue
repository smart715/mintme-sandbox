<template>
    <div class="buy-crypto flex">
        <div class="d-flex align-items-center">
            <button
                    class="btn btn-primary"
                    @click="buyCrypto"
            >
                {{ $t('wallet.buy_crypto') }}
            </button>
            <font-awesome-icon
                class="ml-2"
                :icon="{prefix: 'fab', iconName: 'cc-visa'}"
                size="2x"
            />
            <font-awesome-icon
                class="ml-2"
                :icon="{prefix: 'fab', iconName: 'cc-mastercard'}"
                size="2x"
            />
            <img
                class="ml-2 img-rounded"
                src="../../../img/sepa-icon.svg"
                alt="visa icon"
            >
        </div>
        <buy-crypto-modal
            :visible="modalVisible"
            :ui-url="coinifyUiUrl"
            :partner-id="coinifyPartnerId"
            :crypto-currencies="coinifyCryptoCurrencies"
            :addresses="addresses"
            :addresses-signature="addressesSignature"
            :refresh-token="refreshToken"
            :predefined-tokens="predefinedTokens"
            @close="modalVisible = false"
        />
    </div>
</template>

<script>
import BuyCryptoModal from '../modal/BuyCryptoModal';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCcVisa, faCcMastercard} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faCcVisa, faCcMastercard);

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
        addresses: Object,
        addressesSignature: Object,
        predefinedTokens: Array,
        mintmeExchangeMailSent: Boolean,
    },
    data() {
        return {
            modalVisible: false,
            refreshToken: null,
        };
    },
    methods: {
        buyCrypto: function() {
            this.modalVisible = true;
            this.getRefreshToken();

            if (!this.mintmeExchangeMailSent) {
                this.$axios.retry.get(this.$routing.generate('send_exchange_mintme_mail'))
                    .catch((err) => {
                            this.sendLogs('error', 'Can not sent exchange cryptos mail', err);
                        });
            }
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
