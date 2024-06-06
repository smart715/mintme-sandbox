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
                                <template slot="title">
                                    {{ config.text }}
                                    <guide v-if="tooltipNewInvestor(config)" class="tooltip-center">
                                        <template slot="header">
                                            {{ $t('userNotification.tooltip.new_investor') }}
                                        </template>
                                    </guide>
                                </template>
                                <template slot="body">
                                    <div class="mb-2 mt-1 d-flex justify-content-between">
                                        <div>
                                            <span> {{ config.channels.email.text }} </span>
                                        </div>
                                        <b-form-checkbox
                                            v-if="!savingCheckboxConfig.email"
                                            v-model="config.channels.email.value"
                                            class="float-right checkbox-dashed-onfocus no-top"
                                            size="lg"
                                            name="check-button"
                                            switch
                                            tabindex="0"
                                            :disabled="saving"
                                            @change="changeCheckboxConfig('email')"
                                        />
                                        <div v-else class="pr-4">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="sr-only">{{ $t('loading') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span> {{ config.channels.website.text }} </span>
                                        <b-form-checkbox
                                            v-if="!savingCheckboxConfig.website"
                                            v-model="config.channels.website.value"
                                            class="float-right checkbox-dashed-onfocus no-top"
                                            size="lg"
                                            name="check-button"
                                            switch
                                            :disabled="saving"
                                            @change="changeCheckboxConfig('website')"
                                        />
                                        <div v-else class="pr-4">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="sr-only">{{ $t('loading') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="isNewPostSettings(config)" class="mb-2 plain-text-content">
                                        <a class="link" @click="openAdvancedModal">
                                            {{ $t('userNotification.config.settings.advanced') }}
                                        </a>
                                    </div>
                                </template>
                            </faq-item>
                        </div>
                    </template>
                    <div v-if="loading" class="text-center pb-4">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="sr-only">{{ $t('loading') }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </modal>
        <notifications-management-advanced-modal
            v-if="notificationAdvancedModalVisible"
            :notification-advanced-modal-visible="notificationAdvancedModalVisible"
            :config-prop="userConfigModel"
            :saving-config-data="saving"
            @close="notificationAdvancedModalVisible = false"
            @save-config="saveConfig"
        />
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import Modal from './Modal';
import NotificationsManagementAdvancedModal from './NotificationsManagementAdvancedModal';
import FaqItem from '../FaqItem';
import {NotificationMixin} from '../../mixins/';
import {BCard, BCardText, BFormCheckbox} from 'bootstrap-vue';
import {HTTP_ACCESS_DENIED} from '../../utils/constants';
import Guide from '../Guide';
import TruncateFilterMixin from '../../mixins/filters/truncate';

library.add(faCircleNotch);

export default {
    name: 'NotificationsManagementModal',
    components: {
        Guide,
        BCard,
        BCardText,
        BFormCheckbox,
        Modal,
        FaqItem,
        NotificationsManagementAdvancedModal,
    },
    mixins: [
        NotificationMixin,
        TruncateFilterMixin,
    ],
    props: {
        notificationConfigModalVisibleProp: Boolean,
        notificationConfigModalVisible: Boolean,
        noClose: Boolean,
    },
    data() {
        return {
            loading: false,
            saving: false,
            userConfig: {},
            userConfigModel: {},
            savingCheckboxConfig: {
                email: false,
                website: false,
            },
            notificationAdvancedModalVisible: this.notificationConfigModalVisibleProp,
        };
    },
    computed: {
        userConfigModelFiltered: function() {
            return Object.values(this.userConfigModel)
                .filter((config) => config.show);
        },
    },
    mounted() {
        this.fetchUserNotificationsConfig(true);
    },
    methods: {
        changeCheckboxConfig: async function(type) {
            this.savingCheckboxConfig[type] = true;

            try {
                await this.saveConfig();
            } finally {
                this.savingCheckboxConfig[type] = false;
            }
        },
        tooltipNewInvestor: function(config) {
            return this.userConfigModel.new_investor.text === config.text;
        },
        fetchUserNotificationsConfig: function(loading = false) {
            this.loading = loading;
            this.$axios.retry.get(this.$routing.generate('user_notifications_config'))
                .then((res) => {
                    this.userConfig = res.data;
                    this.userConfigModel = JSON.parse(JSON.stringify(this.userConfig));
                })
                .catch((err) => {
                    this.$logger.error('Error loading User Notifications config', err);
                })
                .then(() => this.loading = false);
        },
        saveConfig: async function(config) {
            this.saving = true;
            const data = config?.advancedConfig ?? this.userConfigModel;

            try {
                await this.$axios.retry.post(this.$routing.generate('update_notifications_config'), data);

                this.fetchUserNotificationsConfig();
                this.notifySuccess(this.$t('userNotification.config.updated'));
            } catch (err) {
                if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                    this.notifyError(err.response.data.message);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                }
                this.$logger.error('Error updating User Notifications config', err);
            } finally {
                this.saving = false;
            }
        },
        isNewPostSettings: function(config) {
            return this.userConfigModel.new_post.text === config.text;
        },
        openAdvancedModal: function() {
            this.notificationAdvancedModalVisible = true;
        },
        closeModal: function() {
            this.notificationAdvancedModalVisible = false;
            this.$emit('close');
            this.userConfigModel = JSON.parse(JSON.stringify(this.userConfig));
        },
    },
};
</script>
