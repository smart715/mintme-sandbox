<template>
    <div>
        <template v-if="existed">
            <div class="text-center">
                <div class="text-left">
                    <div class="text-left d-inline-block ml-api">
                        Your public key:<br />
                            <span class="text-danger word-break">{{ keys.publicKey }}</span>
                            <copy-link class="code-copy c-pointer ml-2" id="pub-copy-btn" :content-to-copy="keys.publicKey">
                                <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                            </copy-link><br />
                        Your private key:<br />
                            <div v-if="keys.plainPrivateKey">
                                <template>
                                    <span class="text-danger word-break">{{ keys.plainPrivateKey }}</span>
                                    <copy-link
                                            class="code-copy c-pointer ml-2"
                                            id="private-copy-btn"
                                            :content-to-copy="keys.plainPrivateKey">
                                        <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                                    </copy-link>
                                </template>
                            </div>
                            <div v-else>
                                <template>
                                    <span class="text-white-50">** hidden **</span>
                                </template>
                            </div>
                    </div>
                </div>
               <span v-show="keys.plainPrivateKey" class="small">
                    (Copy this key, you will not able to see it again after reload)
                </span>
            </div>
            <p>Invalidate your API keys:</p>
            <button
                class="btn btn-primary c-pointer"
                @click="toggleInvalidateModal(true)"
            >
                Invalidate
            </button>
        </template>
        <template v-else>
            <p>Generate your API keys:</p>
            <button
                class="btn btn-primary c-pointer"
                @click="generate"
            >
                Generate
            </button>
        </template>
        <confirm-modal :visible="invalidateModal" @confirm="invalidate" @close="toggleInvalidateModal(false)">
            <p class="text-white modal-title pt-2">
                Are you sure you want to invalidate your API keys.
                Currently running applications will not work. Continue?
            </p>
        </confirm-modal>
    </div>
</template>

<script>
    import ConfirmModal from './modal/ConfirmModal';
    import CopyLink from './CopyLink';
    import {LoggerMixin, NotificationMixin} from '../mixins';

    export default {
        name: 'ApiKeys',
        mixins: [NotificationMixin, LoggerMixin],
        components: {ConfirmModal, CopyLink},
        props: {
            apiKeys: {type: [Object, Array], required: true},
        },
        data: function() {
            return {
                keys: this.apiKeys,
                invalidateModal: false,
            };
        },
        computed: {
            existed: function() {
                return this.keys.hasOwnProperty('publicKey');
            },
        },
        methods: {
            generate: function() {
                return this.$axios.single.post(this.$routing.generate('post_keys'))
                    .then((res) => this.keys = res.data)
                    .catch((err) => {
                        this.notifyError('Something went wrong. Try to reload the page.');
                        this.sendLogs('error', 'Can not generate API Keys', err);
                    });
            },
            invalidate: function() {
                return this.$axios.single.delete(this.$routing.generate('delete_keys'))
                    .then((res) => {
                        this.keys = {};
                        this.toggleInvalidateModal(false);
                    })
                    .catch((err) => {
                        this.notifyError('Something went wrong. Try to reload the page.');
                        this.sendLogs('error', 'Can not invalidate API Keys', err);
                    });
            },
            toggleInvalidateModal: function(on) {
                this.invalidateModal = on;
            },
        },
    };
</script>
