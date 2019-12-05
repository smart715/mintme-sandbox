<template>
    <div v-if="canUpdate">
        <div class="col-12 pb-3 px-0">
            <label for="address" class="d-block text-left">
                New address:
            </label>
            <input
                id="address"
                type="text"
                v-model.trim="$v.newAddress.$model"
                class="form-control"
                :class="{ 'is-invalid': $v.newAddress.$error }"
            >
            <div v-if="$v.newAddress.$error" class="invalid-feedback">
                Wrong address
            </div>
        </div>
        <div class="col-12 pt-2 px-0 clearfix">
            <button
                class="btn btn-primary float-left"
                :disabled="submitting"
                @click="editAddress"
            >
                Save
            </button>
        </div>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            @verify="doEditAddress"
            @close="closeTwoFactorModal"
        />
    </div>
    <div
        v-else-if="!isTokenDeployed"
        class="text-left"
    >
        <p class="bg-info m-0 py-1 px-3">
            Token is not deployed yet.
        </p>
    </div>
    <div
        v-else
        class="text-left"
    >
        <p class="bg-info m-0 py-1 px-3">
            Updating address is pending.
        </p>
    </div>
</template>

<script>
import TwoFactorModal from '../modal/TwoFactorModal';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {addressLength, addressContain} from '../../utils/constants';

export default {
    name: 'TokenReleaseAddress',
    components: {
        TwoFactorModal,
    },
    props: {
        isTokenDeployed: Boolean,
        tokenName: String,
        twofa: Boolean,
        releaseAddress: String,
    },
    data() {
        return {
            currentAddress: this.releaseAddress,
            locked: false,
            newAddress: this.releaseAddress,
            showTwoFactorModal: false,
            submitting: false,
        };
    },
    computed: {
        canUpdate: function() {
            return this.isTokenDeployed && '0x' !== this.currentAddress;
        },
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        closeModal: function() {
            this.cancelEditingMode();
        },
        setUpdatingState: function() {
            this.currentAddress = '0x';
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
                this.$toasted.error('Release address can contain alphabets and numbers');
                return;
            } else if (!this.$v.newAddress.minLength) {
                this.$toasted.error(`Release address should have at least ${addressLength.WEB.min} symbols`);
                return;
            } else if (!this.$v.newAddress.maxLength) {
                this.$toasted.error(`Release address can not be longer than ${addressLength.WEB.max} characters`);
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
            this.$axios.single.post(this.$routing.generate('token_contract_update', {
                name: this.tokenName,
            }), {
                address: this.newAddress,
                lock: this.locked,
                code,
            })
            .then(() => {
                this.setUpdatingState();
                this.$toasted.success('Updating address is pending.');
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

