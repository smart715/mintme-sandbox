<template>
    <div class="col-12 pt-2 pl-0 d-flex align-items-center">
        <label class="text-left pb-0">
            {{ $t('wallet.select_network.label') }}
        </label>
        <div class="text-left">
            <span
                v-for="network in networks"
                :key="network.networkInfo.symbol"
                v-b-tooltip="tooltipConfig(network)"
            >
                <button
                    class="btn btn-network btn-primary px-2 pb-1 ml-3"
                    :class="{'selected': value === network}"
                    :disabled="!isNetworkAvailable(network)"
                    @click="onNetworkChoose(network)"
                >
                    <coin-avatar
                        :symbol="network.networkInfo.symbol"
                        :is-crypto="true"
                        class="d-inline avatar avatar__coin"
                    />
                    {{ getBlockchainShortName(network.networkInfo.symbol) }}
                </button>
            </span>
            <span v-if="isLoading" class="ml-2">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </span>
        </div>
    </div>
</template>

<script>
import {VBTooltip} from 'bootstrap-vue';
import {BlockchainShortNameMixin} from '../../mixins/';
import CoinAvatar from '../CoinAvatar';

export default {
    name: 'NetworkSelector',
    mixins: [
        BlockchainShortNameMixin,
    ],
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        value: {
            type: Object,
            default: null,
        },
        networks: {
            type: Array,
            default: () => [],
        },
        isOwner: Boolean,
        tokenName: {
            type: String,
            default: null,
        },
        isLoading: Boolean,
    },
    components: {
        CoinAvatar,
    },
    methods: {
        tooltipConfig(network) {
            if (this.isLoading) {
                return;
            }

            const tooltip = {
                title: null,
                boundary: 'window',
                customClass: 'tooltip-custom',
                variant: 'light',
                html: true,
            };

            if (!this.networks.includes(network) && this.isOwner) {
                tooltip.title = this.$t('withdraw_modal.disabled_network', {
                    tab: `<a class="highlight" href="${this.$routing.generate('token_settings', {
                        tokenName: this.tokenName,
                        tab: 'advanced',
                    })}" > ${this.$t('token_edit_modal.connect')} </a>`,
                });

                return tooltip;
            }
        },
        isNetworkAvailable: function(network) {
            return !this.isLoading && this.networks.includes(network);
        },
        onNetworkChoose: function(network) {
            this.$emit('input', network);
        },
    },
};
</script>
