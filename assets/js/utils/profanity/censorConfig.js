/**
 * Configuration for the profanity censor.
 */
class CensorConfig {
    /**
     * @param {object} config
     */
    constructor(config) {
        this.censorChecks = config['censorChecks'] ?? [];
        this.whitelist = config['whitelist'] ?? [];
    }
}

export default CensorConfig;
