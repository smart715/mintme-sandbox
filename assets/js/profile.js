import LimitedTextarea from '../components/LimitedTextarea.vue';
new Vue({
    el: '#profile',
    data: {
        showEditForm: false,
    },
    mounted: function() {
        this.showEditForm = this.$refs.editFormShowFirst.value;
    },
    components: {
        LimitedTextarea,
    },
});
