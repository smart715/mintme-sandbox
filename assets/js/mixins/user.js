import TruncateFilterMixin from './filters/truncate';
/**
 *  Normalizes profile to user
 *  @param {object} user
 *  @return {(object|null)}
 */
function normalizeUser(user) {
    if (!user) {
        return null;
    }

    return user.profile ? user : {profile: user};
};

export default {
    mixins: [
        TruncateFilterMixin,
    ],
    methods: {
        getNickname(_user, truncate = null) {
            const user = normalizeUser(_user);

            if (user) {
                if (user.profile.deleted) {
                    return this.$t('user.deleted');
                }

                return truncate
                    ? this.truncateFunc(user.profile.nickname, truncate)
                    : user.profile.nickname;
            }

            return null;
        },
        getProfileUrl(_user) {
            const user = normalizeUser(_user);

            if (user && !user.profile.deleted) {
                return this.$routing.generate('profile-view', {nickname: user.profile.nickname});
            }

            return null;
        },
    },
};
