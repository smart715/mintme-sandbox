<template>
    <div class="card">
        <div
            class="card-body p-0 description"
            :class="{'closed': !shouldUnfoldDescription}"
        >
            <div class="fix-height custom-scrollbar">
                <div class="overflow-hidden">
                    <span>
                        <font-awesome-icon
                            v-if="showEditIcon"
                            class="float-right c-pointer icon-default"
                            icon="pen"
                            transform="shrink-4 up-1.5"
                            @click="editingDescription = true"
                        />
                    </span>
                    <div id="description-text">
                        <div :class="{'show-hide-text': showMore}" ref="hide">
                            <plain-text-view v-if="!editingDescription && description" :text="description" />
                            <div v-if="!editingDescription && !description" class="text-muted text-center mb-3">
                                {{ $t('page.pair.no_description') }}
                            </div>
                        </div>
                        <div
                            v-show="shouldShowMoreBtn"
                            :class="{'opened': !showMore}"
                            class="text-center show-more-wrp"
                        >
                            <button
                                class="btn btn-secondary-rounded"
                                @click="toggleDescription"
                            >
                                {{ showMessage }}
                            </button>
                        </div>
                    </div>
                    <template v-if="editable">
                        <div v-if="editingDescription">
                            <div class="pb-1">
                                {{ $t('token.intro.description.plan.header') }}
                                <guide>
                                    <template slot="header">
                                        {{ $t('token.intro.description.plan.guide_header') }}
                                    </template>
                                    <template slot="body">
                                        <span v-html="this.$t(
                                            'token.intro.description.plan.guide_body',
                                            translationsContext
                                        )"></span>
                                    </template>
                                </guide>
                            </div>
                            <counted-textarea
                                :rows="5"
                                :value="newDescriptionHtmlDecode"
                                :invalid="$v.$invalid && newDescription.length > 0"
                                editable
                                @change="onDescriptionChange"
                                @input="onDescriptionChange"
                            >
                                <template v-slot:label>
                                    <span class="token-intro-description-plan label-bg-primary-dark">
                                        {{ $t('token.intro.description.plan') }}
                                    </span>
                                </template>
                                <template v-slot:errors>
                                    <div v-if="newDescription.length > 0 && !$v.newDescription.minLength">
                                        {{ $t('token.intro.description.min_length', translationsContext) }}
                                    </div>
                                    <div v-if="!$v.newDescription.maxLength">
                                        {{ $t('token.intro.description.max_length', translationsContext) }}
                                    </div>
                                    <div
                                        v-if="!$v.newDescription.noBadWords"
                                        v-text="newDescriptionBadWordMessage"
                                    ></div>
                                </template>
                            </counted-textarea>
                            <div class="mb-4">
                                <m-button
                                    tabindex="0"
                                    type="primary"
                                    :disabled="$v.$invalid || !readyToSave"
                                    :loading="saving"
                                    @click="editDescription"
                                >
                                    {{ $t('save') }}
                                </m-button>
                                <span
                                    class="btn-cancel pl-3 c-pointer"
                                    tabindex="1"
                                    @click="cancelEditing"
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
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faPen} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../../Guide';
import {CountedTextarea, MButton} from '../../UI';
import PlainTextView from '../../UI/PlainTextView';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {descriptionLength} from '../../../utils/constants';
import {
    NotificationMixin,
    ClearInputMixin,
    NoBadWordsMixin,
} from '../../../mixins';
import TruncateFilterMixin from '../../../mixins/filters/truncate';
import he from 'he';

const DESCRIPTION_TRUNCATE_LENGTH = 70;

library.add(faPen);

export default {
    name: 'TokenIntroductionDescription',
    mixins: [
        NotificationMixin,
        ClearInputMixin,
        NoBadWordsMixin,
        TruncateFilterMixin,
    ],
    props: {
        description: String,
        editable: Boolean,
        name: String,
        shouldUnfoldDescription: Boolean,
        isMobileScreen: Boolean,
    },
    components: {
        CountedTextarea,
        PlainTextView,
        FontAwesomeIcon,
        Guide,
        MButton,
    },
    data() {
        return {
            editingDescription: false,
            newDescription: this.description || '',
            readyToSave: false,
            showMore: true,
            height: 0,
            resizeObserver: null,
            saving: false,
            newDescriptionBadWordMessage: '',
        };
    },
    mounted: function() {
        this.$nextTick()
            .then(() => {
                this.resizeObserver = new ResizeObserver(this.updateHeight.bind(this));
                this.resizeObserver.observe(this.$refs.hide);
            });

        window.addEventListener('scroll', () => {
            if (!this.isMobileScreen &&
                !this.editingDescription &&
                this.showMore &&
                this.shouldUnfoldDescription
            ) {
                this.$emit('fold', false);
            }
        });
    },
    beforeDestroy() {
        this.resizeObserver.disconnect();
    },
    computed: {
        showEditIcon: function() {
            return !this.editingDescription && this.editable;
        },
        showMessage() {
            return this.showMore ? this.$t('read_more') : this.$t('read_less');
        },
        shouldShowMoreBtn: function() {
            return 239 <= this.height;
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
        updateHeight() {
            const styles = window.getComputedStyle(this.$refs.hide, null);
            const height = parseFloat(styles.getPropertyValue('height'));
            const topPadding = parseFloat(styles.getPropertyValue('padding-top'));
            const bottomPadding = parseFloat(styles.getPropertyValue('padding-bottom'));
            this.height = height - topPadding - bottomPadding;
        },
        toggleDescription: function() {
            this.showMore = !this.showMore;
        },
        editDescription: async function() {
            this.$v.$touch();

            if (this.$v.$invalid) {
                return;
            }

            this.readyToSave = false;
            this.saving = true;
            try {
                const response = await this.$axios.single.patch(this.$routing.generate('token_update', {
                    name: this.name,
                }), {
                    description: this.newDescriptionHtmlDecode,
                });
                const truncatedDescription = this.truncateFunc(
                    response.data.newDescription,
                    DESCRIPTION_TRUNCATE_LENGTH
                );

                this.newDescription = response.data.newDescription;
                document.title = `${this.name} ${truncatedDescription}`;
                this.$emit('updated', this.newDescription);
            } catch (error) {
                this.readyToSave = true;
                this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                this.$logger.error('Error while editing description', error);
            } finally {
                this.editingDescription = false;
                this.saving = false;
            }
        },
        cancelEditing() {
            if (this.saving) {
                return;
            }

            this.editingDescription = false;
        },
    },
    validations() {
        return {
            newDescription: {
                required,
                minLength: minLength(descriptionLength.min),
                maxLength: maxLength(descriptionLength.max),
                changed: () => this.description !== this.newDescriptionHtmlDecode,
                noBadWords: () => this.noBadWordsValidator('newDescription', 'newDescriptionBadWordMessage'),
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

<style lang="scss" scoped>
    p {
        white-space: pre-line;
        word-break: break-word;
    }
</style>
