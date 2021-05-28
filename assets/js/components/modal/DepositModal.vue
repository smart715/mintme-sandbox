<template>
    <modal
        id="modal"
        :visible="visible"
        :no-close="noClose"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <h3 class="modal-title overflow-wrap-break-word">{{ $t('deposit_modal.title') }} ({{ currency|rebranding }})</h3>
                <div class="col-12 pt-2">
                    <code class="wallet-code text-blue" id="walletaddress">
                        <span>
                            {{ address }}
                        </span>
                        <copy-link :content-to-copy="address" class="c-pointer">
                            <font-awesome-icon :icon="['far', 'copy']">
                            </font-awesome-icon>
                        </copy-link>
                    </code>
                    <div class="clearfix"></div>
                    <b-row>
                        <b-col>
                            <p class="text-center mt-2 overflow-wrap-break-word word-break-all">{{ description|rebranding }}</p>
                        </b-col>
                    </b-row>
                    <b-row>
                        <b-col v-if="min" cols="auto" class="text-left overflow-wrap-break-word word-break-all">
                            {{ $t('deposit_modal.min_value') }} {{ min }} {{ currency|rebranding }}
                        </b-col>
                        <b-col v-if="fee" class="text-right overflow-wrap-break-word word-break-all">{{ $t('deposit_modal.fee') }} {{ fee }} {{ feeCurrency|rebranding }}</b-col>
                    </b-row>
                </div>
                <div class="pt-2 text-center">
                    <button
                        class="btn btn-primary"
                        @click="onSuccess"
                    >
                        {{ $t('deposit_modal.ok') }}
                    </button>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {BRow, BCol} from 'bootstrap-vue';
import Modal from './Modal.vue';
import CopyLink from '../CopyLink';
import {RebrandingFilterMixin} from '../../mixins';
import {webSymbol} from '../../utils/constants';

library.add(faCopy);

export default {
    name: 'DepositModal',
    mixins: [RebrandingFilterMixin],
    components: {
        BRow,
        BCol,
        CopyLink,
        Modal,
        FontAwesomeIcon,
    },
    props: {
        visible: Boolean,
        address: String,
        description: String,
        currency: String,
        isToken: Boolean,
        min: String,
        fee: String,
        noClose: Boolean,
    },
    computed: {
      feeCurrency: function() {
          return this.isToken ? webSymbol : this.currency;
      },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
        onSuccess: function() {
            this.closeModal();
            this.$emit('success');
        },
    },
};
</script>

