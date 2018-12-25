<template>
    <div class="d-inline">
        <a v-tippy="tooltipOptions">
            <slot name="icon">
                <font-awesome-icon
                    icon="question"
                    class="m-0 p-1 h4 bg-orange rounded-circle square"
                    />
            </slot>
        </a>
        <div :id="id" class="d-none">
            <slot name="template">
                <div class="m-2">
                    <h5 class="font-bold">
                        <slot name="header"></slot>
                    </h5>
                    <p>
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
    data() {
        return {
            id: null,
        };
    },
    computed: {
        tooltipOptions: function() {
            if (this.id !== null) {
                return {
                    placement: 'bottom',
                    html: '#' + this.id,
                    arrow: true,
                    interactive: true,
                    theme: 'light',
                    delay: [200, 0],
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

