<template>
    <div class="form-control-container">
        <div
            class="form-control-field form-control-select"
            :class="formControlFieldClass"
        >
            <slot></slot>
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
    name: 'FormControlWrapper',
    mixins: [FormControl],
    props: {
        type: {
            type: String,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        labelPointerEvents: Boolean,
    },
};
</script>
