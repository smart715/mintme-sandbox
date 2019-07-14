<template>
    <div>
        <template v-if="isTokenExchanged">
            <span class="btn px-1 py-0">
                <img src="../../../img/x-icon-grey.png" alt="x" style="height: 18px;" class="d-block my-auto">
            </span>
            <guide class="float-right">
                <div slot="header">Token deletion</div>
                <template slot="body">
                    To delete your token, you need to have all released tokens in your possession and no open sell orders.
                </template>
            </guide>
        </template>
        <template v-else>
            <button class="btn px-1 py-0" @click="openTwoFactorModal">
                <img src="../../../img/x-icon.png" alt="x" style="height: 18px;" class="d-block my-auto">
            </button>
        </template>
        <two-factor-modal
            :visible="showTwoFactorModal"
            @verify="deleteToken"
            @close="closeTwoFactorModal"
            />
    </div>
</template>

<script>

import Guide from '../Guide';
import TwoFactorModal from '../modal/TwoFactorModal';

Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenDelete',
    props: {
        name: String,
        sendCodeUrl: String,
        deleteUrl: String,
        twofaEnabled: String,
    },
    components: {
        Guide,
        TwoFactorModal,
    },
    data() {
        return {
            isTokenExchanged: true,
            showTwoFactorModal: false,
            needToSendCode: true,
        };
    },
    methods: {
        openTwoFactorModal: function() {
            if (this.needToSendCode) {
                this.$axios.single.post(this.sendCodeUrl)
                    .then((response) => {
                        if (HTTP_ACCEPTED === response.status && null !== response.data.message) {
                            this.$toasted.success(response.data.message);
                            this.needToSendCode = false;
                        }
                    }, (error) => {
                        if (!error.response) {
                            this.$toasted.error('Network error');
                        } else if (error.response.data.message) {
                            this.$toasted.error(error.response.data.message);
                        } else {
                            this.$toasted.error('An error has occurred, please try again later');
                        }
                    });
            }
            this.showTwoFactorModal = true;
        },
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        checkIfTokenExchanged: function() {
            this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {
                name: this.name,
            }))
                .then((res) => this.isTokenExchanged = res.data)
                .catch(() => this.$toasted.error('Can not fetch token data now. Try later'));
        },
        deleteToken: function(code = '') {
            this.$axios.single.post(this.deleteUrl, {
                'code': code,
            })
                .then((response) => {
                    if (HTTP_ACCEPTED === response.status) {
                        this.$toasted.success(response.data.message);
                        this.closeTwoFactorModal();
                    }
                }, (error) => {
                    if (!error.response) {
                        this.$toasted.error('Network error');
                    } else if (error.response.data.message) {
                        this.$toasted.error(error.response.data.message);
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                });
        },
    },
    mounted: function() {
        this.needToSendCode = 'false' === this.twofaEnabled;
        this.checkIfTokenExchanged();
    },
};
</script>
