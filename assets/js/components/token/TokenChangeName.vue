<template>
    <div>
        <div class="col-12 px-0">
            <m-input
                v-model="newName"
                :label="$t('token.change_name.edit_token')"
                :loading="tokenNameProcessing"
                :disabled="!!errorMessage"
                :hint="errorMessage"
                input-tab-index="1"
            >
                <template v-slot:errors>
                    <div v-if="!$v.newName.validChars">
                        {{ $t('page.token_creation.error.contain') }}
                    </div>
                    <div v-if="isInvalidSpaceError">
                        {{ $t('page.token_creation.error.space') }}
                    </div>
                    <div v-if="!$v.newName.minLength">
                        {{ $t('page.token_creation.error.min') }}
                    </div>
                    <div v-if="!$v.newName.maxLength">
                        {{ $t('page.token_creation.error.max') }}
                    </div>
                    <div v-if="!$v.newName.hasNotBlockedWords">
                        {{ $t('page.token_creation.error.blocked') }}
                    </div>
                    <div v-if="tokenNameExists">
                        {{ $t('page.token_creation.error.taken') }}
                    </div>
                    <div v-if="tokenNameInBlacklist">
                        {{ $t('page.token_creation.error.forbidden') }}
                    </div>
                    <div v-if="!$v.newName.noBadWords" v-text="newNameBadWordMessage"></div>
                </template>
            </m-input>
        </div>
    </div>
</template>

<script>
import {maxLength, minLength, required} from 'vuelidate/lib/validators';
import {
    FORBIDDEN_WORDS,
    HTTP_OK,
    tokenNameValidChars,
    tokenNoSpaceBetweenDashes,
    tokenValidFirstChars,
    tokenValidLastChars,
} from '../../utils/constants';
import {MInput} from '../UI';
import {
    ClearInputMixin,
    NoBadWordsMixin,
    NotificationMixin,
} from '../../mixins';

export default {
    name: 'TokenChangeName',
    mixins: [
        NotificationMixin,
        ClearInputMixin,
        NoBadWordsMixin,
    ],
    components: {
        MInput,
    },
    props: {
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
        currentName: String,
    },
    data() {
        return {
            minLength: 4,
            maxLength: 60,
            newName: this.currentName,
            submitting: false,
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameTimeout: null,
            tokenNameInBlacklist: false,
            isRequestingNameCheck: false,
            loading: false,
            newNameBadWordMessage: '',
        };
    },
    computed: {
        btnDisabled: function() {
            return this.tokenNameExists || this.tokenNameProcessing || this.submitting
                || this.isTokenExchanged || !this.isTokenNotDeployed || this.$v.$invalid
                || this.currentName === this.newName || this.tokenNameInBlacklist;
        },
        errorMessage: function() {
            let message = null;

            if (!this.isTokenNotDeployed) {
                message = this.$t('token.change_name.cant_be_changed');
            } else if (this.isTokenExchanged) {
                message = this.$t('token.change_name.must_own_all');
            }

            return message;
        },
        isInvalidSpaceError: function() {
            if (0 === this.newName.length) {
                return false;
            }

            return !this.$v.newName.validFirstChars
                || !this.$v.newName.validLastChars
                || !this.$v.newName.noSpaceBetweenDashes;
        },
    },
    watch: {
        newName: function() {
            clearTimeout(this.tokenNameTimeout);
            if (0 === this.newName.replace(/\s/g, '').length) {
                this.newName = '';
            }
            this.tokenNameExists = false;
            this.tokenNameInBlacklist = false;
            this.$emit('validation', true);
            if (this.$v.$invalid && this.newName) {
                this.tokenNameProcessing = true;
                this.tokenNameTimeout = setTimeout(this.checkTokenExistence, 500);
            }
        },
    },
    methods: {
        checkTokenExistence: function() {
            new Promise((resolve, reject) => {
                this.$axios.single.get(this.$routing.generate('token_name_blacklist_check', {name: this.newName}))
                    .then((response) => {
                        if (HTTP_OK === response.status) {
                            this.tokenNameInBlacklist = response.data.blacklisted;
                            if (!this.tokenNameInBlacklist) {
                                this.$axios.single.get(
                                    this.$routing.generate('is_unique_token_name',
                                        {name: this.newName}))
                                    .then((response) => {
                                        if (HTTP_OK === response.status) {
                                            this.tokenNameExists = response.data.exists;

                                            if (!this.tokenNameExists) {
                                                this.$emit('name-change', this.newName);
                                            }
                                            this.$emit('validation', this.tokenNameExists);
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
                noBadWords: () => this.noBadWordsValidator('newName', 'newNameBadWordMessage'),
            },
        };
    },
    beforeMount() {
        this.newName = this.currentName;
    },
};
</script>
