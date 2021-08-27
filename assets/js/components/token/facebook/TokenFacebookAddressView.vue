<template>
    <div class="row">
        <div class="col text-truncate">
            {{ $t('token.facebook.view_label') }}
            <a
                :href="currentAddress"
                target="_blank"
                rel="nofollow"
                v-b-tooltip.hover :title="currentAddress"
            >
                {{ currentAddress }}
            </a>
        </div>
        <div class="col-auto">
            <div
                class="fb-share-button"
                :data-href="currentAddress"
                data-layout="button_count"
                data-size="small"
                data-mobile-iframe="true"
            >
                <a
                    target="_blank"
                    :href="facebookShareButtonUrl"
                    class="fb-xfbml-parse-ignore"
                ></a>
            </div>
        </div>
        <div class="col-auto social-help">
            <guide>
                <template slot="header">
                  {{ $t('token.facebook.guide_header') }}
                </template>
                <template slot="body">
                  <span v-html="this.$t('token.facebook.guide_body')"></span>
                </template>
            </guide>
        </div>
    </div>
</template>

<script>
import {VBTooltip} from 'bootstrap-vue';
import {FiltersMixin} from '../../../mixins';
import Guide from '../../Guide';

export default {
    name: 'TokenFacebookAddressView',
    components: {
        Guide,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
    ],
    props: {
        address: String,
    },
    data() {
        return {
            currentAddress: this.address,
        };
    },
    computed: {
        facebookShareButtonUrl: function() {
            return 'https://www.facebook.com/sharer/sharer.php?u='
                + encodeURIComponent(this.currentAddress)
                + '&amp;src=sdkpreparse';
        },
    },
};
</script>


