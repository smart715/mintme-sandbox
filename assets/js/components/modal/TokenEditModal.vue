<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :without-padding="true"
            @close="$emit('close')"
        >
            <template slot="header">
                <span class="modal-title py-2 pl-4 d-inline-block">{{ currentName | truncate(25) }}</span>
            </template>
            <template slot="body">
                <div class="token-edit p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <faq-item>
                            <template slot="title">
                                Change token name
                            </template>
                            <template slot="body">
                                <token-change-name
                                    :is-token-exchanged="isTokenExchanged"
                                    :current-name="currentName"
                                    :twofa="twofa"
                                    @close="$emit('close')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div
                        v-if="!preventAddressEdition"
                        class="row faq-block mx-0 border-bottom"
                    >
                        <faq-item>
                            <template slot="title">
                                Modify token withdrawal address
                            </template>
                            <template slot="body">
                                <token-withdrawal-address
                                    :is-token-exchanged="isTokenExchanged"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                    :withdrawal-address="withdrawalAddress"
                                    @close="$emit('close')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item @switch="refreshSliders">
                            <template slot="title">
                                Token release period
                            </template>
                            <template slot="body">
                                <token-release-period
                                    ref="token-release-period-component"
                                    :is-token-exchanged="isTokenExchanged"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                    @update="releasePeriodUpdated"
                                    @cancel="$emit('close')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <template slot="title">
                                Deploy token to blockchain
                            </template>
                            <template slot="body">
                                <token-deploy
                                    :has-release-period="hasReleasePeriod"
                                    :is-owner="isOwner"
                                    :name="currentName"
                                    :precision="precision"
                                    :status-prop="statusProp"
                                    :websocket-url="websocketUrl"
                                    @cancel="$emit('close')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0">
                        <faq-item>
                            <template slot="title">
                                Delete token
                            </template>
                            <template slot="body">
                                <token-delete
                                    :is-token-exchanged="isTokenExchanged"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                />
                            </template>
                        </faq-item>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import FaqItem from '../FaqItem';
import Guide from '../Guide';
import Modal from './Modal';
import TokenChangeName from '../token/TokenChangeName';
import TokenDelete from '../token/TokenDelete';
import TokenDeploy from '../token/deploy/TokenDeploy';
import TokenReleasePeriod from '../token/TokenReleasePeriod';
import TokenWithdrawalAddress from '../token/TokenWithdrawalAddress';
import TwoFactorModal from './TwoFactorModal';
import {FiltersMixin} from '../../mixins';

export default {
    name: 'TokenEditModal',
    components: {
        FaqItem,
        Guide,
        Modal,
        TokenChangeName,
        TokenDelete,
        TokenDeploy,
        TokenReleasePeriod,
        TokenWithdrawalAddress,
        TwoFactorModal,
    },
    props: {
        currentName: String,
        hasReleasePeriodProp: Boolean,
        isOwner: Boolean,
        isTokenExchanged: Boolean,
        noClose: Boolean,
        preventAddressEdition: Boolean,
        precision: Number,
        statusProp: String,
        twofa: Boolean,
        visible: Boolean,
        websocketUrl: String,
        withdrawalAddress: String,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            hasReleasePeriod: this.hasReleasePeriodProp,
        };
    },
    methods: {
        releasePeriodUpdated: function() {
            this.hasReleasePeriod = true;
        },
        refreshSliders: function() {
            this.$refs['token-release-period-component'].$refs['released-slider'].refresh();
            this.$refs['token-release-period-component'].$refs['release-period-slider'].refresh();
        },
    },
};
</script>

