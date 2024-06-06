import {openPopup} from '../utils';
import {mapGetters, mapMutations} from 'vuex';

export default {
    computed: {
        ...mapGetters('user', ['getIsAuthorizedYoutube']),
        isAuthorizedYoutube: {
            get() {
                return this.getIsAuthorizedYoutube;
            },
            set(val) {
                this.setIsAuthorizedYoutube(val);
            },
        },
    },
    methods: {
        ...mapMutations('user', ['setIsAuthorizedYoutube']),
        authorizeYoutube() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('youtube_request_token'))
                    .then((res) => openPopup(res.data.url))
                    .then(this.checkAuthorizedYoutube)
                    .then(() => {
                        if (this.isAuthorizedYoutube) {
                            resolve();
                            return;
                        }

                        reject(new Error(this.$t('youtube.must_sign_in')));
                    })
                    .catch((err) => reject(new Error(err.response.data.message)));
            });
        },
        checkAuthorizedYoutube() {
            return this.$axios.single.get(this.$routing.generate('youtube_token_expired'))
                .then((res) => this.isAuthorizedYoutube = !res.data.isExpired);
        },
    },
};
