<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">Create post</slot>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="amount">
                    Required amount of tokens:
                </label>
                <guide>
                    <template slot="body">
                        You can restrict view for this post or leave 0. User will need to own required amount of your tokens to see this post.
                    </template>
                </guide>
                <input class="form-control form-control-lg w-100"
                    :class="{ 'is-invalid' : invalidAmount }"
                    name="amount"
                    type="text"
                    v-model="amount"
                    @keypress="checkInput(4, 6)"
                    @paste="checkInput(4, 6)"
                >
                <div class="invalid-feedback">
                    {{ invalidAmountMessage }}
                </div>
            </div>
            <div class="form-group">
                <bbcode-help class="float-right mt-2" placement="right" />
                <bbcode-editor class="form-control w-100"
                    :class="{ 'is-invalid' : invalidContent }"
                    :value="content"
                    @change="onContentChange"
                    @input="onContentChange"
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
                Save
            </button>
            <button class="btn btn-cancel"
                @click="cancel"
            >
                Cancel
            </button>
        </div>
    </div>
</template>

<script>
import BbcodeEditor from '../bbcode/BbcodeEditor';
import BbcodeHelp from '../bbcode/BbcodeHelp';
import Guide from '../Guide';
import {toMoney} from '../../utils';
import {HTTP_OK} from '../../utils/constants';
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
        apiUrl: {
            type: String,
            required: true,
        },
        post: {
            type: Object,
            default: () => ({
                content: '',
                amount: '0',
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
            minContentLength: 2,
            maxContentLength: 1000,
            maxDecimals: 4,
            maxAmount: 999999.9999,
            contentError: false,
            contentErrorMessage: '',
            amountError: false,
            amountErrorMessage: '',
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
                return 'Content can\'t be empty or contain only bbcodes and whitespaces';
            }
            if (!this.$v.content.minLength) {
                return `Content must be at least ${this.minContentLength} characters long`;
            }
            if (!this.$v.content.maxLength) {
                return `Content can't be more than ${this.maxContentLength} characters long`;
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
                return 'Amount is required';
            }
            if (!this.$v.amount.decimal) {
                return 'Amount must be a numeric value';
            }
            if (!this.$v.amount.maxDecimals) {
                return `Amount can only have at most ${this.maxDecimals} decimals`;
            }
            if (!this.$v.amount.between) {
                return `Amount must be between 0 and ${this.maxAmount}`;
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

            this.$axios.single.post(this.apiUrl, {
                content: this.content,
                amount: this.amount,
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
            }
        },
        reset() {
            this.content = '';
            this.amount = '0';
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
    },
    validations() {
        return {
            content: {
                required: (val) => {
                    return required(val.replace(/\[\s*\/?\s*(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)\s*\]/g, '').trim());
                },
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
        };
    },
};
</script>
