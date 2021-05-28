<template>
    <div class="row">
        <div class="col text-truncate">
            {{ $t('token.website.view_label') }}
            <a
                :href="currentWebsite"
                target="_blank"
                rel="nofollow"
                v-b-tooltip.hover :title="currentWebsite"
            >
                {{ currentWebsite }}
            </a>
        </div>
        <div class="col-auto social-help">
            <guide>
                <template  slot="header">
                  {{ $t('token.website.guide_header') }}
                </template>
                <template slot="body">
                  {{ $t('token.website.guide_body') }}
                </template>
            </guide>
        </div>
    </div>
</template>

<script>
import {VBTooltip} from 'bootstrap-vue';
import Guide from '../../Guide';

export default {
    name: 'TokenWebsiteAddress',
    components: {
        Guide,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        currentWebsite: String,
    },
};
</script>
