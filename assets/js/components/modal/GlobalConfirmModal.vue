<template>
    <confirm-modal
        :visible="showLeaveSiteModal"
        :show-image="false"
        :submitting="false"
        @confirm="leaveSite"
        @cancel="showLeaveSiteModal = false"
        @close="showLeaveSiteModal = false"
    >
        <slot>
            <div
                class="w-100 text-white modal-title pt-2 pb-4"
            >
                <div>
                    {{ $t('global_confirm_modal.external_url_warning_1') }}
                    <span
                        v-if="isLongUrl"
                        v-b-tooltip="externalUrl"
                        class="text-primary-darker"
                    >
                        {{ externalUrlTruncated }}
                    </span>
                    <span
                        v-else
                        class="text-primary-darker"
                    >
                        {{ externalUrl }}
                    </span>
                    {{ $t('global_confirm_modal.external_url_warning_2') }}
                </div>
                <div class="pt-3">
                    {{ $t('global_confirm_modal.external_url_confirm') }}
                </div>
            </div>
        </slot>
        <template v-slot:confirm>
            {{ $t('confirm_modal.continue') }}
        </template>
    </confirm-modal>
</template>

<script>
import ConfirmModal from './ConfirmModal';
import TruncateFilterMixin from '../../mixins/filters/truncate';
import {VBTooltip} from 'bootstrap-vue';
import {EXTERNAL_URL_TRUNCATE_LENGTH, mintmeUrlHost} from '../../utils/constants';

const ANCHOR_CHILDS = [
    'img',
    'path',
    'svg',
];

export default {
    name: 'GlobalConfirmModal',
    mixins: [
        TruncateFilterMixin,
    ],
    components: {
        ConfirmModal,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        knownHosts: Array,
    },
    data() {
        return {
            externalUrl: '',
            showLeaveSiteModal: false,
        };
    },
    mounted() {
        document.addEventListener('click', this.checkExternalLink);
    },
    computed: {
        isLongUrl() {
            return this.externalUrl.length > EXTERNAL_URL_TRUNCATE_LENGTH;
        },
        externalUrlTruncated() {
            return this.truncateFunc(this.externalUrl, EXTERNAL_URL_TRUNCATE_LENGTH);
        },
    },
    methods: {
        leaveSite() {
            window.open(this.externalUrl, '_blank');
            this.showLeaveSiteModal = false;
        },
        checkExternalLink(event) {
            let target = event.target;

            if (ANCHOR_CHILDS.includes(target.tagName.toLowerCase())) {
                target = target.closest('a');
            }

            if (target && 'A' === target.tagName && target.href) {
                const url = new URL(target.href);
                const currentHost = window.location.host.replace(/^www\./, '');
                const hostFromUrl = url.host.replace(/^www\./, '');

                if (hostFromUrl !== currentHost
                    && hostFromUrl !== mintmeUrlHost
                    && !this.knownHosts.includes(hostFromUrl)
                ) {
                    this.showLeaveSiteModal = true;
                    this.externalUrl = target.href;
                    event.preventDefault();
                }
            }
        },
    },
};
</script>
