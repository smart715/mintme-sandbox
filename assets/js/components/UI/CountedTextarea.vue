<template>
    <m-textarea
        v-model="localValue"
        :label="label"
        :rows="rows"
        :invalid="invalid"
        :name="name"
        :textarea-tab-index="textareaTabIndex"
        :editable="editable"
        @change="onChange($event)"
        @input="onInput($event)"
    >
        <template v-slot:label>
            <slot name="label"></slot>
        </template>
        <template v-slot:errors>
            <slot name="errors"></slot>
        </template>
        <template v-if="minLength" v-slot:assistive-postfix>
            <div v-if="isNotEnoughLength" :class="{'text-danger' : isNotEmpty}">
                <span>
                    {{ (localValue || '').length }} / {{ minLength }}
                </span>
            </div>
            <template v-else>
                <font-awesome-icon
                    :icon="['fas', 'check']"
                    class="text-success"
                />
            </template>
        </template>
    </m-textarea>
</template>

<script>
import {MTextarea} from '.';
import {faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';

library.add(faCheck);

export default {
    name: 'CountedTextarea',
    components: {
        MTextarea,
        FontAwesomeIcon,
    },
    props: {
        value: String,
        name: String,
        label: {
            type: String,
            default: 'Editor',
        },
        rows: {
            type: Number,
            default: 5,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        minLength: {
            type: Number,
            default: null,
        },
        invalid: {
            type: Boolean,
            default: false,
        },
        labelPointerEvents: Boolean,
        textareaTabIndex: {
            type: String,
            default: '',
        },
        editable: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            localValue: this.value,
            textareaId: null,
        };
    },
    created: function() {
        this.textareaId = `textarea_${this._uid}`;
    },
    mounted: function() {
        this.$nextTick(() => {
            const textareaEl = this.$el.querySelector('#' + this.textareaId);

            if (!textareaEl) {
                return;
            }

            textareaEl.addEventListener('input', this.resizeTextArea);
            textareaEl.dispatchEvent(new Event('input'));
        });
    },
    beforeDestroy() {
        this.$el.removeEventListener('input', this.resizeTextArea);
    },
    computed: {
        isNotEnoughLength: function() {
            return this.minLength && (this.localValue || '').length < this.minLength;
        },
        isNotEmpty: function() {
            return 0 < (this.localValue || '').length;
        },
    },
    methods: {
        onChange: function() {
            this.$emit('change', this.localValue);
        },
        onInput: function() {
            this.$emit('input', this.localValue);
        },
    },
    watch: {
        value() {
            this.localValue = this.value;
        },
    },
};
</script>
