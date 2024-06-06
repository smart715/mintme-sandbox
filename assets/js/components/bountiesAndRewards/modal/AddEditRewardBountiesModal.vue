<template>
    <div>
        <modal
            :visible="visible"
            :no-close="false"
            without-padding
            @close="closeModal"
        >
            <template v-slot:header>
                <span v-html="modalTitle"></span>
            </template>
            <template v-slot:body>
                <div class="overflow-wrap-break-word modal-body">
                    <div>
                        <div class="col-12">
                            <m-input
                                :label="$t('token.rewards_bounties.title.label')"
                                v-model="$v.title.$model"
                                :invalid="$v.title.$anyError"
                            >
                                <template v-slot:errors>
                                    <div v-if="$v.title.$dirty && !$v.title.required">
                                        {{ $t('form.validation.required')  }}
                                    </div>
                                    <div v-if="!$v.title.minLength">
                                        {{ $t('add_reward.title.min', translationContext)  }}
                                    </div>
                                    <div v-if="!$v.title.maxLength">
                                        {{ $t('add_reward.title.max', translationContext) }}
                                    </div>
                                    <div v-if="hasInvalidSpace">
                                        {{ $t('add_reward.title.space') }}
                                    </div>
                                    <div v-if="!$v.title.noBadWords" v-text="titleBadWordMessage"></div>
                                </template>
                            </m-input>
                        </div>
                        <div class="col-12">
                            <m-input
                                v-model="$v.price.$model"
                                :invalid="$v.price.$anyError"
                                :placeholder="placeholderZero"
                                :disabled="isBountyType && isEditModalType"
                                @keyup="checkInputDot"
                                @keypress="checkInput()"
                                @paste="checkInput()"
                            >
                                <template v-slot:label>
                                    <span v-html="isRewardType
                                        ? $t('token.rewards.price.label')
                                        : $t('token.bounty.reward.label')"
                                    ></span>
                                </template>
                                <template v-slot:errors>
                                    <div v-if="$v.price.$dirty && !$v.price.required">
                                        {{ $t('form.validation.required')  }}
                                    </div>
                                    <div v-if="!$v.price.minValue && $v.price.decimal">
                                        {{ $t('add_reward.price.min_value', translationContext) }}
                                    </div>
                                    <div v-if="!$v.price.maxValue && $v.price.decimal">
                                        {{ $t('add_reward.price.max_value', translationContext) }}
                                    </div>
                                    <div v-if="!$v.price.decimal">
                                        {{ $t('add_reward.price.invalid') }}
                                    </div>
                                </template>
                            </m-input>
                        </div>
                        <div class="col-12">
                            <m-textarea
                                :label="$t('token.rewards_bounties.description.label')"
                                :hint="descriptionGuideLabel"
                                v-model="$v.description.$model"
                                :invalid="$v.description.$anyError"
                                :rows="5"
                                :max-length="maxDescriptionLength"
                                :counter="true"
                            >
                                <template v-slot:errors>
                                    <div v-if="!$v.description.maxLength">
                                        {{ $t('add_reward.description.max_value', translationContext)  }}
                                    </div>
                                    <div v-if="!$v.description.noBadWords" v-text="descriptionBadWordMessage"></div>
                                </template>
                            </m-textarea>
                        </div>
                        <div class="col-12 pt-2">
                            <m-input
                                v-model="$v.quantity.$model"
                                :invalid="$v.quantity.$anyError"
                                :placeholder="placeholderZero"
                                @keyup="checkInputDot"
                                @keypress="checkInput(0)"
                                @paste="checkInput(0)"
                            >
                                <template v-slot:label>
                                    <span v-html="isRewardType
                                        ? $t('token.rewards.quantity.label')
                                        : $t('token.bounty.number_of_participants.label')"
                                    ></span>
                                    <guide v-if="isRewardType" class="font-size-1 form-control-label-guide">
                                        <template slot="body">
                                            {{ $t('token.rewards.quantity.tooltip') }}
                                        </template>
                                    </guide>
                                </template>
                                <template v-slot:errors>
                                    <div v-if="isBountyQuantityRequired">
                                        {{ $t('form.validation.required')  }}
                                    </div>
                                    <div v-if="!$v.quantity.numeric">
                                        {{ $t('add_reward.quantity.numeric') }}
                                    </div>
                                    <div v-if="!$v.quantity.minValue">
                                        {{ $t('add_reward.quantity.min_value', translationContext) }}
                                    </div>
                                    <div v-if="!$v.quantity.maxValue">
                                        {{ $t('add_reward.quantity.max_value', translationContext) }}
                                    </div>
                                </template>
                            </m-input>
                        </div>
                        <div class="col-12 py-2 d-flex justify-content-end align-items-center">
                            <span v-if="serviceUnavailable" class="text-danger mr-2">
                                {{ $t('toasted.error.service_unavailable_short') }}
                            </span>
                            <m-button
                                type="primary"
                                :disabled="saveBtnDisabled"
                                :loading="saveBtnLoading"
                                @click="saveReward"
                                wide
                            >
                                {{ $t('save') }}
                            </m-button>
                            <m-button
                                type="link"
                                class="ml-2"
                                wide
                                @click="$emit('close')"
                            >
                                {{ $t('cancel') }}
                            </m-button>
                        </div>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import _ from 'lodash';
import Modal from '../../modal/Modal';
import {required, numeric, minLength, maxLength, minValue, maxValue, decimal} from 'vuelidate/lib/validators';
import {
    CheckInputMixin,
    MoneyFilterMixin,
    NotificationMixin,
    ClearInputMixin,
    NoBadWordsMixin,
} from '../../../mixins';
import {toMoney, toIntegerWithSpaces} from '../../../utils';
import {
    HTTP_OK,
    TYPE_REWARD,
    TYPE_BOUNTY,
    EDIT_TYPE_REWARDS_MODAL,
    ADD_TYPE_REWARDS_MODAL,
    tokenValidFirstChars,
    tokenValidLastChars,
} from '../../../utils/constants';
import {mapGetters, mapMutations} from 'vuex';
import {MInput, MTextarea, MButton} from '../../UI';
import {VBTooltip} from 'bootstrap-vue';
import Guide from '../../Guide';

const TITLE_MIN = 3;
const TITLE_MAX = 100;
const DESCRIPTION_MAX = 255;
const REWARD_QUANTITY_MIN = 0;
const BOUNTY_QUANTITY_MIN = 1;
const QUANTITY_MAX = 999;

export default {
    name: 'AddEditRewardBountiesModal',
    directives: {
        'b-tooltip': VBTooltip,
    },
    components: {
        Modal,
        MInput,
        MTextarea,
        MButton,
        Guide,
    },
    mixins: [
        CheckInputMixin,
        MoneyFilterMixin,
        NotificationMixin,
        ClearInputMixin,
        NoBadWordsMixin,
    ],
    props: {
        visible: Boolean,
        tokenName: String,
        type: String,
        modalType: {
            type: String,
            default: ADD_TYPE_REWARDS_MODAL,
        },
        editItem: {
            type: Object,
            required: false,
            default: null,
        },
    },
    data() {
        return {
            title: '',
            price: '',
            description: '',
            quantity: '',
            saveClickDisabled: false,
            titleBadWordMessage: '',
            descriptionBadWordMessage: '',
            maxDescriptionLength: DESCRIPTION_MAX,
            placeholderZero: '0',
        };
    },
    methods: {
        ...mapMutations('rewardsAndBounties', [
            'addReward',
            'addBounty',
            'editBounty',
            'editReward',
        ]),
        async saveReward() {
            this.$v.$touch();

            if (this.saveBtnDisabled) {
                return;
            }

            const data = {
                title: this.title,
                price: this.price,
                description: this.description,
                quantity: this.quantity || '0',
            };

            const requestParams = this.isEditModalType
                ? {slug: this.editItem.slug}
                : {tokenName: this.tokenName, type: this.type};

            const route = this.isEditModalType
                ? 'edit_reward'
                : 'add_new_reward';

            this.saveClickDisabled = true;

            try {
                const response = await this.$axios.single.post(this.$routing.generate(route, requestParams), data);

                if (HTTP_OK === response.status && response.data.hasOwnProperty('error')) {
                    this.notifyError(response.data.error);
                    this.saveClickDisabled = false;
                    return;
                }

                const translatedType = this.isRewardType ? this.$t('reward.title') : this.$t('bounty.title');

                if (HTTP_OK === response.status) {
                    this.notifySuccess(this.isEditModalType
                        ? this.$t('reward_bounty.edited', {type: _.upperFirst(translatedType.toString())})
                        : this.$t('reward_bounty.created', {type: _.upperFirst(translatedType.toString())})
                    );

                    this.isRewardType
                        ? this.editReward(response.data)
                        : this.editBounty(response.data);

                    this.closeModal();
                    this.clearFields();
                }
            } catch (error) {
                this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                this.$logger.error('Error during add reward/bounty', error);
            }

            this.saveClickDisabled = false;
        },
        closeModal(item) {
            this.$emit('close', item);
        },
        clearFields() {
            this.title = this.price = this.description = this.quantity = '';
            this.$v.$reset();
        },
    },
    computed: {
        ...mapGetters('tradeBalance', {
            serviceUnavailable: 'isServiceUnavailable',
            balances: 'getBalances',
        }),
        isEditModalType() {
            return EDIT_TYPE_REWARDS_MODAL === this.modalType;
        },
        isRewardType() {
            return TYPE_REWARD === this.type;
        },
        isBountyType() {
            return TYPE_BOUNTY === this.type;
        },
        balanceLoaded() {
            return null !== this.balances;
        },
        saveBtnLoading() {
            return this.saveClickDisabled || (!this.balanceLoaded && !this.serviceUnavailable);
        },
        saveBtnDisabled() {
            return this.$v.$invalid || this.saveBtnLoading || this.serviceUnavailable;
        },
        minPrice() {
            return toMoney('1e-4', 4);
        },
        maxPrice() {
            return toMoney('100000', 4);
        },
        participantsAmount() {
            return this.editItem && this.editItem.participants ? this.editItem.participants.length : 0;
        },
        translationContext() {
            return {
                minTitle: TITLE_MIN,
                maxTitle: TITLE_MAX,
                minPrice: this.minPrice,
                maxPrice: toIntegerWithSpaces(this.maxPrice),
                maxDescription: DESCRIPTION_MAX,
                minQuantity: this.isRewardType ? REWARD_QUANTITY_MIN : BOUNTY_QUANTITY_MIN,
                maxQuantity: QUANTITY_MAX,
                price_type: this.isRewardType ? this.$t('rewards.reward.price') : this.$t('rewards.bountie.reward'),
                participantsAmount: this.participantsAmount,
            };
        },
        modalTitle() {
            return this.isRewardType
                ? this.isEditModalType ? this.$t('token.rewards.edit') : this.$t('token.rewards.add_new')
                : this.isEditModalType ? this.$t('token.bounties.edit') : this.$t('token.bounties.add_new');
        },
        priceDisabled() {
            return this.isBountyType && this.isEditModalType;
        },
        descriptionLength() {
            return this.description?.length || 0;
        },
        descriptionGuideLabel() {
            return this.isRewardType
                ? this.$t('token.rewards_bounties.reward.description.guide')
                : this.$t('token.rewards_bounties.bounty.description.guide');
        },
        tooltipRewardType() {
            return this.isRewardType ? this.$t('token.rewards.quantity.tooltip') : '';
        },
        isBountyQuantityRequired() {
            return this.isBountyType
                && this.$v.quantity.$dirty
                && !this.$v.quantity.required;
        },
        quantityMin() {
            return this.isRewardType ? REWARD_QUANTITY_MIN : BOUNTY_QUANTITY_MIN;
        },
        hasInvalidSpace() {
            return 0 === this.title.length
                ? false
                : !this.$v.title.validFirstChars || !this.$v.title.validLastChars;
        },
    },
    watch: {
        visible() {
            if (this.visible && this.editItem && this.isEditModalType) {
                const {title, price, description, quantity} = this.editItem;
                this.title = title;
                this.price = price;
                this.description = description;
                this.quantity = quantity;
            } else if (!this.isEditModalType) {
                this.clearFields();
            }
        },
    },
    validations() {
        const validation = {
            title: {
                required,
                minLength: minLength(TITLE_MIN),
                maxLength: maxLength(TITLE_MAX),
                noBadWords: () => this.noBadWordsValidator('title', 'titleBadWordMessage'),
                validFirstChars: (value) => !tokenValidFirstChars(value),
                validLastChars: (value) => !tokenValidLastChars(value),
            },
            price: {
                required,
                decimal,
                minValue: minValue(this.minPrice),
                maxValue: maxValue(this.maxPrice),
            },
            description: {
                maxLength: maxLength(DESCRIPTION_MAX),
                noBadWords: () => this.noBadWordsValidator('description', 'descriptionBadWordMessage'),
            },
            quantity: {
                numeric,
                minValue: minValue(this.quantityMin),
                maxValue: maxValue(QUANTITY_MAX),
                wrongAmount(val) {
                    if (!this.isEditModalType || this.isRewardType || 0 === this.participantsAmount) {
                        return true;
                    }

                    return this.participantsAmount < parseInt(val);
                },
            },
        };
        if (this.isBountyType) {
            validation.quantity.required = required;
        }

        return validation;
    },
};
</script>
