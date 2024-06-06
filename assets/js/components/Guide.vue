<template>
    <div :class="classProp">
        <a v-tippy="tooltipOptions"
           :class="tippyClass"
        >
            <slot name="icon">
                <font-awesome-icon
                    icon="question"
                    slot='icon'
                    class="ml-1 text-white
                    rounded-circle square guide-icon"/>
            </slot>
        </a>
        <div :id="id" :class="{'d-none': !reactive}">
            <slot name="template">
                <div class="m-2">
                    <h5 class="font-bold">
                        <slot name="header"></slot>
                    </h5>
                    <p class="overflow-wrap-break-word">
                    <slot name="body"></slot>
                    </p>
                </div>
            </slot>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faQuestion} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faQuestion);

export default {
    name: 'Guide',
    components: {
        FontAwesomeIcon,
    },
    props: {
        maxWidth: {type: String, default: '350px'},
        placement: {type: String, default: 'bottom'},
        tippyClass: {type: String, default: ''},
        reactive: {type: Boolean, default: false},
        boundariesElement: {type: String, default: 'scrollParent'},
        classProp: {type: String, default: 'd-inline'},
    },
    data() {
        return {
            id: null,
        };
    },
    computed: {
        tooltipOptions: function() {
            if (null !== this.id) {
                return {
                    placement: this.placement,
                    html: '#' + this.id,
                    arrow: true,
                    interactive: true,
                    reactive: this.reactive,
                    theme: 'light',
                    delay: [200, 0],
                    maxWidth: this.maxWidth,
                    popperOptions: {
                        modifiers: {
                            preventOverflow: {
                                boundariesElement: this.boundariesElement,
                            },
                        },
                    },
                };
            }
            return null;
        },
    },
    created: function() {
        this.id = 'guide_' + this._uid;
    },
};
</script>

