<template>
    <div class="form-control-container">
        <div
            class="form-control-field"
            :class="formControlFieldClass"
        >
            <input
                v-model="localValue"
                ref="input"
                type="text"
                class="form-control"
                :name="name"
                :maxlength="maxLength || ''"
                :autocomplete="autocomplete"
                :tabindex="inputTabIndex"
                :placeholder="placeholder"
                @input="onInput"
                @change="onChange"
                @keyup="$emit('keyup', $event)"
                @keypress="$emit('keypress', $event)"
                @paste="$emit('paste', $event)"
                @focus="$emit('focus', $event)"
            />
            <div class="postfix-icon-container d-flex align-items-center">
                <slot v-if="!loading" name="postfix-icon"></slot>
                <template v-if="loading">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </template>
            </div>
            <div class="outline">
                <div class="left-outline"></div>
                <div class="label-outline">
                    <label :for="name">
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

export default {
    name: 'm-input',
    mixins: [FormControl, FormControlCounter],
    props: {
        value: {
            type: [String, Number],
        },
        maxLength: {
            type: Number,
            default: null,
        },
        autocomplete: String,
        inputTabIndex: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            localValue: this.value,
        };
    },
    methods: {
        focus() {
            this.$refs.input.focus();
        },
        onChange: function() {
            this.$emit('change', this.localValue);
        },
        onInput: function() {
            this.$emit('input', this.localValue);
        },
    },
    watch: {
        value: function() {
            this.localValue = this.value;
        },
    },
};
</script>
