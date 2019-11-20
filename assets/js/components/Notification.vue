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
        },
        mounted: function() {
            this.type = this.type === 'danger' ? 'error' : this.type;
            this.type = this.type === 'primary' ? 'info' : this.type;

            this.sendNotification(this.$refs.content.innerHTML, this.type);
        },
    };
</script>
