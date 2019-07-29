<template>
    <div>
        <modal
            :visible="visible"
            no-close-on-backdrop
            @close="closeModal">
            <template slot="header">
                <span class="modal-title py-2 pl-4 d-inline-block">{{ currentName }}</span>
            </template>
            <template slot="body">
                <div class="col-12 pb-3">
                    <label for="tokenName" class="d-block text-left">
                        Edit your token name:
                    </label>
                    <input
                        id="tokenName"
                        type="text"
                        v-model.trim="newName"
                        ref="tokenNameInput"
                        class="token-name-input w-100 px-2"
                        :class="{ 'is-invalid': $v.$invalid }">
                </div>
                <div class="col-12 pt-2 clearfix">
                    <button
                        class="btn btn-primary float-left"
                        @click="editName">
                        Save
                    </button>
                    <span
                        class="btn-cancel pl-3 c-pointer float-left"
                        @click="closeModal">
                        <slot name="cancel">Cancel</slot>
                    </span>
                    <span
                        class="btn-cancel pl-3 c-pointer float-right"
                        @click="deleteToken">
                        Delete token
                    </span>
                </div>
            </template>
        </modal>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            @verify="doEditToken"
            @close="closeTwoFactorModal">
        </two-factor-modal>
    </div>
</template>

<script>
import TwoFactorModal from './TwoFactorModal';
import Modal from './Modal';
import Guide from '../Guide';
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';

const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);
const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenEditModal',
    components: {
        Guide,
        Modal,
        TwoFactorModal,
    },
    props: {
        currentName: String,
        deleteUrl: String,
        sendCodeUrl: String,
        twofa: Boolean,
        updateUrl: String,
        visible: Boolean,
    },
    data() {
        return {
            minLength: 4,
            mode: null,
            needToSendCode: !this.twofa,
            newName: this.currentName,
            showTwoFactorModal: false,
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
                this.cancelEditingMode();
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
            this.$axios.single.patch(this.updateUrl, {
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
                    if (!error.response) {
                        this.$toasted.error('Network error');
                    } else if (error.response.data.message) {
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
        },
        doDeleteToken: function(code = '') {
            this.$axios.single.post(this.deleteUrl, {
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

