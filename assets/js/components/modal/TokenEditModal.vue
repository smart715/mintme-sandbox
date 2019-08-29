<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            @close="closeModal">
            <template slot="header">
                <span class="modal-title py-2 pl-4 d-inline-block">{{ currentName | truncate(25) }}</span>
            </template>
            <template slot="body">
                <div class="col-12 pb-3">
                    <label for="tokenName" class="d-block text-left">
                        Edit your token name:
                    </label>
                    <input
                        id="tokenName"
                        type="text"
                        v-model="newName"
                        ref="tokenNameInput"
                        class="token-name-input token-name-font w-100 px-2"
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
            :no-close="noClose"
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
import {FiltersMixin} from '../../mixins';

const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);
const HTTP_ACCEPTED = 202;
const HTTP_BAD_REQUEST = 400;

const customTrimmer = (text) => {
    const mask = ' -';
    const preText = text;

    while (~mask.indexOf(text[0])) {
        text = text.slice(1);
    }
    while (~mask.indexOf(text[text.length - 1])) {
        text = text.slice(0, -1);
    }

    return (preText === text ? text : customTrimmer(text));
};

export default {
    name: 'TokenEditModal',
    components: {
        Guide,
        Modal,
        TwoFactorModal,
    },
    props: {
        currentName: String,
        noClose: Boolean,
        twofa: Boolean,
        visible: Boolean,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            minLength: 4,
            maxLength: 60,
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
            if (this.currentName === this.newName || customTrimmer(this.newName) == this.currentName) {
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
                maxLength: maxLength(this.maxLength),
                customTrimmer,
            },
        };
    },
};
</script>

