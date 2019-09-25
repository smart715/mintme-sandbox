<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :without-padding="true"
            @close="$emit('close')"
        >
            <template slot="header">
                <span class="modal-title py-2 pl-4 d-inline-block">{{ currentName | truncate(25) }}</span>
            </template>
            <template slot="body">
                <div class="token-edit p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <faq-item>
                            <template slot="title">
                                Change token name
                            </template>
                            <template slot="body">
                                <token-change-name
                                    :is-token-exchanged="isTokenExchanged"
                                    :is-token-not-deployed="isTokenNotDeployed"
                                    :current-name="currentName"
                                    :twofa="twofa"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div
                        v-if="!minDestinationLocked"
                        class="row faq-block mx-0 border-bottom"
                        ref="withdrawal-address"
                    >
                        <faq-item>
                            <template slot="title">
                                Modify token withdrawal address
                            </template>
                            <template slot="body">
                                <token-withdrawal-address
                                    :is-token-deployed="isTokenDeployed"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                    :withdrawal-address="withdrawalAddress"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item @switch="refreshSliders">
                            <template slot="title">
                                Token release period
                            </template>
                            <template slot="body">
                                <token-release-period
                                    ref="token-release-period-component"
                                    :is-token-exchanged="isTokenExchanged"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                    @update="releasePeriodUpdated"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <template slot="title">
                                Deploy token to blockchain
                            </template>
                            <template slot="body">
                                <token-deploy
                                    :has-release-period="hasReleasePeriod"
                                    :is-owner="isOwner"
                                    :twofa="twofa"
                                    :name="currentName"
                                    :precision="precision"
                                    :status-prop="statusProp"
                                    :websocket-url="websocketUrl"
                                    @pending="$emit('token-deploy-pending')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0">
                        <faq-item>
                            <template slot="title">
                                Delete token
                            </template>
                            <template slot="body">
                                <token-delete
                                    :is-token-exchanged="isTokenExchanged"
                                    :is-token-not-deployed="isTokenNotDeployed"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                />
                            </template>
                        </faq-item>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import FaqItem from '../FaqItem';
import Guide from '../Guide';
import Modal from './Modal';
import TokenChangeName from '../token/TokenChangeName';
import TokenDelete from '../token/TokenDelete';
import TokenDeploy from '../token/deploy/TokenDeploy';
import TokenReleasePeriod from '../token/TokenReleasePeriod';
import TokenWithdrawalAddress from '../token/TokenWithdrawalAddress';
import TwoFactorModal from './TwoFactorModal';
import {FiltersMixin} from '../../mixins';
<<<<<<< HEAD
import {tokenDeploymentStatus} from '../../utils/constants';
=======

const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);
const HTTP_ACCEPTED = 202;
const HTTP_BAD_REQUEST = 400;
>>>>>>> 4119b52f2d7e076f14ba5ef997886c722175c62a

export default {
    name: 'TokenEditModal',
    components: {
        FaqItem,
        Guide,
        Modal,
        TokenChangeName,
        TokenDelete,
        TokenDeploy,
        TokenReleasePeriod,
        TokenWithdrawalAddress,
        TwoFactorModal,
    },
    props: {
        currentName: String,
        hasReleasePeriodProp: Boolean,
        isOwner: Boolean,
        isTokenExchanged: Boolean,
        noClose: Boolean,
        minDestinationLocked: Boolean,
        precision: Number,
        statusProp: String,
        twofa: Boolean,
        visible: Boolean,
        websocketUrl: String,
        withdrawalAddress: String,
    },
    mixins: [FiltersMixin],
    data() {
        return {
<<<<<<< HEAD
            hasReleasePeriod: this.hasReleasePeriodProp,
=======
            minLength: 4,
            maxLength: 60,
            mode: null,
            needToSendCode: !this.twofa,
            newName: this.currentName,
            showTwoFactorModal: false,
>>>>>>> 4119b52f2d7e076f14ba5ef997886c722175c62a
        };
    },
    computed: {
        isTokenNotDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.statusProp;
        },
        isTokenDeployed: function() {
            return tokenDeploymentStatus.deployed === this.statusProp;
        },
<<<<<<< HEAD
    },
    methods: {
        releasePeriodUpdated: function() {
            this.hasReleasePeriod = true;
=======
        trimName: function(name) {
              return name.replace(/^[\s\-]+/, '').replace(/[\s\-]+$/, '');
        },
        editName: function() {
            this.$v.$touch();
            if (this.currentName === this.newName || this.trimName(this.newName) === this.currentName) {
                this.closeModal();
                return;
            } else if (!this.newName || this.newName.replace(/-/g, '').length === 0) {
                this.$toasted.error('Token name shouldn\'t be blank');
                return;
            } else if (!this.$v.newName.tokenContain) {
                this.$toasted.error('Token name can contain alphabets, numbers, spaces and dashes');
                return;
            } else if (!this.$v.newName.minLength || this.newName.replace(/-/g, '').length < this.minLength) {
                this.$toasted.error('Token name should have at least 4 symbols');
                return;
            } else if (!this.$v.newName.maxLength) {
                this.$toasted.error('Token name can not be longer than 60 characters');
                return;
            }

            if (this.twofa) {
                this.mode = 'edit';
                this.showTwoFactorModal = true;
            } else {
                this.doEditName();
            }
        },
        doEditName: function(code = '') {
            this.$axios.single.patch(this.$routing.generate('token_update', {
                    name: this.currentName,
                }), {
                    name: this.newName,
                    code: code,
                })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        this.currentName = response.data['tokenName'];

                        this.showTwoFactorModal = false;
                        this.closeModal();

                        // TODO: update name in a related components and link path instead of redirecting
                        location.href = this.$routing.generate('token_show', {
                            name: this.currentName,
                        });
                    }
                }, (error) => {
                    if (error.response.status === HTTP_BAD_REQUEST) {
                        this.$toasted.error(error.response.data.message);
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                });
        },
        deleteToken: function() {
            this.mode = 'delete';
            this.showTwoFactorModal = true;

            if (!this.needToSendCode) {
                return;
            }

            this.sendConfirmCode();
        },
        doDeleteToken: function(code = '') {
            this.$axios.single.post(this.$routing.generate('token_delete', {
                    name: this.currentName,
                }), {
                    code: code,
                })
                .then((response) => {
                    if (HTTP_ACCEPTED === response.status) {
                        this.$toasted.success(response.data.message);
                        this.showTwoFactorModal = false;
                        location.href = this.$routing.generate('homepage');
                    }
                }, (error) => {
                    if (!error.response) {
                        this.$toasted.error('Network error');
                    } else if (error.response.data.message) {
                        this.$toasted.error(error.response.data.message);
                        if ('2fa code is expired' === error.response.data.message) {
                            this.sendConfirmCode();
                        }
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                });
        },
        doEditToken: function(code = '') {
            if ('delete' === this.mode) {
                this.doDeleteToken(code);
            } else if ('edit' === this.mode) {
                this.doEditName(code);
            }
>>>>>>> 4119b52f2d7e076f14ba5ef997886c722175c62a
        },
        refreshSliders: function() {
            this.$refs['token-release-period-component'].$refs['released-slider'].refresh();
            this.$refs['token-release-period-component'].$refs['release-period-slider'].refresh();
        },
    },
<<<<<<< HEAD
=======
    validations() {
        return {
            newName: {
                required,
                tokenContain: tokenContain,
                minLength: minLength(this.minLength),
                maxLength: maxLength(this.maxLength),
                customTrimmer: this.trimName,
            },
        };
    },
>>>>>>> 4119b52f2d7e076f14ba5ef997886c722175c62a
};
</script>

