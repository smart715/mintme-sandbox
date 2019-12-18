<template>
    <div>
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
    addressContain,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
} from '../../utils/constants';
import {NotificationMixin} from '../../mixins';

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenChangeName',
    mixins: [NotificationMixin],
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
        };
    },
    computed: {
        btnDisabled: function() {
            return this.submitting || this.isTokenExchanged || !this.isTokenNotDeployed;
        },
    },
    watch: {
        newName: function() {
            if (this.newName.replace(/-|\s/g, '').length === 0) {
                this.newName = '';
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
            if (this.currentName === this.newName) {
                this.notifyError('You didn\'t change the token name');
                return;
            } else if (this.isTokenExchanged) {
                this.notifyError('You need all your tokens to change token\'s name');
                return;
            } else if (!this.isTokenNotDeployed) {
                this.notifyError('Token is deploying or deployed.');
                return;
            } else if (!this.newName) {
                this.notifyError('Token name shouldn\'t be blank');
                return;
            } else if (!this.$v.newName.validFirstChars) {
                this.notifyError('Token name can not contain spaces or dashes in the beginning');
                return;
            } else if (!this.$v.newName.validLastChars) {
                this.notifyError('Token name can not contain spaces or dashes in the end');
                return;
            } else if (!this.$v.newName.noSpaceBetweenDashes) {
                this.notifyError('Token name can not contain space between dashes');
                return;
            } else if (!this.$v.newName.addressContain) {
                this.notifyError('Token name can contain alphabets, numbers, spaces and dashes');
                return;
            } else if (!this.$v.newName.minLength) {
                this.notifyError('Token name should have at least 4 symbols');
                return;
            } else if (!this.$v.newName.maxLength) {
                this.notifyError('Token name can not be longer than 60 characters');
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
                } else if (error.response.data.message) {
                    this.notifyError(error.response.data.message);
                } else {
                    this.notifyError('An error has occurred, please try again later');
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
                addressContain,
                validFirstChars: tokenValidFirstChars,
                validLastChars: tokenValidLastChars,
                noSpaceBetweenDashes: tokenNoSpaceBetweenDashes,
                minLength: minLength(this.minLength),
                maxLength: maxLength(this.maxLength),
            },
        };
    },
};
</script>

