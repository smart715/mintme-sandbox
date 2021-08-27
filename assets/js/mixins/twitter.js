import {openPopup} from '../utils';
import {mapGetters, mapMutations} from 'vuex';

export default {
    computed: {
        ...mapGetters('user', ['getIsSignedInWithTwitter']),
        isSignedInWithTwitter: {
            get() {
                return this.getIsSignedInWithTwitter;
            },
            set(val) {
                this.setIsSignedInWithTwitter(val);
            },
        },
    },
    methods: {
        ...mapMutations('user', ['setIsSignedInWithTwitter']),
        signInWithTwitter() {
            return new Promise((resolve, reject) => {
                this.$axios.single.post(this.$routing.generate('twitter_request_token'))
                    .then((res) => openPopup(res.data.url))
                    .then(this.checkSignedInWithTwitter)
                    .then(() => {
                        if (this.isSignedInWithTwitter) {
                            resolve();
                        } else {
                            reject(new Error(this.$t('twitter.must_sign_in')));
                        }
                    })
                    .catch((err) => reject(new Error(err.response.data.message)));
            });
        },
        checkSignedInWithTwitter() {
            return this.$axios.single.get(this.$routing.generate('check_twitter'))
                .then((res) => this.isSignedInWithTwitter = res.data.isSignedInWithTwitter);
        },
    },
};
