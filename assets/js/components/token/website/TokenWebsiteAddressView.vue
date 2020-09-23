<template>
    <div class="row">
        <div class="col text-truncate">
            Web:
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
                    Web
                </template>
                <template slot="body">
                    Link to token creator’s website.
                    Before adding it, we confirmed ownership.
                </template>
            </guide>
        </div>
    </div>
</template>

<script>
import Guide from '../../Guide';

export default {
    name: 'TokenWebsiteAddress',
    props: {
        currentWebsite: String,
    },
    components: {
        Guide,
    },
};
</script>
