<template>
    <div>
        <div v-if="isTokenExchanged || !isTokenNotDeployed" id="error-message"
             class="bg-danger text-white text-center py-2 mb-3">
            {{ errorMessage }}
        </div>
        <div class="col-12 pb-3 px-0">
            <div class="clearfix">
                <label for="tokenName" class="float-left">
                    {{ $t('token.change_name.edit_token') }}
                </label>
                <div class="float-right">
                    <div
                        v-if="tokenNameExists"
                        class="alert alert-danger alert-float"
                    >
                        <font-awesome-icon icon="exclamation-circle"></font-awesome-icon>
                        {{ $t('page.token_creation.error.taken') }}
                    </div>
                </div>
                <div class="float-right">
                    <div
                        v-if="tokenNameInBlacklist"
                        class="alert alert-danger alert-float"
                    >
                        <font-awesome-icon icon="exclamation-circle"></font-awesome-icon>
                        {{ $t('page.token_creation.error.forbidden') }}
                    </div>
                </div>
            </div>
            <input
                id="tokenName"
                type="text"
                v-model="newName"
                ref="tokenNameInput"
                class="token-name-input form-control w-100 px-2"
                :class="{ 'is-invalid': this.$v.$invalid }"
            >
            <div class="col-12 pt-2 px-0 clearfix">
                <div v-if="!this.$v.newName.validChars" class="text-danger text-center small">
                    {{ $t('page.token_creation.error.contain') }}
                </div>
                <div
                    v-if="this.newName.length > 0
                    &&(!this.$v.newName.validFirstChars
                    || !this.$v.newName.validLastChars
                    || !this.$v.newName.noSpaceBetweenDashes)"
                    class="text-danger text-center small">
                    {{ $t('page.token_creation.error.space') }}
                </div>
                <div v-if="!this.$v.newName.minLength" class="text-danger text-center small">
                    {{ $t('page.token_creation.error.min') }}
                </div>
                <div v-if="!this.$v.newName.maxLength" class="text-danger text-center small">
                    {{ $t('page.token_creation.error.max') }}
                </div>
                <div v-if="!this.$v.newName.hasNotBlockedWords" class="text-danger text-center small">
                    {{ $t('page.token_creation.error.blocked') }}
                </div>
            </div>
        </div>
        <div class="col-12 pt-2 px-0 clearfix">
            <button
                class="btn btn-primary float-left"
                :disabled="btnDisabled"
                @click="editName"
            >
                {{ $t('save') }}
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
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
    FORBIDDEN_WORDS,
    HTTP_OK,
} from '../../utils/constants';
import {LoggerMixin, NotificationMixin} from '../../mixins';

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
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameTimeout: null,
            tokenNameInBlacklist: false,
        };
    },
    computed: {
        btnDisabled: function() {
            return this.tokenNameExists || this.tokenNameProcessing || this.submitting
                || this.isTokenExchanged || !this.isTokenNotDeployed || this.$v.$invalid
                || this.currentName === this.newName || this.tokenNameInBlacklist;
        },
        errorMessage: function() {
            let message = '';

            if (!this.isTokenNotDeployed) {
                message = this.$t('token.change_name.cant_be_changed');
            } else if (this.isTokenExchanged) {
                message = this.$t('token.change_name.must_own_all');
            }

            return message;
        },
    },
    watch: {
        newName: function() {
            clearTimeout(this.tokenNameTimeout);
            if (this.newName.replace(/\s/g, '').length === 0) {
                this.newName = '';
            }
            this.tokenNameExists = false;
            this.tokenNameInBlacklist = false;
            if (!this.$v.$invalid && this.newName) {
                this.tokenNameProcessing = true;
                this.tokenNameTimeout = setTimeout(this.checkTokenExistence, 500);
            }
        },
    },
    methods: {
        checkTokenExistence: function() {
            new Promise((resolve, reject) => {
                this.$axios.single.get(
                    this.$routing.generate('token_name_blacklist_check',
                        {name: this.newName}))
                    .then((response) => {
                        if (HTTP_OK === response.status) {
                            this.tokenNameInBlacklist = response.data.blacklisted;
                            if (!this.tokenNameInBlacklist) {
                                this.$axios.single.get(
                                    this.$routing.generate('check_token_name_exists',
                                        {name: this.newName}))
                                    .then((response) => {
                                        if (HTTP_OK === response.status) {
                                            this.tokenNameExists = response.data.exists;
                                        }
                                    }, () => {
                                        this.notifyError(this.$t('toasted.error.try_later'));
                                    })
                                    .then(() => {
                                        this.tokenNameProcessing = false;
                                    });
                            }
                        }
                    }, () => {
                        this.notifyError(this.$t('toasted.error.try_later'));
                    });
            });
        },
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
            if (this.currentName === this.newName ||
                this.isTokenExchanged || !this.isTokenNotDeployed || !this.newName ||
                !this.$v.newName.validFirstChars || !this.$v.newName.validLastChars ||
                !this.$v.newName.noSpaceBetweenDashes || !this.$v.newName.validChars ||
                !this.$v.newName.minLength || !this.$v.newName.maxLength) {
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
            this.$axios.single.patch(this.$routing.generate('token_update_name', {
                name: this.currentName,
            }), {
                name: this.newName,
                code: code,
            })
                .then((response) => {
                    if (response.status === HTTP_OK) {
                        // todo: don't mutate prop
                        // eslint-disable-next-line
                        this.currentName = response.data['tokenName'];
                        this.notifySuccess(this.$t('token.change_name.changed_successfully'));

                        this.showTwoFactorModal = false;
                        this.closeModal();

                        location.href = this.$routing.generate('token_show', {
                            name: this.currentName,
                        });
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.sendLogs('error', 'Edit name network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.sendLogs('error', 'Can not edit name', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
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
                hasNotBlockedWords: (value) => !FORBIDDEN_WORDS.some(
                    (blocked) =>
                        new RegExp('\\b' + blocked + 's{0,1}\\b', 'ig').test(value)
                ),
                validChars: tokenNameValidChars,
                minLength: minLength(this.minLength),
                maxLength: maxLength(this.maxLength),
            },
        };
    },
};
</script>
