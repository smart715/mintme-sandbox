<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">
                {{ $t('post.create') }}
            </slot>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="amount">
                    {{ $t('post_form.require_amount') }}
                </label>
                <guide>
                    <template slot="body">
                        {{ $t('post_form.body') }}
                    </template>
                </guide>
                <div class="input-group">
                    <div class="w-100">
                        <input id="post-form-amount-input"
                            autocomplete="off"
                            class="form-control form-control-lg w-100"
                            :class="{ 'is-input-invalid' : invalidAmount }"
                            name="amount"
                            type="text"
                            v-model="amount"
                            placeholder="0"
                            @keypress="checkInput(subunit)"
                            @paste="checkInput(subunit)"
                        >
                    </div>
                    <input-delete
                        :hasInputError="invalidAmount"
                        @clear-input="clearInput('amount', '')"
                    >
                    </input-delete>
                </div>
                <div class="invalid-feedback"
                     :class="{ 'd-block' : invalidAmount }"
                >
                    {{ invalidAmountMessage }}
                </div>
            </div>
            <div class="form-group">
                <label for="share_reward">
                    {{ $t('post_form.share_reward') }}
                </label>
                <guide>
                    <template slot="body">
                        {{ $t('post_form.share_reward_guide', { rewardDays }) }}
                    </template>
                </guide>
                <div class="input-group">
                    <div class="w-100">
                        <input id="post-form-share_reward-input"
                            autocomplete="off"
                            class="form-control form-control-lg w-100"
                            :class="{ 'is-input-invalid' : invalidShareReward }"
                            name="share_reward"
                            type="text"
                            v-model="shareReward"
                            placeholder="0"
                            @keypress="checkInput(4)"
                            @paste="checkInput(4)"
                        >
                    </div>
                    <input-delete
                        :hasInputError="invalidShareReward"
                        @clear-input="clearInput('shareReward', '')"
                    >
                </input-delete>
                </div>
                <div class="invalid-feedback"
                    :class="{ 'd-block' : invalidShareReward }"
                >
                    {{ invalidShareRewardMessage }}
                </div>
            </div>
            <div class="form-group">
                <label for="title">
                    {{ $t('post_form.title') }}
                </label>
                <div class="input-group">
                    <div class="w-100">
                        <input class="form-control form-control-lg w-100"
                            :class="{ 'is-input-invalid' : invalidTitle }"
                            id="title"
                            name="title"
                            type="text"
                            v-model="title"
                        >
                    </div>
                    <input-delete
                        :hasInputError="invalidTitle"
                        @clear-input="clearInput('title', '')"
                    >
                    </input-delete>
                </div>
                <div class="invalid-feedback"
                    :class="{ 'd-block' : invalidTitle }"
                >
                    {{ invalidTitleMessage }}
                </div>
            </div>
            <div class="form-group">
                <div class="input-group d-block" >
                    <input-delete
                        :hasInputError="invalidContent"
                        class="float-right mt-5"
                        @clear-input="clearInput('content', '')"
                    >
                    </input-delete>
                    <counted-textarea
                        class="form-control w-100"
                        :class="{ 'is-input-invalid' : invalidContent }"
                        :value="content"
                        name="content"
                        @change="onContentChange"
                        @input="onContentChange"
                        ref="input"
                    />
                </div>
                <div class="invalid-feedback"
                    :class="{ 'd-block' : invalidContent }"
                >
                    {{ invalidContentMessage }}
                </div>
            </div>
            <button class="btn btn-primary"
                :disabled="submitting || $v.$invalid"
                @click="savePost"
            >
                {{ $t('save') }}
            </button>
            <button class="btn btn-cancel"
                @click="cancel"
            >
               {{ $t('cancel') }}
            </button>
        </div>
    </div>
</template>

<script>
import {CountedTextarea} from '../UI';
import Guide from '../Guide';
import {HTTP_OK, requiredBBCText} from '../../utils/constants';
import {toMoney} from '../../utils';
import {
    CheckInputMixin,
    NotificationMixin,
    ClearInputMixin,
    FloatInputMixin,
} from '../../mixins';
import {required, minLength, maxLength, decimal, between} from 'vuelidate/lib/validators';
import InputDelete from '../InputDelete';
import {mapGetters} from 'vuex';

export default {
    name: 'PostForm',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
        ClearInputMixin,
        FloatInputMixin,
    ],
    components: {
        CountedTextarea,
        Guide,
        InputDelete,
    },
    props: {
        tokenName: String,
        subunit: Number,
        post: {
            type: Object,
            default: () => ({
                content: '',
                amount: '',
                title: '',
                shareReward: '',
            }),
        },
        resetAfterAction: {
            type: Boolean,
            default: false,
        },
        resetCancel: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            content: this.post.content,
            amount: this.post.amount ? toMoney(this.post.amount, this.subunit) : this.post.amount,
            title: this.post.title,
            shareReward: this.post.shareReward ? toMoney(this.post.shareReward, this.subunit) : this.post.shareReward,
            minContentLength: 2,
            maxContentLength: 1000,
            maxDecimals: 4,
            maxAmount: 999999.9999,
            maxShareReward: 100,
            maxTitleLength: 100,
            contentError: false,
            contentErrorMessage: '',
            amountError: false,
            amountErrorMessage: '',
            titleError: false,
            titleErrorMessage: '',
            shareRewardError: false,
            shareRewardErrorMessage: '',
            submitting: false,
        };
    },
    computed: {
        ...mapGetters('posts', {
            rewardDays: 'getPostRewardsCollectableDays',
        }),
        invalidContent() {
            return this.contentError || (this.$v.content.$invalid && 0 < this.content.length);
        },
        invalidContentMessage() {
            if (this.contentError) {
                return this.contentErrorMessage;
            }
            if (!this.$v.content.required) {
                return this.$t('post_form.msg.empty');
            }
            if (!this.$v.content.minLength) {
                return this.$t('post_form.msg.min_length', {minContentLength: this.minContentLength});
            }
            if (!this.$v.content.maxLength) {
                return this.$t('post_form.msg.max_length', {maxContentLength: this.maxContentLength});
            }

            return '';
        },
        invalidAmount() {
            return this.amountError || (this.$v.amount.$invalid && 0 < this.amount.length);
        },
        invalidAmountMessage() {
            if (this.amountError) {
                return this.amountErrorMessage;
            }
            if (!this.$v.amount.decimal) {
                return this.$t('post_form.msg.amount.numeric');
            }
            if (!this.$v.amount.maxDecimals) {
                return this.$t('post_form.msg.amount.max_decimals', {maxDecimals: this.maxDecimals});
            }
            if (!this.$v.amount.between) {
                return this.$t('post_form.msg.amount.between', {maxAmount: this.maxAmount});
            }

            return '';
        },
        invalidTitle() {
            return this.titleError || (this.$v.title.$invalid && 0 < this.title.length);
        },
        invalidTitleMessage() {
            if (this.titleError) {
                return this.titleErrorMessage;
            }

            if (!this.$v.title.required) {
                return this.$t('post_form.msg.title.required');
            }

            if (!this.$v.title.maxLength) {
                return this.$t('post_form.msg.title.max_length', {maxTitleLength: this.maxTitleLength});
            }

            return '';
        },
        invalidShareReward() {
            return this.shareRewardError || (this.$v.shareReward.$invalid && 0 < this.shareReward.length);
        },
        invalidShareRewardMessage() {
            if (this.shareRewardError) {
                return this.shareRewardErrorMessage;
            }
            if (!this.$v.shareReward.decimal) {
                return this.$t('post_form.msg.share_reward.numeric');
            }
            if (!this.$v.shareReward.maxDecimals) {
                return this.$t('post_form.msg.share_reward.max_decimals', {maxDecimals: this.maxDecimals});
            }
            if (!this.$v.shareReward.between) {
                return this.$t('post_form.msg.share_reward.between', {maxReward: this.maxShareReward});
            }

            return '';
        },
    },
    methods: {
        onContentChange(content) {
            this.content = content;
        },
        savePost() {
            this.submitting = true;
            this.$v.$touch();

            const url = this.post.id
                ? this.$routing.generate('edit_post', {tokenName: this.tokenName, id: this.post.id})
                : this.$routing.generate('create_post', {tokenName: this.tokenName});

            this.$axios.single.post(url, {
                content: this.content,
                amount: this.amount ? this.amount : '0',
                title: this.title,
                shareReward: this.shareReward ? this.shareReward : '0',
            })
                .then(this.savePostSuccessHandler.bind(this), this.savePostErrorHandler.bind(this))
                .finally(() => this.submitting = false);
        },
        savePostSuccessHandler(res) {
            if (HTTP_OK !== res.status) {
                return;
            }

            this.$emit('save-success');
            this.notifySuccess(res.data.message);

            if (this.resetAfterAction) {
                this.reset();
            }
        },
        // handles server side validation errors, although it shouldn't happen (because of frontend validation)
        savePostErrorHandler(data) {
            data.response?.data?.message && this.notifyError(data.response.data.message);
            const errors = data.response.data.errors;
            if (errors) {
                if (errors.children.amount.errors) {
                    this.amountError = true;
                    this.amountErrorMessage = errors.children.amount.errors[0];
                }

                if (errors.children.content.errors) {
                    this.contentError = true;
                    this.contentErrorMessage = errors.children.content.errors[0];
                }

                if (errors.children.title.errors) {
                    this.titleError = true;
                    this.titleErrorMessage = errors.children.title.errors[0];
                }

                if (errors.children.shareReward.errors) {
                    this.shareRewardError = true;
                    this.shareRewardErrorMessage = errors.children.shareReward.errors[0];
                }
            }
        },
        reset() {
            this.title = '';
            this.content = '';
            this.amount = '';
            this.shareReward = '';

            this.$nextTick(() => {
                this.$refs.input.$el.dispatchEvent(new Event('input'));
            });
        },
        cancel() {
            this.$emit('cancel');
            if (this.resetCancel) {
                this.reset();
            }
        },
    },
    watch: {
        content() {
            this.contentError = false;
            this.contentErrorMessage = '';
        },
        amount() {
            this.amountError = false;
            this.amountErrorMessage = '';
            this.amount = this.parseFloatInput(this.amount);
        },
        title() {
            this.titleError = false;
            this.titleErrorMessage = '';
        },
        shareReward() {
            this.shareRewardError = false;
            this.shareRewardErrorMessage = '';
            this.shareReward = this.parseFloatInput(this.shareReward);
        },
    },
    validations() {
        return {
            content: {
                required: requiredBBCText,
                minLength: minLength(this.minContentLength),
                maxLength: maxLength(this.maxContentLength),
            },
            amount: {
                decimal,
                maxDecimals: (val) => {
                    return (val.split('.')[1] || '').length <= this.maxDecimals;
                },
                between: between(0, this.maxAmount),
            },
            title: {
                required: (val) => required(val.trim()),
                maxLength: maxLength(this.maxTitleLength),
            },
            shareReward: {
                decimal,
                maxDecimals: (val) => {
                    return (val.split('.')[1] || '').length <= this.maxDecimals;
                },
                between: between(0, this.maxShareReward),
            },
        };
    },
};
</script>
