<template>
    <div class="form-control-container">
        <div
            class="form-control-field"
            :class="formControlFieldClass"
        >
            <textarea
                v-if="!editable"
                :id="inputId"
                v-model="localValue"
                :name="name"
                :maxlength="maxLength || ''"
                :rows="rows"
                :tabindex="textareaTabIndex"
                :disabled="disabled"
                class="form-control custom-scrollbar"
                :contenteditable="editable"
                @input="onInput"
                @change="onChange"
                @click="onClick"
            ></textarea>
            <div
                v-if="editable"
                contenteditable="true"
                :tabindex="textareaTabIndex"
                :style="{'min-height': `${rows * 2}em`}"
                class="p-4 content-editable-textarea position-relative custom-scrollbar"
                ref="editable"
                @input="onEditableBlockInput()"
                @keydown="onEditableKeyDown"
                @click="onEditableBlockClick"
                @blur="onEditableBlockBlur"
                @paste="onEditablePaste"
            ></div>
            <div
                v-if="editable"
                v-show="foundHashtags.length"
                :id="getHashtagsRecommendationHintId()"
                class="hashtags-recommendation-hint position-absolute"
            >
                <div
                    v-for="hashtag in foundHashtags"
                    :key="hashtag.value"
                    @mousedown.prevent="chooseHashtag($event, hashtag.value)"
                >#{{ hashtag.value }}</div>
            </div>
            <div class="postfix-icon-container d-flex align-items-center">
                <slot v-if="!loading" name="postfix-icon"></slot>
                <template v-if="loading">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </template>
            </div>
            <div class="outline">
                <div class="left-outline"></div>
                <div class="label-outline">
                    <label :for="name" :class="{'pe-all' : labelPointerEvents}">
                        <slot name="label">{{ label }}</slot>
                    </label>
                </div>
                <div class="right-outline"></div>
            </div>
        </div>
        <div class="assistive d-flex">
            <div v-if="!hasErrors" class="hint flex-1">
                <slot name="hint">{{ hint }}</slot>
            </div>
            <div v-if="hasErrors" class="errors flex-1">
                <slot name="errors"></slot>
            </div>
            <div v-if="counter" class="input-counter">
                {{ valueLength }} / {{ maxLength }}
            </div>
            <div class="input-counter">
                <slot name="assistive-postfix"></slot>
            </div>
        </div>
    </div>
</template>

<script>
import FormControl from './FormControl';
import FormControlCounter from './FormControlCounter';
import ContentEditableTextarea from './ContentEditableTextarea';

export default {
    name: 'm-textarea',
    mixins: [FormControl, FormControlCounter, ContentEditableTextarea],
    props: {
        maxLength: {
            type: Number,
            default: null,
        },
        inputId: {
            type: String,
            default: null,
        },
        rows: {
            type: Number,
            default: 5,
        },
        textareaTabIndex: {
            type: String,
            default: '',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        editable: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            localValue: this.value,
            textareaId: Math.floor(Math.random() * 1000),
        };
    },
    methods: {
        onChange() {
            this.$emit('change', this.localValue);
        },
        onInput() {
            this.$emit('input', this.localValue);
        },
    },
    watch: {
        value() {
            this.localValue = this.value;
            this.handleContentEditableValueWatch();
        },
    },
};
</script>
