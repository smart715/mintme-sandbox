<template>
    <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
        <b-tooltip
            :target="() => $refs['a']"
            :title="value"
            :disabled.sync="disableTooltip"
            boundary="viewport"
        />
        <img
            :src="img"
            class="d-block rounded-circle"
            alt="avatar">
        <a
            ref="a"
            :href="url"
            class="holder-name mr-1 text-white"
        >
            <v-clamp autoresize @clampchange="updateTooltip" :max-lines="1">
                {{ value }}
            </v-clamp>
        </a>
    </div>
</template>

<script>
import VClamp from 'vue-clamp/dist/vue-clamp';

export default {
    name: 'HolderName',
    components: {
        VClamp,
    },
    data() {
      return {
          disableTooltip: false,
      };
    },
    props: {
        value: String,
        url: String,
        img: String,
    },
    methods: {
        updateTooltip: function(val) {
            this.disableTooltip = !val;
        },
    },
};
</script>
