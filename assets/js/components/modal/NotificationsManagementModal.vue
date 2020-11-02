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
                        <template v-for="nType in notificationsType">
                            <faq-item>
                                <template slot="title"> {{ nType.type }} </template>
                                <template slot="body">
                                    <template v-for="nType in notificationsType">
                                        <div class="mb-2">
                                            <span> Email </span>
                                            <b-form-checkbox class="float-right" size="lg" v-model="nType.email" name="check-button" switch>
                                            </b-form-checkbox>
                                        </div>
                                        <div>
                                            <span> Website </span>
                                            <b-form-checkbox class="float-right" size="lg" v-model="nType.website" name="check-button" switch>
                                            </b-form-checkbox>
                                        </div>
                                    </template>
                                </template>
                            </faq-item>

                        </template>
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
        notificationsType: Array,
    },
    data() {
        return {
            email: false,
            website: false,
            selected: [], // Must be an array reference!
            options: [
                {text: '', value: 'email'}, // set translation tag
                {text: '', value: 'website'}, // set translation tag
            ],
        };
    },
};
</script>
