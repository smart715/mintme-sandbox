<template>
    <div class="vld-parent">
        <loading
            :active.sync="isLoading"
            :opacity="0.8"
            background-color="#000000"
            color="#c0c0c0"
        >
            <div class="text-info" slot="after">
               <p>
                   CREATING BACKEND SERVICES... PLEASE DON'T REFRESH THE PAGE.
               </p>
            </div>
        </loading>
    </div>
</template>

<script>
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';

export default {
    props: {
        isLoading: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            fullPage: true,
        };
    },
    components: {
        Loading,
    },
    methods: {
        onCancel: function() {
            console.log('User cancelled the loader.');
        },
    },
};
</script>
<style lang="sass">
    .vld-icon
        background-image: none
    .text-info
        margin-right: -787px
        margin-left: -414px
        padding-top: 26px
        font-size: 2em
        color: white !important
</style>
