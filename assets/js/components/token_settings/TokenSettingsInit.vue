<template>
    <div></div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import NotificationMixin from '../../mixins/notification';

export default {
    name: 'TokenSettingsInit',
    mixins: [
        NotificationMixin,
    ],
    props: {
        tokenName: String,
        tokenAvatar: String,
        activeTab: String,
        deploys: {
            Type: Array,
            required: true,
        },
        socialUrls: Object,
        tokenDeleteSoldLimit: Number,
        isOwner: Boolean,
        hasReleasePeriod: {
            type: Boolean,
            required: true,
        },
        isCreatedOnMintmeSite: Boolean,
    },
    data() {
        return {
            deployTimeout: null,
        };
    },
    created() {
        this.setSocialUrls(this.socialUrls);
        this.setDeploys(this.deploys);
        this.setTokenName(this.tokenName);
        this.setTokenAvatar(this.tokenAvatar);
        this.checkIfTokenExchanged();
        this.setTokenDeleteSoldLimit(this.tokenDeleteSoldLimit);
        this.setHasReleasePeriod(this.hasReleasePeriod);
        this.setIsCreatedOnMintmeSite(this.isCreatedOnMintmeSite);
    },
    computed: {
        ...mapGetters('tokenSettings', [
            'getTokenName',
        ]),
    },
    methods: {
        ...mapMutations('tokenSettings', [
            'setIsTokenExchanged',
            'setTokenName',
            'setTokenAvatar',
            'setSocialUrls',
            'setHasReleasePeriod',
            'setIsCreatedOnMintmeSite',
        ]),
        ...mapMutations('tokenInfo', [
            'setDeploys',
        ]),
        ...mapMutations('tokenStatistics', [
            'setTokenDeleteSoldLimit',
        ]),
        checkIfTokenExchanged: function() {
            this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {
                name: this.getTokenName,
            }))
                .then((res) => this.setIsTokenExchanged(res.data))
                .catch((err) => this.$logger.error('Can not fetch token data now', err));
        },
        fetchDeploys() {
            return this.$axios.retry.get(
                this.$routing.generate('token_deploys', {
                    name: this.getTokenName,
                })
            )
                .then((response) => response.data);
        },
        handleDeployEvent: function(crypto) {
            clearTimeout(this.deployTimeout);

            this.fetchDeploys()
                .then((deploys) => {
                    this.setDeploys(deploys);

                    const current = deploys.find((deploy) => crypto === deploy.crypto.symbol);

                    if (this.isOwner) {
                        if (!current && 0 === deploys.length) {
                            // main deploy failed
                            return this.notifyError(this.$t('toasted.error.can_not_be_deployed'));
                        }

                        if (!current && 1 <= deploys.length) {
                            // connection failed
                            return this.notifyError(this.$t('toasted.error.can_not_be_connected'));
                        }

                        if (current && !current.pending && 1 === deploys.length) {
                            // main deploy succesed
                            return this.$emit('show-deployed-modal');
                        }

                        if (current && !current.pending && 1 < deploys.length) {
                            // connection succesed
                            return this.notifySuccess(this.$t('token.connect.successed'));
                        }
                    }

                    if (current && current.pending) {
                        this.deployTimeout = setTimeout(
                            () => this.handleDeployEvent(crypto),
                            60000
                        );
                    }
                })
                .catch(() => {
                    clearTimeout(this.deployTimeout);
                    this.notifyError(this.$t('toasted.error.network'));
                });
        },
    },
};
</script>
