<template>
    <div class="form-control-container">
        <div
            class="form-control-field"
            :class="formControlFieldClass"
        >
            <b-dropdown
                :text="text"
                :variant="type"
                toggle-class="text-left w-100"
            >
                <template v-if="$slots['button-content']" v-slot:button-content>
                    <slot name="button-content"></slot>
                </template>
                <slot></slot>
            </b-dropdown>
            <div class="postfix-icon-container d-flex align-items-center">
                <slot v-if="!loading" name="postfix-icon"></slot>
                <template v-if="loading">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </template>
            </div>
            <div class="outline">
                <div class="left-outline"></div>
                <div v-if="label" class="label-outline">
                    <label :for="name">{{ label }}</label>
                </div>
                <div class="right-outline"></div>
            </div>
        </div>
        <div v-if="!hideAssistive" class="assistive d-flex">
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
import {BDropdown} from 'bootstrap-vue';
import FormControl from './FormControl';

export default {
    name: 'm-dropdown',
    mixins: [FormControl],
    components: {
        BDropdown,
    },
    props: {
        text: {
            type: String,
        },
        type: {
            type: String,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        hideAssistive: {
            type: Boolean,
            default: false,
        },
        theme: {
            type: String,
        },
    },
    computed: {
        formControlFieldClass() {
            const formControlFieldClass = {
                'invalid': this.hasErrors || this.invalid,
                'disabled': this.disabled,
                'has-postfix-icon': this.loading,
            };

            if (this.theme) {
                formControlFieldClass[this.theme + '-theme'] = true;
            }

            return formControlFieldClass;
        },
    },
};
</script>
