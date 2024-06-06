import {mapActions, mapGetters} from 'vuex';
import {status} from '../storage/modules/websocket';

export default {
    props: {
        websocketUrl: {type: String, required: true},
        hash: {type: String},
    },
    data() {
        return {
            requestId: parseInt(Math.random().toString().replace('0.', '')),
        };
    },
    computed: {
        ...mapGetters('websocket', {
            _getClient: 'getClient',
        }),
    },
    methods: {
        ...mapActions('websocket', {
            _addMessageHandler: 'addMessageHandler',
            _addOnOpenHandler: 'addOnOpenHandler',
            _sendMessage: 'sendMessage',
            _authorizeClient: 'authorize',
            _logoutClient: 'logout',
            _loginClient: 'login',
            _initClient: 'init',
        }),
        _authCallback: function() {
            const auth = this._getClient(this.websocketUrl).auth;

            if (auth === status.FAILED) {
                this.sendMessage(JSON.stringify({
                    method: 'server.auth',
                    params: [this.hash, 'auth_api'],
                    id: this.requestId,
                }));
                this._loginClient(this.websocketUrl);
            }
        },
        authorize: function() {
            return new Promise((resolve, reject) => {
                this._initClient(this.websocketUrl);
                const auth = this._getClient(this.websocketUrl).auth;

                switch (auth) {
                    case status.SUCCESS: return resolve();
                    case status.PENDING: setTimeout(() => {
                        return this.authorize().then(resolve).catch(reject);
                    }, 2000);
                }

                if (!this.hash) {
                    return reject(new Error(this.$t('mixin.websocket.hash_not_set')));
                }

                this.addOnOpenHandler(this._authCallback);
                this.addMessageHandler((result) => {
                    if (result.id === this.requestId) {
                        const auth = this._getClient(this.websocketUrl).auth;

                        if (auth === status.SUCCESS) {
                            return resolve();
                        }

                        if (null !== result.error ||
                            (null !== result.result && 'success' !== result.result.status)) {
                            this._logoutClient(this.websocketUrl);
                            return reject(
                                new Error(this.$t('mixin.websocket.authorize_failed') + JSON.stringify(result.error))
                            );
                        }

                        this._authorizeClient(this.websocketUrl);
                        return resolve();
                    }
                }, null, 'WebSocket');
            });
        },
        /**
         * Add additional handler for a websocket stream.
         * @param {function} handler
         * @param {*} id - uniq identifier for a handler to overwrite duplicated handler
         * @param {*} message - message from vue component
         * @return {*}
         */
        addMessageHandler: function(handler, id, message) {
            return this._addMessageHandler({
                url: this.websocketUrl,
                id,
                handler: (result) => {
                    this.sendLogsIfWsError(result, message);
                    handler(result);
                },
            });
        },
        addOnOpenHandler: function(handler) {
            return this._addOnOpenHandler({
                url: this.websocketUrl,
                handler,
            });
        },
        sendMessage: function(message) {
            return this.addOnOpenHandler(() => {
                return this._sendMessage({
                    url: this.websocketUrl,
                    request: message,
                });
            });
        },
        sendLogsIfWsError: function(result, message = '') {
            if (null !== result.error && 'object' === typeof result.error) {
                this.$logger.error(message, result.error);
            }
        },
    },
};
