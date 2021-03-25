<template>
    <div ref="content" class="d-none">
        <slot></slot>
    </div>
</template>
<script>
    import {NotificationMixin} from '../mixins';

    export default {
        name: 'Notification',
        mixins: [NotificationMixin],
        props: {
            type: String,
            duration: {
                type: Number,
                default: 5000,
            },
        },
        mounted: function() {
            // todo: don't mutate prop
            // eslint-disable-next-line
            this.type = this.type === 'danger' ? 'error' : this.type;
            // todo: don't mutate prop
            // eslint-disable-next-line
            this.type = this.type === 'primary' ? 'info' : this.type;

            this.sendNotification(this.$refs.content.innerHTML, this.type, this.duration);
        },
    };
</script>
