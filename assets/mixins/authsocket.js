import WebSocket from '../js/websocket';

const METHOD_AUTH = 12345;

Vue.use(WebSocket);

export default {
    props: {
        websocketUrl: String,
        hash: String,
    },
    data() {
        return {
            wsClient: null,
        };
    },
    created: function() {
        this.wsClient = this.$socket(this.websocketUrl);
        this.wsClient.onopen = () => {
            this.wsClient.send(JSON.stringify({
                method: 'server.auth',
                params: [this.hash, 'auth_api'],
                id: METHOD_AUTH,
            }));
        };
    },
    methods: {
        authorize: function(onAuth, onResponse) {
            this.wsClient.onmessage = (result) => {
                let response = JSON.parse(result.data);

                if (response.id === METHOD_AUTH) {
                    onAuth();
                } else {
                    onResponse(response);
                }
            };
        },
    },
};
