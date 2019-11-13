<template>
    <div>
        <template v-if="btnDisabled">
            <span class="btn-cancel px-0 m-1 text-muted">
                Delete this token
            </span>
            <guide>
                <template slot="header">
                    Delete token
                </template>
                <template slot="body">
                    <p v-if="isTokenExchanged">
                        You need all your tokens to delete token.
                    </p>
                    <p v-else-if="!isTokenNotDeployed">
                        Token is deploying or deployed.
                    </p>
                </template>
            </guide>
        </template>
        <span
            v-else
            class="btn-cancel px-0 c-pointer m-1"
            @click="deleteToken"
        >
            Delete this token
        </span>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            @verify="doDeleteToken"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import Guide from '../Guide';
import TwoFactorModal from '../modal/TwoFactorModal';
import {NotificationMixin} from '../../mixins';

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenDelete',
    props: {
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
        tokenName: String,
        twofa: Boolean,
    },
    components: {
        Guide,
        TwoFactorModal,
    },
    data() {
        return {
            needToSendCode: !this.twofa,
            showTwoFactorModal: false,
        };
    },
    computed: {
        btnDisabled: function() {
            return this.isTokenExchanged || !this.isTokenNotDeployed;
        },
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        deleteToken: function() {
            this.showTwoFactorModal = true;

            if (!this.needToSendCode) {
                return;
            }

            this.sendConfirmCode();
        },
        doDeleteToken: function(code = '') {
            if (this.isTokenExchanged) {
                this.notifyError('You need all your tokens to delete token.');
                return;
            } else if (!this.isTokenNotDeployed) {
                this.notifyError('Token is deploying or deployed.');
                return;
            }

            this.$axios.single.post(this.$routing.generate('token_delete', {
                    name: this.tokenName,
                }), {
                    code: code,
                })
                .then((response) => {
                    if (HTTP_ACCEPTED === response.status) {
                        this.notifySuccess(response.data.message);
                        this.showTwoFactorModal = false;
                        location.href = this.$routing.generate('homepage');
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError('Network error');
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        if ('2fa code is expired' === error.response.data.message) {
                            this.sendConfirmCode();
                        }
                    } else {
                        this.notifyError('An error has occurred, please try again later');
                    }
                });
        },
        sendConfirmCode: function() {
            if (this.btnDisabled) {
                this.needToSendCode = false;
                return;
            }

            this.$axios.single.post(this.$routing.generate('token_send_code', {
                    name: this.tokenName,
                }))
                .then((response) => {
                    if (HTTP_ACCEPTED === response.status && null !== response.data.message) {
                        this.notifySuccess(response.data.message);
                        this.needToSendCode = false;
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError('Network error');
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('An error has occurred, please try again later');
                    }
                });
        },
    },
};
</script>

