<template>
    <div>
        <div class="card h-100">
            <div class="card-header">
                {{ $t('token.intro.description.header') }}
                <guide class="float-right">
                    <template  slot="header">
                        {{ $t('token.intro.description.guide_header') }}
                    </template>
                    <template slot="body">
                        <span v-html="this.$t('token.intro.description.guide_body', translationsContext)"></span>
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <div class="row fix-height custom-scrollbar">
                    <div class="col-12 overflow-y-hidden">
                        <span class="card-header-icon">
                            <font-awesome-icon
                                v-if="showEditIcon"
                                class="float-right c-pointer icon-default"
                                icon="edit"
                                transform="shrink-4 up-1.5"
                                @click="editingDescription = true"
                            />
                        </span>
                        <bbcode-view v-if="!editingDescription" :value="description" />
                        <template v-if="editable">
                            <div v-show="editingDescription">
                                <div class="pb-1">
                                    {{ $t('token.intro.description.plan.header') }}
                                    <guide>
                                        <template slot="header">
                                            {{ $t('token.intro.description.plan.guide_header') }}
                                        </template>
                                        <template slot="body">
                                            <span v-html="this.$t('token.intro.description.plan.guide_body', translationsContext)"></span>
                                        </template>
                                    </guide>
                                    <bbcode-help class="d-inline"/>
                                </div>
                                <div class="pb-1 text-xs">{{ $t('token.intro.description.plan') }}</div>
                                <bbcode-editor
                                    rows="5"
                                    class="form-control"
                                    :class="{ 'is-invalid': $v.$invalid && newDescription.length > 0 }"
                                    :value="newDescriptionHtmlDecode"
                                    @change="onDescriptionChange"
                                    @input="onDescriptionChange"
                                />
                                <div v-if="newDescription.length > 0 && !$v.newDescription.minLength"
                                     class="text-sm text-danger">
                                    {{ $t('token.intro.description.min_length', translationsContext) }}
                                </div>
                                <div v-if="!$v.newDescription.maxLength" class="text-sm text-danger">
                                    {{ $t('token.intro.description.max_length', translationsContext) }}
                                </div>
                                <div class="text-left pt-3">
                                    <button
                                        class="btn btn-primary"
                                        :disabled="$v.$invalid || !readyToSave"
                                        @click="editDescription"
                                        @keyup.enter="editDescription"
                                        tabindex="0"
                                    >
                                        {{ $t('save') }}
                                    </button>
                                    <span
                                        class="btn-cancel pl-3 c-pointer"
                                        @click="editingDescription = false"
                                        @keyup.enter="editingDescription = false"
                                        tabindex="0"
                                    >
                                        {{ $t('cancel') }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../../Guide';
import BbcodeEditor from '../../bbcode/BbcodeEditor';
import BbcodeHelp from '../../bbcode/BbcodeHelp';
import BbcodeView from '../../bbcode/BbcodeView';
import LimitedTextarea from '../../LimitedTextarea';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {descriptionLength} from '../../../utils/constants';
import {LoggerMixin, NotificationMixin} from '../../../mixins';
import he from 'he';

library.add(faEdit);

export default {
    name: 'TokenIntroductionDescription',
    mixins: [NotificationMixin, LoggerMixin],
    props: {
        description: String,
        editable: Boolean,
        name: String,
    },
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        FontAwesomeIcon,
        Guide,
        LimitedTextarea,
    },
    data() {
        return {
            editingDescription: false,
            newDescription: this.description || '',
            readyToSave: false,
        };
    },
    computed: {
        showEditIcon: function() {
            return !this.editingDescription && this.editable;
        },
        newDescriptionHtmlDecode: function() {
            return he.decode(this.newDescription);
        },
        translationsContext: function() {
            return {
                minDescriptionLength: descriptionLength.min,
                maxDescriptionLength: descriptionLength.max,
                name: this.name,
            };
        },
    },
    methods: {
        onDescriptionChange: function(val) {
            this.newDescription = he.encode(val);
            this.readyToSave = true;
        },
        editDescription: function() {
            this.$v.$touch();
            this.readyToSave = false;
            if (this.$v.$invalid) {
                if (!this.$v.newDescription.minLength || !this.$v.newDescription.required) {
                    this.notifyError(this.$t('token.intro.description.min_length'));
                } else if (!this.$v.newDescription.maxLength) {
                    this.notifyError(
                        this.$t('token.intro.description.max_length', this.translationsContext)
                    );
                }
                return;
            }

            this.$axios.single.patch(this.$routing.generate('token_update', {
                name: this.name,
            }), {
                description: this.newDescriptionHtmlDecode,
            })
                .then((response) => {
                    this.newDescription = response.data.newDescription;
                    this.$emit('updated', this.newDescription);
                }, (error) => {
                    this.readyToSave = true;
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.sendLogs('error', 'Edit description network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.sendLogs('error', 'Can not edit description', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.sendLogs('error', 'An error has occurred, please try again later', error);
                    }
                })
                .then(() => {
                    this.editingDescription = false;
                    this.icon = 'edit';
                });
        },
    },
    validations() {
        return {
            newDescription: {
                required,
                minLength: minLength(descriptionLength.min),
                maxLength: maxLength(descriptionLength.max),
            },
        };
    },
    watch: {
        description: function(val) {
            this.newDescription = val;
        },
    },
};
</script>

<style lang="sass" scoped>
    p
        white-space: pre-line
        word-break: break-word
</style>
