<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :without-padding="true"
            @close="closeModal"
        >
            <template slot="header">
                <span class="modal-title py-2 pl-4 d-inline-block">{{ currentName | truncate(25) }}</span>
            </template>
            <template slot="body">
                <div class="token-edit p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <faq-item>
                            <span slot="title">
                                Change token name
                            </span>
                            <span slot="body">
                                <div class="col-12 pb-3 px-0">
                                    <label for="tokenName" class="d-block text-left">
                                        Edit your token name:
                                    </label>
                                    <input
                                        id="tokenName"
                                        type="text"
                                        v-model.trim="newName"
                                        ref="tokenNameInput"
                                        class="token-name-input w-100 px-2"
                                        :class="{ 'is-invalid': $v.$invalid }"
                                    >
                                </div>
                                <div class="col-12 pt-2 px-0 clearfix">
                                    <button
                                        class="btn btn-primary float-left"
                                        :disabled="submitting"
                                        @click="editName"
                                    >
                                        Save
                                    </button>
                                    <span
                                        class="btn-cancel pl-3 c-pointer float-left"
                                        @click="closeModal"
                                    >
                                        <slot name="cancel">Cancel</slot>
                                    </span>
                                </div>
                            </span>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <span slot="title">
                                Modify token withdrawal address
                            </span>
                            <span slot="body">
                                Modify token withdrawal address
                            </span>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <span slot="title">
                                Token release period
                            </span>
                            <span slot="body">
                                <token-release-period
                                    :token-name="currentName"
                                    :twofa="twofa"
                                    @cancel="closeModal"
                                />
                            </span>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <span slot="title">
                                Deploy token to blockchain
                            </span>
                            <span slot="body">
                                Deploy token to blockchain
                            </span>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0">
                        <faq-item>
                            <span slot="title">
                                Delete token
                            </span>
                            <span slot="body">
                                <span
                                    class="btn-cancel px-0 c-pointer m-1"
                                    @click="deleteToken"
                                >
                                    Delete this token
                                </span>
                            </span>
                        </faq-item>
                    </div>
                </div>
            </template>
        </modal>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            :no-close="noClose"
            @verify="doEditToken"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import FaqItem from '../FaqItem';
import Guide from '../Guide';
import Modal from './Modal';
import TokenReleasePeriod from '../token/introduction/TokenIntroductionReleasePeriod';
import TwoFactorModal from './TwoFactorModal';
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';
import {FiltersMixin} from '../../mixins';

const HTTP_ACCEPTED = 202;
const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);

export default {
    name: 'TokenEditModal',
    components: {
        FaqItem,
        Guide,
        Modal,
        TokenReleasePeriod,
        TwoFactorModal,
    },
    props: {
        isTokenExchanged: Boolean,
        noClose: Boolean,
        currentName: String,
        twofa: Boolean,
        visible: Boolean,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            minLength: 4,
            mode: null,
            needToSendCode: !this.twofa,
            newName: this.currentName,
            showTwoFactorModal: false,
            submitting: false,
        };
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        closeModal: function() {
            this.cancelEditingMode();
            this.$emit('close');
        },
        cancelEditingMode: function() {
            if (!this.showTwoFactorModal) {
                this.$v.$reset();
                this.newName = this.currentName;
                this.mode = null;
            }
        },
        editName: function() {
            this.$v.$touch();
            if (this.currentName === this.newName) {
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
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.patch(this.$routing.generate('token_update', {
                name: this.currentName,
            }), {
                name: this.newName,
                code: code,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        this.currentName = response.data['tokenName'];
                        this.$toasted.success('Token\'s name changed successfully');

                        this.showTwoFactorModal = false;
                        this.closeModal();

                        // TODO: update name in a related components and link path instead of redirecting
                        location.href = this.$routing.generate('token_show', {
                            name: this.currentName,
                        });
                    }
                }, (error) => {
                    if (!error.response) {
                        this.$toasted.error('Network error');
                    } else if (error.response.data.message) {
                        this.$toasted.error(error.response.data.message);
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                })
                .then(() => {
                    this.submitting = false;
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
        },
        sendConfirmCode: function() {
            this.$axios.single.post(this.$routing.generate('token_send_code', {
                    name: this.currentName,
                }))
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
        },
    },
    validations() {
        return {
            newName: {
                required,
                tokenContain: tokenContain,
                minLength: minLength(this.minLength),
                maxLength: maxLength(60),
            },
        };
    },
};
</script>

