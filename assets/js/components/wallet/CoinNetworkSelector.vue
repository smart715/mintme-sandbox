<template>
    <div>
        <m-dropdown
            :label="coinNetworkSelectorLabel"
            type="primary"
            class="mb-2"
        >
            <template v-slot:button-content>
                <div v-if="selectedNetwork" class="d-flex align-items-center flex-fill">
                    <coin-avatar
                        :symbol="selectedNetwork.symbol"
                        :is-crypto="true"
                        class="mb-1 mr-1"
                    />
                    <span class="text-truncate">
                        {{ selectedNetwork.networkName }}
                    </span>
                </div>
                <div
                    v-if="!selectedNetwork"
                    class="d-flex align-items-center flex-fill"
                >
                    {{ $t('withdraw_modal.coin_network_selector.text') }}
                </div>
            </template>
            <template v-slot:errors>
                <div v-if="!isBlockchainAvailable">{{ $t('blockchain_unavailable', translationsContext) }}</div>
            </template>
            <m-dropdown-item
                v-for="network in networks"
                :key="network.symbol"
                :value="network"
                :class="{'active': selectedNetwork === network}"
                @click="handleSelect(network)"
            >
                <div class="row pl-2">
                    <coin-avatar
                        :symbol="network.symbol"
                        :is-crypto="true"
                        class="col-1 d-flex justify-content-center align-items-center"
                    />
                    <span class="col ml-n3">
                        {{ network.networkName }}
                    </span>
                </div>
            </m-dropdown-item>
        </m-dropdown>
    </div>
</template>

<script>
import MDropdownItem from '../UI/DropdownItem.vue';
import MDropdown from '../UI/Dropdown.vue';
import CoinAvatar from '../CoinAvatar.vue';

export default {
    name: 'CoinNetworkSelector',
    components: {CoinAvatar, MDropdown, MDropdownItem},
    props: {
        value: {
            type: Object,
            default: null,
        },
        networks: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            selectedNetwork: this.value,
        };
    },
    computed: {
        isNetworkSelected() {
            return !!this.selectedNetwork;
        },
        coinNetworkSelectorLabel() {
            return this.isNetworkSelected ? this.$t('withdraw_modal.coin_network_selector.text') : '';
        },
        isBlockchainAvailable() {
            return this.isNetworkSelected
                ? this.selectedNetwork?.networkInfo?.blockchainAvailable
                : true;
        },
        translationsContext() {
            return {
                blockchainName: this.selectedNetwork?.networkName,
            };
        },
    },
    methods: {
        handleSelect(value) {
            this.selectedNetwork = value;

            this.$emit('input', value);
            this.$emit('select', value);
        },
    },
    watch: {
        value() {
            this.selectedNetwork = this.value;
        },
    },
};
</script>
