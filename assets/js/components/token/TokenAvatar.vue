<template>
    <div
        class="token-avatar"
        :class="{'token-avatar--default': isDefaultImage}"
    >
        <avatar
            type="token"
            v-b-tooltip.hover="getTooltipTitle"
            :image="image"
            :token="tokenName"
            :editable="isOwner"
        />
        <div v-if="profileImageUrl" class="avatar user-avatar">
            <a :href="getProfileUrl">
                <img
                    :src="profileImageUrl"
                    class="rounded-circle"
                    :class="{'user-avatar-border' : !isDefaultProfileImage}"
                    :title="profileNickname"
                    v-b-tooltip.hover
                />
            </a>
        </div>
    </div>
</template>

<script>
import Avatar from '../Avatar';
import {NotificationMixin} from '../../mixins';
import {VBTooltip} from 'bootstrap-vue';

export default {
    name: 'TokenAvatar',
    components: {
        Avatar,
    },
    mixins: [
        NotificationMixin,
    ],
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        isOwner: Boolean,
        image: String,
        tokenName: String,
        serviceUnavailable: Boolean,
        profileNickname: String,
        profileImageUrl: String,
    },
    computed: {
        isDefaultImage() {
            return this.image.includes('default');
        },
        getTooltipTitle() {
            return {
                title: this.isOwner
                    ? this.$t('tooltip.edit_avatar')
                    : this.$t('tooltip.token_avatar'),
                boundary: 'viewport',
            };
        },
        getProfileUrl() {
            return this.$routing.generate('profile-view', {'nickname': this.profileNickname});
        },
        isDefaultProfileImage() {
            return '/media/default_profile.png' === this.profileImageUrl;
        },
    },
    mounted() {
        if (this.serviceUnavailable) {
            this.notifyError(this.$t('toasted.error.service_unavailable'));
        }
    },
};
</script>
