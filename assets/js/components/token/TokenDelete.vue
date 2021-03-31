<template>
    <div>
        <template v-if="!loaded || btnDisabled">
            <span class="btn-cancel px-0 m-1 text-muted pointer-events-none">
                {{ $t('token.delete.delete_token') }}
            </span>
            <span v-if="!loaded">
                <font-awesome-icon
                    icon="circle-notch"
                    spin class="loading-spinner"
                    fixed-width
                />
            </span>
            <guide v-else>
                <template slot="header">
                    {{ $t('token.delete.header') }}
                </template>
                <template slot="body">
                    <p v-if="isTokenOverDeleteLimit">
                    {{ $t('token.delete.body.over_limit', {limit: tokenDeleteSoldLimit}) }}
                    </p>
                    <p v-else-if="!isTokenNotDeployed">
                        {{ $t('token.delete.body.deploying_or_deployed') }}
                    </p>
                </template>
            </guide>
        </template>
        <span
            v-else
            class="btn-cancel px-0 c-pointer m-1"
            @click="deleteToken"
        >
            {{ $t('token.delete.delete_token') }}
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
import {LoggerMixin, NotificationMixin} from '../../mixins';
import {HTTP_OK} from '../../utils/constants';
import {mapGetters} from 'vuex';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'TokenDelete',
    mixins: [NotificationMixin, LoggerMixin],
    props: {
        isTokenNotDeployed: Boolean,
        tokenName: String,
        twofa: Boolean,
    },
    components: {
        Guide,
        TwoFactorModal,
        FontAwesomeIcon,
    },
    data() {
        return {
            needToSendCode: !this.twofa,
            showTwoFactorModal: false,
            soldOnMarket: null,
            isTokenOverDeleteLimit: null,
        };
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('token_over_delete_limit', {name: this.tokenName}))
            .then((res) => this.isTokenOverDeleteLimit = res.data)
            .catch((err) => {
              this.sendLogs('error', 'Can not get tokens in curculation', err);
            });
    },
    computed: {
        ...mapGetters('tokenStatistics', {
            tokenDeleteSoldLimit: 'getTokenDeleteSoldLimit',
        }),
        btnDisabled: function() {
            return this.isTokenOverDeleteLimit || !this.isTokenNotDeployed;
        },
        loaded: function() {
            return null !== this.tokenDeleteSoldLimit &&
                null !== this.isTokenOverDeleteLimit;
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
            if (this.isTokenOverDeleteLimit) {
                this.notifyError(this.$t('token.delete.body.over_limit', {limit: this.tokenDeleteSoldLimit}));
                return;
            } else if (!this.isTokenNotDeployed) {
                this.notifyError(this.$t('token.delete.body.deploying_or_deployed'));
                return;
            }

            this.$axios.single.post(this.$routing.generate('token_delete', {
                    name: this.tokenName,
                }), {
                    code: code,
                })
                .then((response) => {
                    if (HTTP_OK === response.status) {
                        this.notifySuccess(response.data.message);
                        this.showTwoFactorModal = false;
                        location.href = this.$routing.generate('homepage');
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.sendLogs('error', 'Delete token network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.sendLogs('error', 'Can not delete token', error);
                        if ('2fa code is expired' === error.response.data.message) {
                            this.sendConfirmCode();
                        }
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.sendLogs('error', 'An error has occurred, please try again later', error);
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
                    if (HTTP_OK === response.status && null !== response.data.message) {
                        this.notifySuccess(response.data.message);
                        this.needToSendCode = false;
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.sendLogs('error', 'Send confirm code network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.sendLogs('error', 'Can not send confirm code', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.sendLogs('error', 'An error has occurred, please try again later', error);
                    }
                });
        },
    },
};
</script>

