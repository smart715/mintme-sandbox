<template>
    <div>
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
    tokenFirstValidChars,
    tokenEndValidChars,
    tokenNoSpaceBetweenDashes,
} from '../../utils/constants';

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenChangeName',
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
                this.$toasted.error('You didn\'t change the token name');
                return;
            } else if (this.isTokenExchanged) {
                this.$toasted.error('You need all your tokens to change token\'s name');
                return;
            } else if (!this.isTokenNotDeployed) {
                this.$toasted.error('Token is deploying or deployed.');
                return;
            } else if (!this.newName) {
                this.$toasted.error('Token name shouldn\'t be blank');
                return;
            } else if (!this.$v.newName.validFirstChars) {
                this.$toasted.error('Token name can not contain spaces or dashes in the beginning');
                return;
            } else if (!this.$v.newName.validEndChars) {
                this.$toasted.error('Token name can not contain spaces or dashes in the end');
                return;
            } else if (!this.$v.newName.noSpaceBetweenDashes) {
                this.$toasted.error('Token name can not contain space between dashes');
                return;
            } else if (!this.$v.newName.addressContain) {
                this.$toasted.error('Token name can contain alphabets, numbers, spaces and dashes');
                return;
            } else if (!this.$v.newName.minLength) {
                this.$toasted.error('Token name should have at least 4 symbols');
                return;
            } else if (!this.$v.newName.maxLength) {
                this.$toasted.error('Token name can not be longer than 60 characters');
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
    },
    validations() {
        return {
            newName: {
                required,
                addressContain,
                validFirstChars: tokenFirstValidChars,
                validEndChars: tokenEndValidChars,
                noSpaceBetweenDashes: tokenNoSpaceBetweenDashes,
                minLength: minLength(this.minLength),
                maxLength: maxLength(60),
            },
        };
    },
};
</script>

