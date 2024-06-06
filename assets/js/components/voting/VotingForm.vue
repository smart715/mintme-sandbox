<template>
    <div class="row ml-0 mr-0">
        <div class="col-md-12">
            <div class="card">
                <div class="m-4">
                    <m-input
                        id="title"
                        name="title"
                        :label="$t('voting.form.title')"
                        :invalid="invalidTitle"
                        v-model="title"
                        input-tab-index="1"
                    >
                        <template v-slot:errors>
                            <div v-if="invalidTitle">
                                {{ invalidTitleMessage }}
                            </div>
                        </template>
                    </m-input>

                    <counted-textarea
                        ref="input"
                        :invalid="invalidDescription"
                        :value="description"
                        textarea-tab-index="1"
                        editable
                        @input="onDescriptionChange"
                        @change="onDescriptionChange"
                    >
                        <template v-slot:label>
                            <span class="label-bg-primary-dark">
                                {{ $t('voting.form.description') }}
                            </span>
                        </template>
                        <template v-slot:errors>
                            <div v-if="invalidDescription">
                                {{ invalidDescriptionMessage }}
                            </div>
                        </template>
                    </counted-textarea>

                    <div class="mt-2 position-relative form-group row m-0">
                        <label class="text-primary label-top label pr-1 pl-1 pt-0 pb-0 mb-1">
                            {{ $t('voting.form.end_date') }}
                        </label>
                        <date-picker
                            class="col-sm-6 input-label p-3 input-size"
                            v-model="endDate"
                            :config="endDateOptions"
                            tabindex="1"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import DatePicker from '../DatePicker';
import {CountedTextarea, MInput} from '../UI';
import {
    CheckInputMixin,
    NotificationMixin,
    ClearInputMixin,
    NoBadWordsMixin,
} from '../../mixins';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {requiredBBCText, GENERAL} from '../../utils/constants';
import {mapGetters, mapMutations} from 'vuex';

const minTitleLength = 5;
const maxTitleLength = 100;
const minDescriptionLength = 100;
const maxDescriptionLength = 1000;

export default {
    name: 'VotingForm',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
        ClearInputMixin,
        NoBadWordsMixin,
    ],
    components: {
        DatePicker,
        CountedTextarea,
        MInput,
    },
    data() {
        return {
            endDateOptions: {
                format: GENERAL.dateTimeFormatPicker,
                useCurrent: false,
                minDate: moment().add(1, 'hour').toDate(),
            },
            titleBadWordMessage: '',
            descriptionBadWordMessage: '',
        };
    },
    computed: {
        ...mapGetters('voting', [
            'getTitle',
            'getDescription',
            'getEndDate',
        ]),
        title: {
            get: function() {
                return this.getTitle;
            },
            set: function(val) {
                this.titleBadWordMessage = '';
                this.setTitle(val);
            },
        },
        description: {
            get: function() {
                return this.getDescription;
            },
            set: function(val) {
                this.descriptionBadWordMessage = '';
                this.setDescription(val);
            },
        },
        endDate: {
            get: function() {
                return moment(this.getEndDate, GENERAL.dateTimeFormatPicker).toDate();
            },
            set: function(val) {
                this.setEndDate(val);
            },
        },
        invalidTitle() {
            return this.$v.title.$invalid && 0 < this.title.length;
        },
        invalidTitleMessage() {
            if (!this.$v.title.required) {
                return this.$t('form.validation.title.min', {length: minTitleLength});
            }

            if (!this.$v.title.minLength) {
                return this.$t('form.validation.title.min', {length: minTitleLength});
            }

            if (!this.$v.title.maxLength) {
                return this.$t('form.validation.title.max', {length: maxTitleLength});
            }

            if (!this.$v.title.noBadWords) {
                return this.titleBadWordMessage;
            }

            return '';
        },
        invalidDescription() {
            return this.$v.description.$invalid && 0 < this.description.length;
        },
        invalidDescriptionMessage() {
            if (!this.$v.description.required) {
                return this.$t('form.validation.description.required');
            }
            if (!this.$v.description.minLength) {
                return this.$t('form.validation.description.min', {length: minDescriptionLength});
            }
            if (!this.$v.description.maxLength) {
                return this.$t('form.validation.description.max', {length: maxDescriptionLength});
            }
            if (!this.$v.description.noBadWords) {
                return this.descriptionBadWordMessage;
            }

            return '';
        },
        invalidForm() {
            return this.$v.$invalid;
        },
    },
    methods: {
        ...mapMutations('voting', [
            'setTitle',
            'setDescription',
            'setEndDate',
            'setInvalidForm',
        ]),
        onDescriptionChange(description) {
            this.description = description;
        },
    },
    watch: {
        invalidForm(val) {
            this.setInvalidForm(val);
        },
    },
    validations() {
        return {
            description: {
                required: requiredBBCText,
                minLength: minLength(minDescriptionLength),
                maxLength: maxLength(maxDescriptionLength),
                noBadWords: () => {
                    if (window.location.pathname === this.$routing.generate('create_voting')) {
                        return this.noBadWordsValidator('description', 'descriptionBadWordMessage');
                    }
                    return true;
                },
            },
            title: {
                required: (val) => required(val.trim()),
                minLength: minLength(minTitleLength),
                maxLength: maxLength(maxTitleLength),
                noBadWords: () => {
                    if (window.location.pathname === this.$routing.generate('create_voting')) {
                        return this.noBadWordsValidator('title', 'titleBadWordMessage');
                    }
                    return true;
                },
            },
        };
    },
};
</script>
