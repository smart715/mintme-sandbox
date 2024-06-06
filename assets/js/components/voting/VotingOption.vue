<template>
    <div class="d-flex align-items-center">
        <div class="flex-1 position-relative">
            <m-input
                :value="title"
                :invalid="!!option.errorMessage"
                input-tab-index="1"
                @input="update"
            >
                <template v-slot:label>
                    {{ $t('voting.option') }} {{ countOption }}
                </template>
                <template v-slot:errors>
                    <div v-if="!!option.errorMessage">
                        {{ option.errorMessage }}
                    </div>
                </template>
            </m-input>
        </div>
        <span class="ml-2 mb-4">
            <font-awesome-icon
                v-if="canDeleteOptions"
                class="c-pointer"
                icon="times"
                fixed-width
                @click="$emit('delete-option')"
            />
        </span>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {mapGetters} from 'vuex';
import {maxLength, required} from 'vuelidate/lib/validators';
import {MInput} from '../UI';
import {NoBadWordsMixin} from '../../mixins';

library.add(faTimes);

const maxTitleLength = 32;

export default {
    name: 'VotingOption',
    components: {
        FontAwesomeIcon,
        MInput,
    },
    mixins: [NoBadWordsMixin],
    props: {
        option: Object,
        element: Number,
    },
    data() {
        return {
            titleBadWordMessage: '',
        };
    },
    computed: {
        ...mapGetters('voting', {
            canDeleteOptions: 'canDeleteOptions',
        }),
        title() {
            return this.option.title;
        },
        countOption() {
            return this.element + 1;
        },
    },
    methods: {
        update(value) {
            const option = {
                ...this.option,
                title: value.trim(),
            };
            this.updateOption(option);
        },
        updateOption(option) {
            this.$emit('update-option', option);
        },
        validateOption() {
            if (!this.$v.title.required && this.$v.title.$dirty) {
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
            if (!this.$v.title.noBadWords) {
                return this.updateOption({
                    ...this.option,
                    errorMessage: this.titleBadWordMessage,
                });
            }

            this.updateOption({
                ...this.option,
                errorMessage: null,
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
                required: (val) => required(val),
                maxLength: maxLength(maxTitleLength),
                noBadWords: async () => {
                    if (window.location.pathname === this.$routing.generate('create_voting')) {
                        const isValid = await this.noBadWordsValidator('title', 'titleBadWordMessage');
                        this.updateOption({
                            ...this.option,
                            errorMessage: this.titleBadWordMessage,
                        });
                        return isValid;
                    }
                    return true;
                },
            },
        };
    },
};
</script>
