<template>
    <div class="d-flex flex-wrap">
        <div
            v-for="item in contractAddresses"
            :key="item.symbol"
            class="truncate-address d-flex flex-nowrap mt-2 mr-3"
        >
            <div class="icon contract-avatar d-flex justify-content-center mr-1">
                <img
                    class="w-100"
                    :src="item.icon"
                    :alt="item.rebrandedSymbol"
                />
            </div>
            <span>
                {{ item.previewAddress }}
            </span>
            <div class="token-address-buttons">
                <copy-link
                    class="c-pointer"
                    :content-to-copy="item.address"
                >
                    <font-awesome-icon :icon="['far', 'copy']" class="icon-default"/>
                </copy-link>
            </div>
        </div>
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
    },
    computed: {
        ...mapGetters('tokenInfo', [
            'getDeploys',
            'getDeploymentStatus',
        ]),
        contractAddresses: function() {
            return this.getDeploys.reduce((item, deploy) => {
                if (!deploy.pending) {
                    const crypto = deploy.crypto;
                    item[crypto.symbol] = {
                        icon: require(`../../../img/${getCoinAvatarAssetName(crypto.symbol) || WEB.icon}`),
                        symbol: crypto.symbol,
                        rebrandedSymbol: this.rebrandingFunc(this.bnbToBscFunc(crypto.symbol)),
                        address: deploy.address,
                        previewAddress: this.getPreviewAddress(deploy.address),
                    };
                }

                return item;
            }, {});
        },
    },
    methods: {
        getPreviewAddress: function(address) {
            const size = 7 - this.getDeploys?.length;
            const start = address.slice(0, size);
            const end = address.slice(-size);

            return start + '...' + end;
        },
    },
};
</script>
