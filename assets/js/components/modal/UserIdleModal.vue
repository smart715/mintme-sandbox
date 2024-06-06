<template>
    <modal
        :visible="showModal"
        :no-close="true"
    >
        <div slot="close"></div>
        <template slot="body">
            <div class="text-center">
                {{ $t('user_is_idle.modal.message') }}
            </div>
            <div class="mt-2 d-flex justify-content-around">
                <button
                    class="btn btn-primary"
                    @click="emitExtendSession()"
                >
                    {{ $t('yes') }}
                </button>
                <button
                    class="btn btn-primary"
                    @click="emitCloseSession()"
                >
                    {{ $t('no') }}
                </button>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import {USER_ACTIVITY_EVENTS, LOGOUT_FORM_ID} from '../../utils/constants';

export default {
    name: 'UserIdleModal',
    components: {
        Modal,
    },
    props: {
        timerDuration: String,
        modalDuration: String,
    },
    data() {
        return {
            lastActivity: 0,
            now: 0,
            showModal: false,
            extendedSession: false,
            loggedOut: false,
            eventName: {
                updateActivity: 'updateLastActivity',
                extendSession: 'extendSession',
                closeSession: 'closeSession',
            },
            logOutFormId: LOGOUT_FORM_ID,
        };
    },
    mounted() {
        this.setUp();
    },
    methods: {
        setUp: function() {
            this.$tabEvent.on(this.eventName.updateActivity, this.updateLastActivity);
            this.$tabEvent.on(this.eventName.extendSession, this.extendSession);
            this.$tabEvent.on(this.eventName.closeSession, this.closeSession);
            this.emitUpdateLastActivity();
            let isActive = false;

            USER_ACTIVITY_EVENTS.forEach((event) => {
                document.addEventListener(event, () => {
                    isActive = true;
                });
            });

            setInterval(() => {
                this.now = Math.round(Date.now() / 1000);
                if (isActive) {
                    isActive = false;
                    this.emitUpdateLastActivity();
                }
            }, 1000);
        },
        openModal: function() {
            this.showModal = true;
        },
        closeModal: function() {
            this.showModal = false;
        },
        userIsIdle: function() {
            if (this.showModal) {
                return;
            }

            this.openModal();
            setTimeout(() => {
                if (this.loggedOut) {
                    return;
                }

                if (this.extendedSession) {
                    this.extendedSession = false;
                } else {
                    this.sessionIsExpired();
                }
            }, Number(this.modalDuration) * 1000);
        },
        emitUpdateLastActivity: function() {
            this.$tabEvent.emit(this.eventName.updateActivity);
            this.updateLastActivity();
        },
        emitExtendSession: function() {
            this.$tabEvent.emit(this.eventName.extendSession);
            this.extendSession();
        },
        emitCloseSession: function() {
            this.$tabEvent.emit(this.eventName.closeSession);
            this.closeSession();
        },
        updateLastActivity: function() {
            this.lastActivity = Math.round(Date.now() / 1000);
        },
        extendSession: function() {
            this.extendedSession = true;
            this.closeModal();
        },
        closeSession: function() {
            this.loggedOut = true;
            this.closeModal();

            const logoutForm = new FormData(document.getElementById(this.logOutFormId));
            this.$axios.single.post(this.$routing.generate('fos_user_security_logout'), logoutForm)
                .then(() => {
                    this.setRedirectionAction();
                    document.getElementById(this.logOutFormId).submit();
                })
                .catch((err) => {
                    this.$logger.error('Cant logout user', err);
                    reject(err);
                });
        },
        addAutoLogoutMessage: function() {
            const form = document.getElementById(this.logOutFormId);
            const input = document.createElement('input');

            input.setAttribute('type', 'hidden');
            input.setAttribute('name', 'auto_log_out');
            input.setAttribute('id', 'auto-log-out');
            input.setAttribute('value', this.$t('user_is_idle.auto_logout.message'));

            form.appendChild(input);
        },
        setRedirectionAction: function() {
            const form = document.getElementById(this.logOutFormId);
            form.setAttribute('action', this.$routing.generate('auto_logout_redirection'));
        },
        sessionIsExpired: function() {
            this.loggedOut = true;
            const logoutForm = new FormData(document.getElementById(this.logOutFormId));
            this.$axios.single.post(this.$routing.generate('fos_user_security_logout'), logoutForm)
                .then(() => {
                    this.addAutoLogoutMessage();
                    this.setRedirectionAction();
                    document.getElementById(this.logOutFormId).submit();
                })
                .catch((err) => {
                    this.$logger.error('Cant logout user', err);
                    reject(err);
                });
        },
    },
    computed: {
        isIdle: function() {
            return this.now - this.lastActivity > this.timerDuration;
        },
        idleTime: function() {
            return this.now - this.lastActivity;
        },
    },
    watch: {
        isIdle: function() {
            if (this.isIdle) {
                this.userIsIdle();
            }
        },
    },
};
</script>
