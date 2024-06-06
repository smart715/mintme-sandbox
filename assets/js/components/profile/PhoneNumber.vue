<template>
    <div class="phone-number">
        <div class="d-flex input-height">
            <vue-tel-input
                ref="telInput"
                v-bind:class="computedBorder"
                v-model="phone"
                v-bind="bindProps"
                :disabled="disabled"
                :valid-characters-only="true"
                :input-options="{ tabindex: inputTabIndex, showDialCode: true }"
                :dropdown-options="{ showFlags: true, tabindex: inputTabIndex }"
                @input="updatePhone"
                @open="open=true"
                @close="close"
                @focus="toggleInputFocus"
                @blur="toggleInputFocus"
                @country-changed="countryChanged"
            >
                <template v-slot:arrow-icon>
                    <span>{{ open ? '&#9650;' : '&#9660;' }}</span>
                </template>
            </vue-tel-input>
            <div v-if="inline" class="d-flex align-items-center justify-content-center">
                <div
                    class="spinner-border spinner-border-sm mx-2"
                    :class="spinnerClass"
                    role="status"
                ></div>
            </div>
        </div>
        <div :class="{'error-spacer': inline}">
            <div
                v-if="!inline && phoneChecking && !showValidationMessage"
                class="d-flex align-items-center justify-content-center"
            >
                <div
                    class="spinner-border spinner-border-sm my-2"
                    :class="spinnerClass"
                    role="status"
                ></div>
            </div>
            <div v-if="showValidationMessage" class="text-danger text-center">
                {{ $t('phone_number.wrong') }}
            </div>
            <div v-if="isPhoneBlocked" class="text-danger text-center">
                {{ $t('phone_number.in_use') }}
            </div>
        </div>
    </div>
</template>
<script>
import {VueTelInput} from 'vue-tel-input';
import 'vue-tel-input/dist/vue-tel-input.css';
import debounce from 'lodash/debounce';
import axios from 'axios';
import {NotificationMixin} from '../../mixins';

const PHONE_CHECK_REQUEST_INTERVAL = 200;

export default {
    name: 'PhoneNumber',
    mixins: [NotificationMixin],
    components: {
        VueTelInput,
    },
    props: {
        phoneNumber: {
            type: String,
            default: '',
        },
        inline: Boolean,
        disabled: Boolean,
        editLimitReached: Boolean,
        inputTabIndex: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            open: false,
            phone: this.phoneNumber,
            isValidNumber: null,
            phoneChecking: false,
            debouncedPhoneCheck: null,
            cancelTokenSource: null,
            isPhoneBlocked: false,
            isFocused: false,
            dialCode: '',
            countryUpdated: true,
            bindProps: {
                mode: 'national',
                defaultCountry: 'CA',
                autoFormat: false,
                validCharactersOnly: true,
                dropdownOptions: {
                    disabled: false,
                    disabledDialCode: true,
                    showDialCodeInList: true,
                    showDialCodeInSelection: true,
                    showFlags: true,
                    showSearchBox: true,
                    tabindex: 0,
                    width: '300px',
                },
                inputOptions: {
                    showDialCode: true,
                    placeholder: this.$t('phone.phone_number.label'),
                    maxlength: 30,
                    autofocus: false,
                    type: 'tel',
                },
            },
        };
    },
    computed: {
        showValidationMessage: function() {
            return this.phone && !this.isValidNumber && !this.countryUpdated;
        },
        computedBorder() {
            return this.isFocused ? 'vti-border' : null;
        },
        spinnerClass() {
            return this.phoneChecking && !this.showValidationMessage ? 'visible' : 'invisible';
        },
    },
    created() {
        this.debouncedPhoneCheck = debounce(this.checkPhoneValidity, PHONE_CHECK_REQUEST_INTERVAL);
    },
    mounted() {
        this.sortCountries();
    },
    methods: {
        sortCountries() {
            const countries = this.$refs.telInput.allCountries;

            countries.sort((a, b) => {
                return a.dialCode - b.dialCode;
            });
        },
        countryChanged(countryObject) {
            this.countryUpdated = true;
            this.dialCode = '+' + countryObject.dialCode;
        },
        close() {
            this.phone = this.dialCode;
            this.open = false;
        },
        checkPhoneValidity(phone) {
            this.cancelTokenSource = axios.CancelToken.source();

            this.$axios.single.get(
                this.$routing.generate('check_phone_in_use', {phoneNumber: phone}),
                {cancelToken: this.cancelTokenSource.token},
            )
                .then((response) => {
                    const phoneInUse = response.data;

                    if (phoneInUse) {
                        this.isPhoneBlocked = true;
                    } else {
                        this.$emit('is-valid-phone', true);
                    }

                    this.phoneChecking = false;
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        return;
                    }

                    this.phoneChecking = false;
                    this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                    this.$logger.error('error while checking phone number in use', error);
                });
        },
        updatePhone(number, data) {
            this.countryUpdated = false;
            this.cancelPhoneCheckRequest();

            this.isValidNumber = data.valid;
            this.$emit('phone-change', data.number);

            if (this.phoneNumber && data.number === this.phoneNumber) {
                this.isPhoneBlocked = false;
                this.phoneChecking = false;
                this.$emit('is-valid-phone', true);

                return;
            }

            this.isPhoneBlocked = false;
            this.$emit('is-valid-phone', false);

            if (this.isValidNumber) {
                this.phoneChecking = true;
                this.debouncedPhoneCheck(data.number);
            }
        },
        toggleInputFocus() {
            this.isFocused = !this.isFocused;
        },
        cancelPhoneCheckRequest() {
            if (this.cancelTokenSource) {
                this.cancelTokenSource.cancel();
            }
        },
    },
};
</script>
