<template>
    <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
        <b-tooltip
            :target="() => $refs['a']"
            :title="value"
            :disabled.sync="disableTooltip"
            boundary="viewport"
        />
        <img
            v-if="img"
            :src="img"
            class="d-block rounded-circle"
            alt="avatar">
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

export default {
    name: 'ElasticText',
    components: {
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
