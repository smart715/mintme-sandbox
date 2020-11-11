<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :without-padding="true"
            @close="closeModal"
        >
            <template slot="header"> Notifications Settings</template>
            <template slot="close"></template>
            <template slot="body">
                <div class="p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <div>
                            <b-card>
                                <b-card-text>
                                    Receive notifications about:
                                </b-card-text>
                            </b-card>
                        </div>
                        <template v-for="config in configModel">
                            <faq-item>
                                <template slot="title"> {{ config.text }} </template>
                                    <template slot="body">
                                        <div class="mb-2">
                                            <span> {{ config.email.text }} </span>
                                            <b-form-checkbox
                                                v-model="config.email.value"
                                                class="float-right"
                                                size="lg"
                                                name="check-button"
                                                switch>
                                            </b-form-checkbox>
                                        </div>
                                        <div class="mb-2">
                                            <span> {{ config.website.text }} </span>
                                            <b-form-checkbox
                                                v-model="config.website.value"
                                                class="float-right"
                                                size="lg"
                                                name="check-button"
                                                switch>
                                            </b-form-checkbox>
                                        </div>
                                </template>
                            </faq-item>
                        </template>
                        <div>
                            <b-card>
                                <button
                                    class="btn btn-primary float-left"
                                    @click="saveConfig"
                                >
                                    {{ $t('save') }}
                                </button>
                                <button
                                    class="btn btn-primary float-left ml-2"
                                    @click="closeModal"
                                >
                                    {{ $t('cancel') }}
                                </button>
                            </b-card>
                        </div>
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
            userNotificationsConfig: {},
            email: false,
            website: false,
            configModel: {},
            options: [
                {text: '', value: 'email'}, // set translation tag
                {text: '', value: 'website'}, // set translation tag
            ],
        };
    },
    created() {
        this.fetchUserNotificationsConfig();
    },
    methods: {
        saveConfig: function() {
            let data = this.configModel;
            this.$axios.retry.post(this.$routing.generate('update_notifications_config'), data)
                .then((res) => {
                    console.log(res);
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading User Notifications channels', err);
                });
        },
        fetchUserNotificationsConfig: function() {
            this.$axios.retry.get(this.$routing.generate('user_notifications_config'))
                .then((res) => {
                    this.userNotificationsConfig = res.data;
                    this.configModel = res.data;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading User Notifications channels', err);
                });
        },
        closeModal: function() {
            this.configModel = this.userNotificationsConfig;
            this.$emit('close');
        },
    },
};
</script>
