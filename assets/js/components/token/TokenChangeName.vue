<template>
    <div>
        <div v-if="isTokenExchanged || !isTokenNotDeployed" id="error-message" class="bg-danger text-white text-center py-2 mb-3">
            {{ errorMessage }}
        </div>
        <div class="col-12 pb-3 px-0">
            <label for="tokenName" class="d-block text-left">
                Edit your token name:
            </label>
            <input
                id="tokenName"
                type="text"
                v-model="newName"
                ref="tokenNameInput"
                class="token-name-input w-100 px-2"
                :class="{ 'is-invalid': $v.$invalid }"
            >
            <div v-cloak v-if="!$v.newName.validChars" class="text-danger text-center">
                Token name can contain only alphabets, numbers, spaces and dashes
            </div>
            <div v-cloak v-if="newName.length > 0 && (!$v.newName.validFirstChars || !$v.newName.validLastChars || !$v.newName.noSpaceBetweenDashes)" class="text-danger text-center">
                Token name can't start or end with a dash or space, or have spaces between dashes
            </div>
            <div v-cloak v-if="!$v.newName.minLength" class="text-danger text-center">
                Token name should have at least 4 symbols
            </div>
            <div v-cloak v-if="!$v.newName.maxLength" class="text-danger text-center">
                Token name can't be longer than 255 characters
            </div>
            <div v-cloak v-if="this.currentName === this.newName && !this.isTokenExchanged && this.isTokenNotDeployed" class="text-danger text-center">
                You didn't change the token name
            </div>
            <div v-cloak v-if="!this.newName && !this.isTokenExchanged && this.isTokenNotDeployed" class="text-danger text-center">
                Token name shouldn't be blank
            </div>
            <div v-cloak v-if="newNameExists" class="text-danger text-center">
                Token name is already taken
            </div>
        </div>
        <div class="col-12 pt-2 px-0 clearfix">
            <button
                class="btn btn-primary float-left"
                :disabled="btnDisabled"
                @click="editName"
            >
                Save
            </button>
        </div>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            @verify="doEditName"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import TwoFactorModal from '../modal/TwoFactorModal';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {
    HTTP_OK,
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
} from '../../utils/constants';
import {LoggerMixin, NotificationMixin} from '../../mixins';

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenChangeName',
    mixins: [NotificationMixin, LoggerMixin],
    components: {
        TwoFactorModal,
    },
    props: {
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
        currentName: String,
        twofa: Boolean,
    },
    data() {
        return {
            minLength: 4,
            maxLength: 60,
            newName: this.currentName,
            showTwoFactorModal: false,
            submitting: false,
            newNameExists: false,
            newNameProcessing: false,
            newNameTimeout: null,
        };
    },
    computed: {
        btnDisabled: function() {
            return this.submitting || this.isTokenExchanged || !this.isTokenNotDeployed ||this.currentName === this.newName || this.$v.$invalid || this.newNameProcessing || this.newNameExists;
        },
        errorMessage: function() {
            let message = '';

            if (!this.isTokenNotDeployed) {
                message = 'The name of a deployed token can\'t be changed';
            } else if (this.isTokenExchanged) {
                message = 'You must own all your tokens in order to change the token\'s name';
            }

            return message;
        },
    },
    watch: {
        newName: function() {
            clearTimeout(this.newNameTimeout);
            if (this.newName.replace(/-|\s/g, '').length === 0) {
                this.newName = '';
            }
            this.newNameExists = false;

            if (!this.$v.$invalid && this.newName) {
                this.newNameProcessing = true;
                this.newNameTimeout = setTimeout(() => {
                    this.$axios.single.get(this.$routing.generate('check_token_name_exists', {name: this.newName}))
                        .then((response) => {
                            if (HTTP_OK === response.status) {
                                this.newNameExists = response.data.exists;
                            }
                        }, (error) => {
                            this.notifyError('An error has occurred, please try again later');
                        })
                        .then(() => {
                            this.newNameProcessing = false;
                        });
                }, 2000);
            }
        },
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        closeModal: function() {
            this.cancelEditingMode();
        },
        cancelEditingMode: function() {
            if (!this.showTwoFactorModal) {
                this.$v.$reset();
                this.newName = this.currentName;
            }
        },
        editName: function() {
            this.$v.$touch();
            if (this.isTokenExchanged) {
                this.notifyError('You need all your tokens to change token\'s name');
                return;
            } else if (!this.isTokenNotDeployed) {
                this.notifyError('Token is deploying or deployed.');
                return;
            }
            if (this.twofa) {
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
                    this.notifySuccess('Token\'s name changed successfully');

                    this.showTwoFactorModal = false;
                    this.closeModal();

                    // TODO: update name in a related components and link path instead of redirecting
                    location.href = this.$routing.generate('token_show', {
                        name: this.currentName,
                    });
                }
            }, (error) => {
                if (!error.response) {
                    this.notifyError('Network error');
                    this.sendLogs('error', 'Edit name network error', error);
                } else if (error.response.data.message) {
                    this.notifyError(error.response.data.message);
                    this.sendLogs('error', 'Can not edit name', error);
                } else {
                    this.notifyError('An error has occurred, please try again later');
                    this.sendLogs('error', 'An error has occurred, please try again later', error);
                }
            })
            .then(() => {
                this.submitting = false;
            });
        },
    },
    validations() {
        return {
            newName: {
                required,
                validFirstChars: (value) => !tokenValidFirstChars(value),
                validLastChars: (value) => !tokenValidLastChars(value),
                noSpaceBetweenDashes: (value) => !tokenNoSpaceBetweenDashes(value),
                validChars: tokenNameValidChars,
                minLength: minLength(this.minLength),
                maxLength: maxLength(this.maxLength),
            },
        };
    },
};
</script>

