<template>
    <div>
        <modal
            :visible="notificationConfigModalVisible"
            :noClose="noClose"
            :without-padding="true"
            @close="closeModal"
        >
            <template slot="header"> {{ $t('userNotification.config.settings') }} </template>
            <template slot="body">
                <div class="notification-config p-0">
                    <div>
                        <b-card>
                            <b-card-text>
                                {{ $t('userNotification.config.receive_not_about') }}
                            </b-card-text>
                        </b-card>
                    </div>
                    <template v-if="!loading">
                        <div
                            v-for="config in userConfigModelFiltered"
                            :key="config.text"
                            class="row faq-block light-border no-decoration mx-0">
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
                                                switch
                                            >
                                            </b-form-checkbox>
                                        </div>
                                        <div class="mb-2">
                                            <span> {{ config.channels.website.text }} </span>
                                            <b-form-checkbox
                                                v-model="config.channels.website.value"
                                                class="float-right"
                                                size="lg"
                                                name="check-button"
                                                switch
                                            >
                                            </b-form-checkbox>
                                        </div>
                                </template>
                            </faq-item>
                        </div>
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
                            <div class="mt-1">
                                <b-link class="ml-3" @click="$emit('close')" >{{ $t('cancel') }}</b-link>
                            </div>
                        </b-card>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Modal from './Modal';
import FaqItem from '../FaqItem';
import {NotificationMixin, LoggerMixin} from '../../mixins/';
import {BCard, BCardText, BLink, BFormCheckbox} from 'bootstrap-vue';

library.add(faCircleNotch);

export default {
    name: 'NotificationManagementModal',
    components: {
        BCard,
        BCardText,
        BLink,
        BFormCheckbox,
        Modal,
        FaqItem,
        FontAwesomeIcon,
    },
    mixins: [
        NotificationMixin,
        LoggerMixin,
    ],
    props: {
        notificationConfigModalVisible: Boolean,
        noClose: Boolean,
    },
    data() {
        return {
            loading: false,
            saving: false,
            userConfig: {},
            userConfigModel: {},
        };
    },
    computed: {
        userConfigModelFiltered: function() {
            return Object.values(this.userConfigModel)
                .filter((config) => config.show);
        },
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
                    this.userConfigModel = JSON.parse(JSON.stringify(this.userConfig));
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading User Notifications config', err);
                })
                .then(() => this.loading = false);
        },
        saveConfig: function() {
            this.saving = true;
            let data = this.userConfigModel;
            this.$axios.retry.post(this.$routing.generate('update_notifications_config'), data)
                .then(() => {
                    this.fetchUserNotificationsConfig();
                    this.notifySuccess(this.$t('userNotification.config.updated'));
                    this.$emit('close');
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error updating User Notifications config', err);
                    this.notifyError(this.$t('toasted.error.try_later'));
                })
                .then(() => this.saving = false);
        },
        closeModal: function() {
            this.$emit('close');
            this.userConfigModel = JSON.parse(JSON.stringify(this.userConfig));
        },
    },
};
</script>
