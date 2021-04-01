<template>
    <div v-if="canUpdate">
        <div class="col-12 pb-3 px-0">
            <label for="address" class="d-block text-left">
                {{ $t('token.release.new_address') }}
            </label>
            <input
                id="address"
                type="text"
                v-model.trim="$v.newAddress.$model"
                class="form-control"
                :class="{ 'is-invalid': $v.newAddress.$error }"
            >
            <div v-if="$v.newAddress.$error" class="invalid-feedback">
                {{ $t('withdraw_modal.length') }}
            </div>
        </div>
        <div class="col-12 pt-2 px-0 clearfix">
            <button
                class="btn btn-primary float-left"
                :disabled="buttonDisabled"
                @click="editAddress"
            >
                {{ $t('save') }}
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
            {{ $t('token.release.not_deployed') }}
        </p>
    </div>
    <div
        v-else
        class="text-left"
    >
        <p class="bg-info m-0 py-1 px-3">
            {{ $t('token.release.updating_address_pending') }}
        </p>
    </div>
</template>

<script>
import TwoFactorModal from '../modal/TwoFactorModal';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {addressLength, addressContain, addressFirstSymbol} from '../../utils/constants';
import {LoggerMixin, NotificationMixin} from '../../mixins';

export default {
    name: 'TokenReleaseAddress',
    mixins: [NotificationMixin, LoggerMixin],
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
            newAddress: this.releaseAddress,
            showTwoFactorModal: false,
            submitting: false,
        };
    },
    computed: {
        canUpdate: function() {
            return this.isTokenDeployed && '0x' !== this.currentAddress;
        },
        sameAddress: function() {
            return this.currentAddress === this.newAddress;
        },
        buttonDisabled: function() {
            return this.submitting || this.sameAddress || this.$v.newAddress.$error;
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
            } else if (this.$v.newAddress.$error) {
                this.notifyError(this.$t('withdraw_modal.length'));
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
                code,
            })
            .then(() => {
                this.submitting = false;
                this.setUpdatingState();
                this.notifySuccess(this.$t('token.release.updating_address_pending'));
            }, (error) => {
                this.submitting = false;
                if (!error.response) {
                    this.notifyError(this.$t('toasted.error.network'));
                    this.sendLogs('error', 'Edit address network error', error);
                } else if (error.response.data.message) {
                    this.notifyError(error.response.data.message);
                    this.sendLogs('error', 'Can not edit address', error);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.sendLogs('error', 'An error has occurred, please try again later', error);
                }
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
                addressFirstSymbol: addressFirstSymbol['WEB'],
            },
        };
    },
};
</script>

