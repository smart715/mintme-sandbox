<template>
    <div>
        <template v-if="existed">
            <div class="text-center">
                <div class="text-left">
                    <div class="text-left d-inline-block ml-api">
                        {{ $t('api_keys.public_key') }}<br />
                            <span class="text-danger word-break">{{ keys.publicKey }}</span>
                            <copy-link
                                id="pub-copy-btn"
                                class="code-copy c-pointer ml-2"
                                :content-to-copy="keys.publicKey"
                            >
                                <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                            </copy-link><br />
                            {{ $t('api_keys.private_key') }}<br />
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
                                    <span class="text-white-50">{{ $t('api_clients.hidden') }}</span>
                                </template>
                            </div>
                    </div>
                </div>
               <span v-show="keys.plainPrivateKey" class="small">
                 {{ $t('api_keys.private_key_note') }}
               </span>
            </div>
            <p>{{ $t('api_keys.invalidate.label') }}</p>
            <button
                class="btn btn-primary c-pointer"
                @click="toggleInvalidateModal(true)"
            >
                {{ $t('api_keys.invalidate') }}
            </button>
        </template>
        <template v-else>
            <p>{{ $t('api_keys.generate.label') }}</p>
            <button
                class="btn btn-primary c-pointer"
                @click="generate"
            >
                {{ $t('api_keys.generate.submit') }}
            </button>
        </template>
        <confirm-modal :visible="invalidateModal" @confirm="invalidate" @close="toggleInvalidateModal(false)">
            <p class="text-white modal-title text-break pt-2">
              <span v-html="this.$t('api_keys.invalidate.note')"></span>
            </p>
        </confirm-modal>
    </div>
</template>

<script>
import {HTTP_ACCESS_DENIED} from '../utils/constants';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import ConfirmModal from './modal/ConfirmModal';
import CopyLink from './CopyLink';
import {NotificationMixin} from '../mixins';

library.add(faCopy);

export default {
    name: 'ApiKeys',
    mixins: [NotificationMixin],
    components: {
        ConfirmModal,
        CopyLink,
        FontAwesomeIcon,
    },
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
                    if (HTTP_ACCESS_DENIED === err.response.status) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }
                    this.$logger.error('Can not generate API Keys', err);
                });
        },
        invalidate: function() {
            return this.$axios.single.delete(this.$routing.generate('delete_keys'))
                .then((res) => {
                    this.keys = {};
                    this.toggleInvalidateModal(false);
                })
                .catch((err) => {
                    if (HTTP_ACCESS_DENIED === err.response.status) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }
                    this.$logger.error('Can not invalidate API Keys', err);
                });
        },
        toggleInvalidateModal: function(on) {
            this.invalidateModal = on;
        },
    },
};
</script>
