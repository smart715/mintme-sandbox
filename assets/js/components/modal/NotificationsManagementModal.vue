<template>
    <div>
        <modal
            :visible="visible"
            :no-close="false"
            :without-padding="true"
            @close="$emit('close')"
        >
            <template slot="header"> Notifications Settings <!-- {{ $t(userNotification.manage) }}--> </template>
            <template slot="close"></template>
            <template slot="body">
                <div class="token-edit p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <template v-for="nType in notificationTypes">
                            <faq-item>
                                <template slot="title"> {{ nType}} </template>
                                <template slot="body">
                                    <template v-for="nChannel in notificationChannels">
                                        <div class="mb-2">
                                            <span> {{ nChannel }} </span>
                                            <b-form-checkbox
                                                v-model="selected"
                                                class="float-right"
                                                size="lg"
                                                name="check-button"
                                                switch>
                                            </b-form-checkbox>
                                        </div>
                                    </template>
                                </template>
                            </faq-item>
                        </template>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';
import FaqItem from '../FaqItem';
import {toMoney} from "../../utils";
import Decimal from "decimal.js";

export default {
    name: 'NotificationManagementModal',
    components: {
    Modal,
    FaqItem,
    },
    props: {
        visible: Boolean,
        noClose: Boolean,
        notificationTypes: Array,
        notificationChannels: Array,
        userNotificationsConfig: Array,
    },
    data() {
        return {
            email: false,
            website: false,
            selected: [], // Must be an array reference!
            options: [
                {text: '', value: 'email'}, // set translation tag
                {text: '', value: 'website'}, // set translation tag
            ],
        };
    },
    methods: {
        configList: function(userConfig) {
            return userConfig.map((config) => {
                return {
                    price: toMoney(order.price, this.market.base.subunit),
                    amount: toMoney(order.amount, this.market.quote.subunit),
                    sum: toMoney(new Decimal(order.price).mul(order.amount).toString(), this.market.base.subunit),
                    trader: order.maker.profile.nickname,
                    traderUrl: this.$routing.generate('profile-view', {nickname: order.maker.profile.nickname}),
                    side: order.side,
                    owner: order.owner,
                    orderId: order.id,
                    ownerId: order.maker.id,
                    highlightClass: '',
                    traderAvatar: order.maker.profile.image.avatar_small,
                };
            });
        },
    }
};
</script>
