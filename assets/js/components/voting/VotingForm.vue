<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">{{ $t('voting.create') }}</slot>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title">
                    {{ $t('voting.form.title') }}
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
                <label>
                    {{ $t('voting.form.description') }}
                </label>
                <bbcode-help class="float-right mt-2" placement="right" />
                <bbcode-editor class="form-control w-100"
                   :class="{ 'is-invalid' : invalidDescription }"
                   :value="description"
                   @change="onDescriptionChange"
                   @input="onDescriptionChange"
                   ref="input"
                />
                <div class="invalid-feedback"
                     :class="{ 'd-block' : invalidDescription }"
                >
                    {{ invalidDescriptionMessage }}
                </div>
            </div>
            <div class="form-group">
                <label>{{ $t('voting.form.end_date') }}</label>
                <date-picker
                    v-model="endDate"
                    :config="endDateOptions">
                </date-picker>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import DatePicker from 'vue-bootstrap-datetimepicker';
import BbcodeEditor from '../bbcode/BbcodeEditor';
import BbcodeHelp from '../bbcode/BbcodeHelp';
import {CheckInputMixin, NotificationMixin} from '../../mixins';
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
    ],
    components: {
        DatePicker,
        BbcodeEditor,
        BbcodeHelp,
    },
    data() {
        return {
            endDateOptions: {
                format: GENERAL.dateTimeFormatPicker,
                useCurrent: false,
                minDate: moment().add(1, 'hour').toDate(),
            },
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
                this.setTitle(val);
            },
        },
        description: {
            get: function() {
                return this.getDescription;
            },
            set: function(val) {
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
            return this.$v.title.$invalid && this.title.length > 0;
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

            return '';
        },
        invalidDescription() {
            return this.$v.description.$invalid && this.description.length > 0;
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
            },
            title: {
                required: (val) => required(val.trim()),
                minLength: minLength(minTitleLength),
                maxLength: maxLength(maxTitleLength),
            },
        };
    },
};
</script>
