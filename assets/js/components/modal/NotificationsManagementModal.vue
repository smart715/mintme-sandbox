<template>
    <div>
        <modal
            :visible="visible"
            :without-padding="true"
            @close="fetchUserNotificationsConfig"
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
                        <template v-if="!loading" v-for="config in userConfig">
                            <faq-item>
                                <template slot="title"> {{ config.text }} </template>
                                    <template slot="body">
                                        <div class="mb-2">
                                            <span> {{ config.channels.email.text }} </span>
                                            <b-form-checkbox
                                                v-model="config.channels.email.value"
                                                class="float-right"
                                                size="lg"
                                                name="check-button"
                                                switch>
                                            </b-form-checkbox>
                                        </div>
                                        <div class="mb-2">
                                            <span> {{ config.channels.website.text }} </span>
                                            <b-form-checkbox
                                                v-model="config.channels.website.value"
                                                class="float-right"
                                                size="lg"
                                                name="check-button"
                                                switch>
                                            </b-form-checkbox>
                                        </div>
                                </template>
                            </faq-item>
                        </template>
                        <div v-if="loading" class="text-center w-100">
                            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                        </div>
                        <div>
                            <b-card>
                                <button
                                    class="btn btn-primary float-left"
                                    @click="saveConfig"
                                    :disabled="saving"
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
import {NotificationMixin, LoggerMixin} from '../../mixins/';

export default {
    mixins: [NotificationMixin, LoggerMixin],
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
            email: false,
            website: false,
            options: [
                {text: '', value: 'email'}, // set translation tag
                {text: '', value: 'website'}, // set translation tag
            ],
            loading: false,
            saving: false,
            userConfig: {},
        };
    },
    mounted() {
        this.fetchUserNotificationsConfig();
    },
    methods: {
        fetchUserNotificationsConfig: function() {
            this.loading = true;
            this.$axios.retry.get(this.$routing.generate('user_notifications_config'))
                .then((res) => {
                    this.userConfig = res.data;
                    this.loading = false;
                })
                .catch((err) => {
                    this.loading = false;
                    this.sendLogs('error', 'Error loading User Notifications channels', err);
                });
        },
        saveConfig: function() {
            this.saving = true;
            let data = this.userConfig;
            this.$axios.retry.post(this.$routing.generate('update_notifications_config'), data)
                .then(() => {
                    this.saving = false;
                    this.notifySuccess('Configuration updated successfully');
                    this.$emit('close');
                })
                .catch((err) => {
                    this.saving = false;
                    this.sendLogs('error', 'Error loading User Notifications channels', err);
                    this.notifyError('Error tag');
                });
        },
        closeModal: function() {
            this.visible = false;
        },
    },
};
</script>
