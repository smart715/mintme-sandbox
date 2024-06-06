<template>
    <div class="form-control-container">
        <div
            class="form-control-field form-control-select"
            :class="formControlFieldClass"
        >
            <select
                :name="name"
                :value="value"
                :tabindex="selectTabIndex"
                @change="onChange($event)"
            >
                <slot></slot>
            </select>
            <div class="postfix-icon-container d-flex align-items-center">
                <slot v-if="!loading" name="postfix-icon"></slot>
                <template v-if="loading">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </template>
            </div>
            <div class="outline">
                <div class="left-outline"></div>
                <div class="label-outline">
                    <label :for="name">{{ label }}</label>
                </div>
                <div class="right-outline"></div>
            </div>
        </div>
        <div class="assistive d-flex">
            <div v-if="!hasErrors" class="hint flex-1">
                <slot name="hint">
                    {{ hint }}
                </slot>
            </div>
            <div v-if="hasErrors" class="errors flex-1">
                <slot name="errors"></slot>
            </div>
        </div>
    </div>
</template>

<script>
import FormControl from './FormControl';

export default {
    name: 'm-select',
    mixins: [FormControl],
    props: {
        type: {
            type: String,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        selectTabIndex: {
            type: String,
            default: '',
        },
    },
    methods: {
        onChange(event) {
            this.$emit('change', event.target.value);
        },
    },
};
</script>
