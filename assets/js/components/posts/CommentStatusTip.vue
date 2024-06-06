<template>
    <div v-if="isLoggedIn" class="d-flex align-items-center font-size-2 text-subtitle c-pointer">
        <guide placement="top" reactive>
            <template slot="icon">
                <div class="d-flex align-items-center font-size-2 text-subtitle c-pointer">
                    <button
                        class="btn-link d-flex align-items-center"
                        :class="btnClass"
                        @click="tip"
                    >
                        <font-awesome-icon icon="coins" class="mx-2" transform="up-1.5" />
                        {{ $t('comment.status.tips.tip') }}
                    </button>
                </div>
            </template>
            <template slot="body">
                {{ tooltipContent }}
            </template>
        </guide>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCoins} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../Guide';

library.add(faCoins);

export default {
    name: 'CommentStatusTip',
    components: {
        Guide,
        FontAwesomeIcon,
    },
    props: {
        isLoggedIn: Boolean,
        userHasDeployedToken: Boolean,
        isTipped: Boolean,
    },
    data() {
        return {
            modalVisible: false,
        };
    },
    computed: {
        tooltipContent() {
            if (!this.userHasDeployedToken) {
                return this.$t('comment.status.tips.tip.tooltip.not_deployed');
            }

            if (this.isTipped) {
                return this.$t('comment.status.tips.tip.tooltip.already_tipped');
            }

            return this.$t('comment.status.tips.tip.tooltip');
        },
        btnClass() {
            return !this.userHasDeployedToken || this.isTipped ? 'btn-disabled' : '';
        },
    },
    methods: {
        tip() {
            if (!this.isLoggedIn || !this.userHasDeployedToken || this.isTipped) {
                return;
            }

            this.$emit('tip');
        },
    },
};
</script>
