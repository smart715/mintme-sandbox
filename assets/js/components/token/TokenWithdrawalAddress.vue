<template>
    <div>
        <div class="col-12 pb-0 px-0">
            <label for="tokenName" class="d-block text-left">
                New address:
            </label>
            <input
                id="address"
                type="text"
                v-model.trim="newAddress"
                ref="addressInput"
                class="w-100 px-2"
                :class="{ 'is-invalid': $v.$invalid }"
            >
            <div
                class="invalid-feedback"
                :class="{ 'd-block': $v.newAddress.$invalid }"
            >
                Wrong address
            </div>
            <label class="custom-control custom-checkbox pt-2">
                <input
                    v-model="preventEdition"
                    type="checkbox"
                    id="prevent-edition"
                    class="custom-control-input"
                >
                <label
                    class="custom-control-label"
                    for="prevent-edition">
                    Prevent another edition of withdrawal address.
                </label>
            </label>
            <p class="text-danger">
                If you check this box and lose access to address it will be impossible to change it or recover rest of tokens.
            </p>
        </div>
        <div class="col-12 px-0 clearfix">
            <button
                class="btn btn-primary float-left"
                :disabled="submitting"
                @click="editAddress"
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
            @verify="doEditAddress"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import TwoFactorModal from '../modal/TwoFactorModal';
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';
import {addressLength} from '../../utils/constants';

const HTTP_ACCEPTED = 202;
const addressContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);

export default {
    name: 'TokenWithdrawalAddress',
    components: {
        TwoFactorModal,
    },
    props: {
        tokenName: String,
        twofa: Boolean,
        withdrawalAddress: String,
    },
    data() {
        return {
            currentAddress: this.withdrawalAddress,
            newAddress: this.withdrawalAddress,
            preventEdition: false,
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
                this.newAddress = this.currentAddress;
            }
        },
        editAddress: function() {
            this.$v.$touch();
            if (this.currentAddress === this.newAddress) {
                this.closeModal();
                return;
            } else if (!this.$v.newAddress.addressContain) {
                this.$toasted.error('Withdrawal address can contain alphabets and numbers');
                return;
            } else if (!this.$v.newAddress.minLength) {
                this.$toasted.error(`Withdrawal address should have at least ${addressLength.WEB.min} symbols`);
                return;
            } else if (!this.$v.newAddress.maxLength) {
                this.$toasted.error(`Withdrawal address can not be longer than ${addressLength.WEB.max} characters`);
                return;
            }

            if (this.twofa) {
                this.showTwoFactorModal = true;
            } else {
                this.doEditAddress();
            }
        },
        doEditAddress: function(code = '') {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.post(this.$routing.generate('withdrawal_address_update', {
                name: this.tokenName,
            }), {
                address: this.newAddress,
                code: code,
                preventEdition: this.preventEdition,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        this.currentAddress = this.newAddress;
                        this.$toasted.success('Withdrawal address changed successfully');

                        this.showTwoFactorModal = false;
                        this.closeModal();

                        if (this.preventEdition) {
                            this.$emit('prevent-edition');
                        }
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
            newAddress: {
                required,
                addressContain,
                minLength: minLength(addressLength.WEB.min),
                maxLength: maxLength(addressLength.WEB.max),
            },
        };
    },
};
</script>

