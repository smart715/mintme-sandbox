<template>
    <div class="d-inline">
        <a v-tippy="tooltipOptions">
            <slot name="icon">
                <font-awesome-icon
                    icon="question"
                    slot='icon'
                    class="ml-1 text-white
                    rounded-circle square blue-question"/>
            </slot>
        </a>
        <div :id="id" class="d-none">
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
export default {
    name: 'Guide',
    props: {
        maxWidth: {type: String, default: '350px'},
        placement: {type: String, default: 'bottom'},
    },
    data() {
        return {
            id: null,
        };
    },
    computed: {
        tooltipOptions: function() {
            if (this.id !== null) {
                return {
                    placement: this.placement,
                    html: '#' + this.id,
                    arrow: true,
                    interactive: true,
                    theme: 'light',
                    delay: [200, 0],
                    maxWidth: this.maxWidth,
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

