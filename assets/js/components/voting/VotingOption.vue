<template>
    <div class="d-flex align-items-center m-2">
        <div class="flex-1">
            <input
                :value="title"
                :class="{ 'is-invalid' : option.errorMessage }"
                class="form-control bg-primary text-center w-100"
                type="text"
                @input="update"
            >
            <div class="invalid-feedback">
                {{ option.errorMessage }}
            </div>
        </div>
        <font-awesome-icon
            v-if="canDeleteOptions"
            class="c-pointer"
            icon="times"
            fixed-width
            @click="$emit('delete-option')"
        />
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {mapGetters} from 'vuex';
import {maxLength, required} from 'vuelidate/lib/validators';

library.add(faTimes);

const maxTitleLength = 32;

export default {
    name: 'VotingOption',
    components: {
        FontAwesomeIcon,
    },
    props: {
        option: Object,
    },
    computed: {
        ...mapGetters('voting', {
            canDeleteOptions: 'canDeleteOptions',
        }),
        title() {
            return this.option.title;
        },
    },
    methods: {
        update(e) {
            const option = {
                ...this.option,
                title: e.target.value,
            };
            this.updateOption(option);
        },
        updateOption(option) {
            this.$emit('update-option', option);
        },
        validateOption() {
            if (!this.$v.title.required) {
                return this.updateOption({
                    ...this.option,
                    errorMessage: this.$t('form.validation.option.required'),
                });
            }

            if (!this.$v.title.maxLength) {
                return this.updateOption({
                    ...this.option,
                    errorMessage: this.$t('form.validation.option.max', {length: maxTitleLength}),
                });
            }

            this.updateOption({
                ...this.option,
                errorMessage: '',
            });
        },
    },
    watch: {
        title() {
            this.validateOption();
        },
    },
    validations() {
        return {
            title: {
                required: (val) => required(val.trim()),
                maxLength: maxLength(maxTitleLength),
            },
        };
    },
};
</script>
