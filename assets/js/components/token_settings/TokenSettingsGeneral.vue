<template>
    <div>
        <div v-if="isCreatedOnMintmeSite" class="card mt-2 px-3 py-3">
            <h5 class="card-title" v-html="$t('page.token_settings.tab.general.token_name')"></h5>
            <div class="row">
                <div class="col-12 col-md-6">
                    <token-change-name
                        :is-token-exchanged="getIsTokenExchanged"
                        :is-token-not-deployed="isTokenNotDeployed"
                        :current-name="getTokenName"
                        @name-change="onTokenNameChange"
                        @validation="onTokenNameValidation"
                    />
                </div>
                <div class="col"></div>
            </div>
        </div>
        <div class="card mt-2 px-3 py-3">
            <h5 class="card-title" v-html="$t('page.token_settings.tab.general.plan')"></h5>
            <div class="row">
                <div class="col-12 col-md-8">
                    <counted-textarea
                        :rows="5"
                        v-model="newDescription"
                        :min-length="descriptionMinLength"
                        :invalid="$v.newDescription.$anyError"
                        textarea-tab-index="1"
                        editable
                    >
                        <template v-slot:label>
                            <span class="token-intro-description-plan label-bg-primary-dark">
                                {{ $t('token.intro.description.plan') }}
                            </span>
                        </template>
                        <template v-slot:errors>
                            <div v-if="!$v.newDescription.required">
                                {{ $t('form.validation.required') }}
                            </div>
                            <div v-if="!$v.newDescription.minLength">
                                {{ $t('token.intro.description.min_length', translationContext) }}
                            </div>
                            <div v-if="!$v.newDescription.maxLength">
                                {{ $t('token.intro.description.max_length', translationContext) }}
                            </div>
                        </template>
                    </counted-textarea>
                </div>
                <div class="col">
                    <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                    {{ $t('token.intro.description.plan.guide_body') }}
                </div>
            </div>
        </div>
        <div class="card mt-2 px-3 py-3">
            <h5 class="card-title" v-html="$t('page.token_settings.tab.general.cover')"></h5>
            <div class="row">
                <div class="col-12 col-lg-8">
                    <token-cover-image
                        editable
                        entry-point
                        :token-name="getTokenName"
                        :init-image="coverImage"
                        tabindex="1"
                    />
                    <div class="text-muted p-1">
                        {{ $t('page.token_settings.tab.general.cover.width', {width: coverMinWidth}) }}
                    </div>
                </div>
                <div class="col"></div>
            </div>
        </div>
        <div class="card mt-2 px-3">
            <h5
                class="card-title font-weight-semibold"
                v-html="$t('page.token_settings.tab.general.proposals_voting')"
            ></h5>
            <div class="text-hint">
                {{ $t('page.token_settings.tab.general.proposals_voting.hint') }}
            </div>
            <div class="row">
                <div class="col-12 col-md-4 col-lg-3">
                    <m-input
                        :label="$t('page.token_settings.tab.general.amount_tokens')"
                        v-model="newTokenProposalMinAmount"
                        autocomplete="off"
                        :max-length="maxInputLength"
                        @keyup="checkInputDot"
                        @keypress="checkInput(tokSubunit)"
                        @paste="checkInput(tokSubunit)"
                    >
                        <template v-slot:errors>
                            <div v-if="!$v.newTokenProposalMinAmount.required">
                                {{ $t('page.token_settings.tab.general.amount_tokens.required') }}
                            </div>
                            <div v-if="!$v.newTokenProposalMinAmount.decimal">
                                {{ $t('page.token_settings.tab.general.amount_tokens.numeric') }}
                            </div>
                            <div v-if="!$v.newTokenProposalMinAmount.maxValue">
                                {{ $t('page.token_settings.tab.general.amount_tokens.max', translationContext) }}
                            </div>
                        </template>
                    </m-input>
                </div>
                <div class="col"></div>
            </div>
        </div>
        <div class="card px-3">
            <h5
                class="card-title font-weight-semibold"
                v-html="$t('page.token_settings.tab.general.direct_message')"
            ></h5>
            <div class="text-hint">
                {{ $t('page.token_settings.tab.general.direct_message.hint') }}
            </div>
            <div class="row">
                <div class="col-12 col-md-4 col-lg-3">
                    <m-input
                        :label="$t('page.token_settings.tab.general.amount_tokens')"
                        v-model="newDmMinAmount"
                        autocomplete="off"
                        :max-length="maxInputLength"
                        @keyup="checkInputDot"
                        @keypress="checkInput(tokSubunit)"
                        @paste="checkInput(tokSubunit)"
                    >
                        <template v-slot:errors>
                            <div v-if="!$v.newDmMinAmount.required">
                                {{ $t('page.token_settings.tab.general.amount_tokens.required') }}
                            </div>
                            <div v-if="!$v.newDmMinAmount.decimal">
                                {{ $t('page.token_settings.tab.general.amount_tokens.numeric') }}
                            </div>
                            <div v-if="!$v.newDmMinAmount.maxValue">
                                {{ $t('page.token_settings.tab.general.amount_tokens.max', translationContext) }}
                            </div>
                        </template>
                    </m-input>
                </div>
                <div class="col"></div>
            </div>
        </div>
        <div class="card px-3">
            <h5
                class="card-title font-weight-semibold"
                v-html="$t('page.token_settings.tab.general.write_comments')"
            />
            <div class="text-hint">
                {{ $t('page.token_settings.tab.general.write_comments.hint') }}
            </div>
            <div class="row">
                <div class="col-12 col-md-4 col-lg-3">
                    <m-input
                        :label="$t('page.token_settings.tab.general.amount_tokens')"
                        v-model="newCommentMinAmount"
                        autocomplete="off"
                        :max-length="maxInputLength"
                        @keyup="checkInputDot"
                        @keypress="checkInput(tokSubunit)"
                        @paste="checkInput(tokSubunit)"
                    >
                        <template v-slot:errors>
                            <div v-if="!$v.newCommentMinAmount.required">
                                {{ $t('page.token_settings.tab.general.amount_tokens.required') }}
                            </div>
                            <div v-if="!$v.newCommentMinAmount.decimal">
                                {{ $t('page.token_settings.tab.general.amount_tokens.numeric') }}
                            </div>
                            <div v-if="!$v.newCommentMinAmount.maxValue">
                                {{ $t('page.token_settings.tab.general.amount_tokens.max', translationContext) }}
                            </div>
                        </template>
                    </m-input>
                </div>
                <div class="col"></div>
            </div>
            <div class="d-flex justify-content-center pb-4">
                <m-button
                    type="primary"
                    tabindex="2"
                    :disabled="saveButtonDisabled"
                    :loading="saveButtonLoading"
                    @click="onSaveChanges"
                >
                    <font-awesome-icon :icon="['far', 'check-square']" class="mr-2"></font-awesome-icon>
                    {{ $t('page.token_settings.save_changes') }}
                </m-button>
            </div>
        </div>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofaEnabled"
            @verify="sendSaveRequest"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import he from 'he';
import {mapGetters, mapMutations} from 'vuex';
import {descriptionLength, tokenDeploymentStatus, TOK} from '../../utils/constants';
import TokenChangeName from '../token/TokenChangeName';
import TokenCoverImage from '../token/TokenCoverImage';
import TwoFactorModal from '../modal/TwoFactorModal';
import {
    required,
    minLength,
    maxLength,
    maxValue,
    decimal,
} from 'vuelidate/lib/validators';
import {
    NotificationMixin,
    CheckInputMixin,
} from '../../mixins';
import {
    CountedTextarea,
    MInput,
    MButton,
} from '../UI';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCheckSquare} from '@fortawesome/free-regular-svg-icons';

library.add(faCheckSquare);

const maxAmountDigits = 6;
const maxTokensAmount = 999999.9999;
const maxInputLength = 11;

export default {
    name: 'TokenSettingsGeneral',
    mixins: [
        NotificationMixin,
        CheckInputMixin,
    ],
    props: {
        currentDescription: String,
        twofaEnabled: Boolean,
        coverImage: String,
        isCreatedOnMintmeSite: Boolean,
        tokenProposalMinAmount: Number,
        dmMinAmount: Number,
        commentMinAmount: Number,
    },
    components: {
        TokenChangeName,
        CountedTextarea,
        TokenCoverImage,
        TwoFactorModal,
        MInput,
        MButton,
        FontAwesomeIcon,
    },
    data() {
        return {
            newTokenName: null,
            newDescription: this.currentDescription,
            tokenNameInvalid: false,
            showTwoFactorModal: false,
            readyToSave: true,
            coverMinWidth: 784,
            descriptionMinLength: descriptionLength.min,
            newTokenProposalMinAmount: this.tokenProposalMinAmount,
            newDmMinAmount: this.dmMinAmount,
            newCommentMinAmount: this.commentMinAmount,
            maxTokensAmount: maxTokensAmount,
            maxAmountDigits: maxAmountDigits,
            tokSubunit: TOK.subunit,
            maxInputLength: maxInputLength,
            saveButtonLoading: false,
            saveButtonDisabled: true,
        };
    },
    mounted() {
        this.newTokenName = this.getTokenName;
    },
    computed: {
        ...mapGetters('tokenInfo', [
            'getDeploymentStatus',
        ]),
        ...mapGetters('tokenSettings', [
            'getTokenName',
            'getIsTokenExchanged',
        ]),
        currentDescriptionHtmlDecode: function() {
            return he.decode(this.currentDescription);
        },
        isTokenNotDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.getDeploymentStatus;
        },
        isShortDescription: function() {
            return 0 < this.currentDescriptionHtmlDecode?.length
                && this.currentDescriptionHtmlDecode.length < this.descriptionMinLength;
        },
        translationContext: function() {
            return {
                minDescriptionLength: descriptionLength.min,
                maxDescriptionLength: descriptionLength.max,
                maxTokensAmount: this.maxTokensAmount,
            };
        },
    },
    methods: {
        ...mapMutations('tokenSettings', [
            'setTokenName',
        ]),
        onTokenNameChange: function(newName) {
            this.newTokenName = newName;
            this.tokenNameProcessing = false;
            this.checkIsDataReadyToSave();
        },
        onTokenNameValidation: function(validationStatus) {
            this.tokenNameInvalid = validationStatus;
            this.tokenNameProcessing = false;
            this.checkIsDataReadyToSave();
        },
        save: function() {
            if (this.twofaEnabled && this.getTokenName !== this.newTokenName) {
                this.showTwoFactorModal = true;
            } else {
                this.sendSaveRequest();
            }
        },
        sendSaveRequest: async function(code) {
            this.saveButtonLoading = true;
            try {
                const response = await this.$axios.single.patch(
                    this.$routing.generate('token_update', {name: this.getTokenName}),
                    {
                        description: this.newDescription,
                        name: this.getTokenName !== this.newTokenName ? this.newTokenName : null,
                        tokenProposalMinAmount: this.newTokenProposalMinAmount.toString(),
                        dmMinAmount: this.newDmMinAmount.toString(),
                        commentMinAmount: this.newCommentMinAmount.toString(),
                        code: code ? code : '',
                    },
                );
                this.newDescription = response.data.newDescription;
                this.setTokenName(this.newTokenName);
                this.onSaveSuccess();
            } catch (error) {
                this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                this.$logger.error('Error while editing description', error);
            } finally {
                this.saveButtonLoading = false;
            }
        },
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        checkIfHasChanges: function() {
            return this.newDescription !== this.currentDescription
                || this.getTokenName !== this.newTokenName
                || this.tokenProposalMinAmount !== this.newTokenProposalMinAmount
                || this.dmMinAmount !== this.newDmMinAmount
                || this.commentMinAmount !== this.newCommentMinAmount;
        },
        onSaveChanges: function() {
            if (!this.checkIfHasChanges()) {
                return;
            }

            this.save();
        },
        checkIsDataReadyToSave: function() {
            this.$v.$touch();
            this.saveButtonDisabled = this.tokenNameInvalid || this.$v.$invalid;
        },
        onSaveSuccess: function() {
            window.location = this.$routing.generate('token_show_intro', {
                name: this.newTokenName,
                saveSuccess: true,
            });
        },
    },
    watch: {
        newDescription: function() {
            this.checkIsDataReadyToSave();
        },
        newTokenProposalMinAmount: function() {
            this.checkIsDataReadyToSave();
        },
        newDmMinAmount: function() {
            this.checkIsDataReadyToSave();
        },
        newCommentMinAmount: function() {
            this.checkIsDataReadyToSave();
        },
    },
    validations() {
        return {
            newDescription: {
                required,
                minLength: minLength(descriptionLength.min),
                maxLength: maxLength(descriptionLength.max),
                checkIfHasChanges: () => this.checkIfHasChanges(),
            },
            newTokenProposalMinAmount: {
                required,
                decimal,
                maxValue: maxValue(this.maxTokensAmount),
                maxLength: maxLength(this.maxInputLength),
            },
            newDmMinAmount: {
                required,
                decimal,
                maxValue: maxValue(this.maxTokensAmount),
                maxLength: maxLength(this.maxInputLength),
            },
            newCommentMinAmount: {
                required,
                decimal,
                maxValue: maxValue(this.maxTokensAmount),
                maxLength: maxLength(this.maxInputLength),
            },
        };
    },
};
</script>
