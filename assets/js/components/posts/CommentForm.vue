<template>
    <div class="form-group">
        <textarea
            class="form-control mb-3"
            :class="{ 'is-invalid' : contentInvalid }"
            v-model="content"
            @focus="goToLogIn"
        ></textarea>
        <div class="invalid-feedback">
            {{ contentError }}
        </div>
        <button
            class="btn btn-primary"
            @click="submit"
            :disabled="$v.content.$invalid"
        >
          {{ $t('save') }}
        </button>
        <button
            class="btn btn-cancel"
            @click="cancel"
        >
          {{ $t('cancel') }}
        </button>
    </div>
</template>

<script>
import {required, minLength, maxLength} from 'vuelidate/lib/validators';

export default {
    name: 'CommentForm',
    props: {
        loggedIn: Boolean,
        propContent: {
            type: String,
            default: '',
        },
        apiUrl: String,
        resetAfterSubmit: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            content: this.propContent,
            loginUrl: this.$routing.generate('login', {}, true),
            minContentLength: 1,
            maxContentLength: 500,
        };
    },
    computed: {
        contentInvalid() {
            return this.$v.content.$invalid && this.content.length > 0;
        },
        contentError() {
            if (!this.$v.content.required) {
                return 'Content can\'t be empty';
            }
            if (!this.$v.content.minLength) {
                return `Content must be at least ${this.minContentLength} characters long`;
            }
            if (!this.$v.content.maxLength) {
                return `Content can't be more than ${this.maxContentLength} characters long`;
            }
            return '';
        },
    },
    methods: {
        submit() {
            if (!this.loggedIn) {
                location.href = this.loginUrl;
                return;
            }

            if (this.$v.content.$invalid) {
                return;
            }

            this.$axios.single.post(this.apiUrl, {content: this.content})
            .then((res) => {
                this.$emit('submitted', res.data.comment);

                if (this.resetAfterSubmit) {
                    this.content = '';
                }
            })
            .catch(() => {
                this.$emit('error');
            });
        },
        cancel() {
            if (!this.loggedIn) {
                location.href = this.loginUrl;
                return;
            }

            this.content = this.propContent;
            this.$emit('cancel');
        },
        goToLogIn(e) {
            if (!this.loggedIn) {
                e.target.blur();
                location.href = this.loginUrl;
            }
        },
    },
    watch: {
        propContent() {
            this.content = this.propContent();
        },
    },
    validations() {
        return {
            content: {
                required: (val) => required(val.trim()),
                minLength: minLength(this.minContentLength),
                maxLength: maxLength(this.maxContentLength),
            },
        };
    },
};
</script>
