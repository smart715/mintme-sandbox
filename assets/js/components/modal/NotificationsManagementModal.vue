<template>
    <div>
        <modal
            :visible="visible"
            :no-close="false"
            :without-padding="true"
            @close="$emit('close')"
        >
            <template slot="header"> Notifications Settings <!-- {{ $t(userNotification.manage) }}--> </template>
            <template slot="close"></template>
            <template slot="body">
                <div class="token-edit p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <!-- TODO make a loop to showing all notification  -->
                        <faq-item>
                            <template slot="title">

                            </template>
                            <template slot="body">

                            </template>
                        </faq-item>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';
import FaqItem from '../FaqItem';

export default {
    name: 'NotificationManagementModal',
    components: {
    Modal,
    FaqItem,
    },
    props: {
        visible: Boolean,
        noClose: Boolean,
    },
    data() {
        return {
            data: 'd',
        };
    },
};
</script>
