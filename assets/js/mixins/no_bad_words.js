import {HTTP_OK} from '../utils/constants';
import Censor from '../utils/profanity/censor';
import CensorConfig from '../utils/profanity/censorConfig';

const day = 1000 * 60 * 60 * 24;
export default {
    data() {
        return {
            isProcessingConfig: false,
            isProcessingConfigError: false,
        };
    },
    methods: {
        async noBadWordsValidator(mainField, badWordMessageField) {
            if (
                '' === this[mainField] || null === this[mainField] ||
                this.isProcessingConfig || this.isProcessingConfigError
            ) {
                return true;
            }

            if ((this._isNotCached() || this._isConfigCacheExpired())) {
                try {
                    await this._fetchAndCacheCensorConfig();
                } catch (error) {
                    this.isProcessingConfigError = true;
                    this.$logger.error('error', 'Failed to fetch censor config', error);
                    return true;
                }
            }
            const config = this._parseConfigFromCache();
            const censor = new Censor(config);

            const result = censor.isClean(this[mainField]);
            this[badWordMessageField] = result.isClean ? '' : this.$t('bad_word.found', {firstBadWord: result.badWord});

            return result.isClean;
        },
        async _fetchAndCacheCensorConfig() {
            this.isProcessingConfig = true;
            const response = await this._fetchCensorConfig();

            if (HTTP_OK !== response.status) {
                throw new Error('Failed to fetch censor config', response);
            }

            localStorage.setItem('censorConfig', JSON.stringify(response.data));
            localStorage.setItem('censorConfigTTL', JSON.stringify(Date.now() + day));
            this.isProcessingConfig = false;
        },
        async _fetchCensorConfig() {
            return this.$axios.single.get(this.$routing.generate('get_censor_config'));
        },
        _isNotCached() {
            return !localStorage.getItem('censorConfig');
        },
        _isConfigCacheExpired: function() {
            return JSON.parse(localStorage.getItem('censorConfigTTL')) < Date.now();
        },

        _parseConfigFromCache() {
            return new CensorConfig(JSON.parse(localStorage.getItem('censorConfig')));
        },
    },
};
