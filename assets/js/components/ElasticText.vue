<template>
    <div
        class="elastic-text-wrapper d-flex flex-row flex-nowrap
            justify-content-between w-100 align-items-center position-relative"
    >
        <b-tooltip
            :target="() => $refs['a']"
            :title="value"
            :disabled.sync="disableTooltip"
            boundary="viewport"
        />
        <img
            v-if="img"
            :src="img"
            class="d-block rounded-circle mr-2"
            alt="avatar"
        />
        <img
            v-if="frame"
            :src="frame"
            class="wreath d-block mr-2"
            alt="wreath"
        />
        <comment
            :is="component"
            ref="a"
            :href="url"
            class="elastic-text mr-1 text-white"
        >
            <v-clamp autoresize @clampchange="updateTooltip" :max-lines="1">
                {{ value }}
            </v-clamp>
        </comment>
    </div>
</template>

<script>
import VClamp from 'vue-clamp/dist/vue-clamp';
import {BTooltip} from 'bootstrap-vue';
import Comment from './posts/Comment.vue';

export default {
    name: 'ElasticText',
    components: {
        Comment,
        BTooltip,
        VClamp,
    },
    props: {
        value: String,
        url: {
            type: String,
            default: null,
        },
        img: {
            type: String,
            default: null,
        },
        frame: {
            type: String,
            default: null,
        },
    },
    computed: {
        component: function() {
            return this.url ? 'a' : 'span';
        },
    },
    data() {
        return {
            disableTooltip: false,
        };
    },
    methods: {
        updateTooltip: function(val) {
            this.disableTooltip = !val;
        },
    },
};
</script>
