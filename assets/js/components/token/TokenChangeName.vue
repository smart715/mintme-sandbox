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
                :disabled="submitting || isTokenExchanged"
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
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';

const HTTP_ACCEPTED = 202;
const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);

export default {
    name: 'TokenChangeName',
    components: {
        TwoFactorModal,
    },
    props: {
        isTokenExchanged: Boolean,
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
                tokenContain: tokenContain,
                minLength: minLength(this.minLength),
                maxLength: maxLength(60),
            },
        };
    },
};
</script>

