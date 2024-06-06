<template>
    <div>
        <m-dropdown
            v-if="mainDeployInfo"
            :label="$t('page.pair.contract_addresses')"
            type="primary"
            hideAssistive
        >
            <template v-slot:button-content>
                <div class="d-flex align-items-center flex-fill mr-3">
                    <div class="icon contract-avatar d-flex justify-content-center mr-1">
                        <img
                            class="w-100"
                            :src="mainDeployInfo.icon"
                            :alt="mainDeployInfo.rebrandedSymbol"
                        />
                    </div>
                    <span class="text-truncate text-white">
                        {{ mainDeployInfo.previewAddress }}
                    </span>
                </div>
            </template>
            <m-dropdown-item
                :value="mainDeployInfo.symbol"
                class="truncate-address d-flex flex-nowrap c-pointer"
            >
                <copy-link class="c-pointer" :content-to-copy="mainDeployInfo.address">
                    <div class="row pl-2">
                        <div class="icon contract-avatar d-flex justify-content-center mr-1">
                            <img
                                class="w-100"
                                :src="mainDeployInfo.icon"
                                :alt="mainDeployInfo.rebrandedSymbol"
                            />
                        </div>
                        <span class="col ml-n3">
                            {{ mainDeployInfo.previewAddress }}
                        </span>
                        <div class="token-address-buttons">
                            <font-awesome-icon :icon="['far', 'copy']" class="icon-default"/>
                        </div>
                    </div>
                </copy-link>
            </m-dropdown-item>
            <m-dropdown-item
                v-for="item in contractAddresses"
                :key="item.symbol"
                :value="item.symbol"
                class="truncate-address d-flex flex-nowrap c-pointer"
            >
                <copy-link
                    class="c-pointer"
                    :content-to-copy="item.address"
                >
                    <div class="row pl-2">
                        <div class="icon contract-avatar d-flex justify-content-center mr-1">
                            <img
                                class="w-100"
                                :src="item.icon"
                                :alt="item.rebrandedSymbol"
                            />
                        </div>
                        <span class="col ml-n3">
                            {{ item.previewAddress }}
                        </span>
                        <div class="token-address-buttons">
                            <font-awesome-icon :icon="['far', 'copy']" class="icon-default"/>
                        </div>
                    </div>
                </copy-link>
            </m-dropdown-item>
        </m-dropdown>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import CopyLink from '../CopyLink';
import {BnbToBscFilterMixin, RebrandingFilterMixin} from '../../mixins';
import {mapGetters} from 'vuex';
import {WEB} from '../../utils/constants';
import {getCoinAvatarAssetName} from '../../utils';
import {MDropdown, MDropdownItem} from '../UI';

library.add(faCopy);

export default {
    name: 'TokenContractAddresses',
    mixins: [
        RebrandingFilterMixin,
        BnbToBscFilterMixin,
    ],
    components: {
        FontAwesomeIcon,
        CopyLink,
        MDropdown,
        MDropdownItem,
    },
    computed: {
        ...mapGetters('tokenInfo', [
            'getDeploys',
            'getMainDeploy',
            'getDeploymentStatus',
        ]),
        contractAddresses: function() {
            return this.getDeploys.reduce((item, deploy) => {
                if (!deploy.pending) {
                    item[crypto.symbol] = this.getCryptoInfo(deploy);
                }

                return item;
            }, {});
        },
        mainDeployInfo: function() {
            return this.getMainDeploy.pending
                ? null
                : this.getCryptoInfo(this.getMainDeploy);
        },
    },
    methods: {
        getPreviewAddress: function(address) {
            const size = 6;
            const start = address.slice(0, size);
            const end = address.slice(-size);

            return start + '...' + end;
        },
        getCryptoInfo: function(deploy) {
            const crypto = deploy.crypto;

            return {
                icon: require(`../../../img/${getCoinAvatarAssetName(crypto.symbol) || WEB.icon}`),
                symbol: crypto.symbol,
                rebrandedSymbol: this.rebrandingFunc(this.bnbToBscFunc(crypto.symbol)),
                address: deploy.address,
                previewAddress: this.getPreviewAddress(deploy.address),
            };
        },
    },
};
</script>
