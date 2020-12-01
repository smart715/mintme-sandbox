import {mapActions, mapGetters} from 'vuex';
import {status} from '../storage/modules/websocket';
import LoggerMixin from './logger';

export default {
    mixins: [LoggerMixin],
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
            let auth = this._getClient(this.websocketUrl).auth;

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
                let auth = this._getClient(this.websocketUrl).auth;

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
                        let auth = this._getClient(this.websocketUrl).auth;

                        if (auth === status.SUCCESS) {
                            return resolve();
                        }

                        if (result.error !== null ||
                            (result.result !== null && result.result.status !== 'success')) {
                            this._logoutClient(this.websocketUrl);
                            return reject(new Error(this.$t('mixin.websocket.authorize_failed') + JSON.stringify(result.error)));
                        }

                        this._authorizeClient(this.websocketUrl);
                        return resolve();
                    }
                });
            });
        },
        /**
         * Add additional handler for a websocket stream.
         * @param {function} handler
         * @param {*} id - uniq identifier for a handler to overwrite duplicated handler
         * @param {*} message - message from vue component
         * @return {*}
         */
        addMessageHandler: function(handler, id = null, message = '') {
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
            if ((null !== result.error && '' !== result.error && undefined !== result.error) ||
                (null !== result.result
                    && undefined !== result.result
                    && null !== result.result.status
                    && 'success' !== result.result.status)
                ) {
                    let context = null !== result.error
                        ? JSON.stringify(result.error)
                        : result.result.status;
                    this.sendLogs('error', message, new Error(context));
            }
        },
    },
};
