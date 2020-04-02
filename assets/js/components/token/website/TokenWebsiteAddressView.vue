<template>
    <div>
        Web:
        <a
            :href="currentWebsite"
            target="_blank"
            rel="nofollow"
        >
            {{ currentWebsite }}
        </a>
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
