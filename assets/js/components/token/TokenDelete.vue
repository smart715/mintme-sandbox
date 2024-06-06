<template>
    <div v-b-tooltip.hover="tooltipConfig">
        <template v-if="!loaded || btnDisabled">
            <span class="btn-cancel px-0 m-1 text-muted pointer-events-none">
                {{ $t('token.delete.delete_token') }}
            </span>
            <span v-if="!loaded && !serviceUnavailable">
                <div class="spinner-border spinner-border-sm" role="status"></div>
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
        <m-button
            v-else
            type="primary"
            @click="deleteToken"
            :loading="isDeleting"
        >
            <template v-slot:prefix>
                <font-awesome-icon :icon="['far', 'window-close']"/>
            </template>
            <span class="ml-1">{{ $t('token.delete.delete_token') }}</span>
        </m-button>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            :loading="isDeleting"
            @verify="doDeleteToken"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import Guide from '../Guide';
import TwoFactorModal from '../modal/TwoFactorModal';
import {NotificationMixin} from '../../mixins';
import {HTTP_OK} from '../../utils/constants';
import {mapGetters} from 'vuex';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {faWindowClose} from '@fortawesome/fontawesome-free-regular';
import {MButton} from '../UI';
import {VBTooltip} from 'bootstrap-vue';

library.add(faCircleNotch, faWindowClose);

export default {
    name: 'TokenDelete',
    mixins: [NotificationMixin],
    props: {
        isTokenNotDeployed: Boolean,
        tokenName: String,
        twofa: Boolean,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    components: {
        Guide,
        TwoFactorModal,
        FontAwesomeIcon,
        MButton,
    },
    data() {
        return {
            needToSendCode: !this.twofa,
            showTwoFactorModal: false,
            soldOnMarket: null,
            isTokenOverDeleteLimit: null,
            serviceUnavailable: false,
            isDeleting: false,
        };
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('token_over_delete_limit', {name: this.tokenName}))
            .then((res) => this.isTokenOverDeleteLimit = res.data)
            .catch((err) => {
                this.serviceUnavailable = true;
                this.$logger.error('Can not get tokens in curculation', err);
            });
    },
    computed: {
        ...mapGetters('tokenStatistics', {
            tokenDeleteSoldLimit: 'getTokenDeleteSoldLimit',
        }),
        tooltipConfig: function(data) {
            return this.serviceUnavailable
                ? {title: this.$t('toasted.error.service_unavailable_short'), boundary: 'viewport'}
                : null;
        },
        btnDisabled: function() {
            return this.isTokenOverDeleteLimit
                || !this.isTokenNotDeployed
                || this.serviceUnavailable
            ;
        },
        loaded: function() {
            return null !== this.tokenDeleteSoldLimit
                && null !== this.isTokenOverDeleteLimit;
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

            this.isDeleting = true;
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
                })
                .catch((error) => {
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.$logger.error('Delete token network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.$logger.error('Can not delete token', error);
                        if ('2fa code is expired' === error.response.data.message) {
                            this.sendConfirmCode();
                        }
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.$logger.error('An error has occurred, please try again later', error);
                    }
                })
                .finally(() => {
                    this.isDeleting = false;
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
                        this.$logger.error('Send confirm code network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.$logger.error('Can not send confirm code', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.$logger.error('An error has occurred, please try again later', error);
                    }
                });
        },
    },
};
</script>

