<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">Create post</slot>
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
                <input id="post-form-amount-input"
                    class="form-control form-control-lg w-100"
                    :class="{ 'is-invalid' : invalidAmount }"
                    name="amount"
                    type="text"
                    v-model="amount"
                    @keypress="checkInput(subunit, 6)"
                    @paste="checkInput(subunit, 6)"
                >
                <div class="invalid-feedback">
                    {{ invalidAmountMessage }}
                </div>
            </div>
            <div class="form-group">
                <label for="share_reward">
                    {{ $t('post_form.share_reward') }}
                </label>
                <guide>
                    <template slot="body">
                        {{ $t('post_form.share_reward_guide') }}
                    </template>
                </guide>
                <input id="post-form-share_reward-input"
                    class="form-control form-control-lg w-100"
                    :class="{ 'is-invalid' : invalidShareReward }"
                    name="share_reward"
                    type="text"
                    v-model="shareReward"
                    @keypress="checkInput(4, 3)"
                    @paste="checkInput(4, 3)"
                >
                <div class="invalid-feedback">
                    {{ invalidShareRewardMessage }}
                </div>
            </div>
            <div class="form-group">
                <label for="title">
                    {{ $t('post_form.title') }}
                </label>
                <input class="form-control form-control-lg w-100"
                       :class="{ 'is-invalid' : invalidTitle }"
                       id="title"
                       name="title"
                       type="text"
                       v-model="title"
                >
                <div class="invalid-feedback">
                    {{ invalidTitleMessage }}
                </div>
            </div>
            <div class="form-group">
                <bbcode-help class="float-right mt-2" placement="right" />
                <bbcode-editor class="form-control w-100"
                    :class="{ 'is-invalid' : invalidContent }"
                    :value="content"
                    @change="onContentChange"
                    @input="onContentChange"
                    ref="input"
                />
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
import BbcodeEditor from '../bbcode/BbcodeEditor';
import BbcodeHelp from '../bbcode/BbcodeHelp';
import Guide from '../Guide';
import {toMoney} from '../../utils';
import {HTTP_OK, requiredBBCText} from '../../utils/constants';
import {CheckInputMixin, NotificationMixin} from '../../mixins';
import {required, minLength, maxLength, decimal, between} from 'vuelidate/lib/validators';

export default {
    name: 'PostForm',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
    ],
    components: {
        BbcodeEditor,
        BbcodeHelp,
        Guide,
    },
    props: {
        tokenName: String,
        subunit: Number,
        post: {
            type: Object,
            default: () => ({
                content: '',
                amount: '0',
                title: '',
                shareReward: '0',
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
            amount: toMoney(this.post.amount),
            title: this.post.title,
            shareReward: toMoney(this.post.shareReward),
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
        invalidContent() {
            return this.contentError || (this.$v.content.$invalid && this.content.length > 0);
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
            return this.amountError || this.$v.amount.$invalid;
        },
        invalidAmountMessage() {
            if (this.amountError) {
                return this.amountErrorMessage;
            }
            if (!this.$v.amount.required) {
                return this.$t('post_form.msg.amount.required');
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
            return this.titleError || (this.$v.title.$invalid && this.title.length > 0);
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
            return this.shareRewardError || this.$v.shareReward.$invalid;
        },
        invalidShareRewardMessage() {
            if (this.shareRewardError) {
                return this.shareRewardErrorMessage;
            }
            if (!this.$v.shareReward.required) {
                return this.$t('post_form.msg.share_reward.required');
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

            if (this.$v.$invalid) {
                return;
            }

            const url = this.post.id
                ? this.$routing.generate('edit_post', {tokenName: this.tokenName, id: this.post.id})
                : this.$routing.generate('create_post', {tokenName: this.tokenName});

            this.$axios.single.post(url, {
                content: this.content,
                amount: this.amount,
                title: this.title,
                shareReward: this.shareReward,
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
            let errors = data.response.data.errors;
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
            this.amount = '0';
            this.shareReward = '0';

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
        },
        title() {
            this.titleError = false;
            this.titleErrorMessage = '';
        },
        shareReward() {
            this.shareRewardError = false;
            this.shareRewardErrorMessage = '';
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
                required,
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
                required,
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
