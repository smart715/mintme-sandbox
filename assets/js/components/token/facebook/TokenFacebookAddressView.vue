<template>
    <div>
        <div class="d-flex-inline">
            <div class="display-text">
                Facebook:
                <a
                    :href="currentAddress"
                    target="_blank"
                    rel="nofollow"
                >
                    {{ currentAddress }}
                </a>
                <div
                    class="fb-share-button"
                    :data-href="currentAddress"
                    data-layout="button_count"
                    data-size="small"
                    data-mobile-iframe="true"
                >
                    <a
                        target="_blank"
                        :href="'https://www.facebook.com/sharer/sharer.php?u='
                        +currentAddressEncoded+'&amp;src=sdkpreparse'"
                        class="fb-xfbml-parse-ignore"
                    ></a>
                </div>
                <guide>
                    <template slot="header">
                        Facebook
                    </template>
                    <template slot="body">
                        Link to token creator’s Facebook.
                        Before adding it, we confirmed ownership.
                    </template>
                </guide>
            </div>
        </div>
    </div>
</template>

<script>
import {FiltersMixin} from '../../../mixins';
import Guide from '../../Guide';

export default {
    name: 'TokenFacebookAddressView',
    props: {
        address: String,
        appId: String,
    },
    components: {
        Guide,
    },
    mixins: [FiltersMixin],
    created: function() {
        this.loadFacebookSdk();
    },
    data() {
        return {
            currentAddress: this.address,
        };
    },
    computed: {
        currentAddressEncoded: function() {
            return encodeURIComponent(this.currentAddress);
        },
    },
    methods: {
        loadFacebookSdk: function() {
            window.fbAsyncInit = () => {
                FB.init({
                    appId: this.appId,
                    autoLogAppEvents: true,
                    xfbml: true,
                    version: 'v3.1',
                });
            };

            (function(d, s, id) {
                let js; let fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {
                    return;
                }
                js = d.createElement(s); js.id = id;
                js.src = 'https://connect.facebook.net/en_US/sdk.js';
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        },
    },
};
</script>

<style lang="sass" scoped>
    .display-text
        display: inline-block
        width: 100%
        text-overflow: ellipsis
</style>
