<template>
    <div>
        <button class="btn px-1 py-0" @click="deleteToken">
            <img :src="deleteImage" alt="&times;" style="height: 18px;" class="d-block my-auto">
        </button>
    </div>
</template>

<script>

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenDelete',
    props: {
        deleteUrl: String,
        deleteImage: String,
    },
    data() {
        return {};
    },
    methods: {
        deleteToken: function() {
            if (confirm('Are you sure to delete token?')) {
                this.$axios.single.delete(this.deleteUrl)
                    .then((response) => {
                        if (response.status === HTTP_ACCEPTED) {
                            location.href = this.$routing.generate('profile-view', {pageUrl: response.data.pageUrl});
                        }
                    }, (error) => {
                        if (!error.response) {
                            this.$toasted.error('Network error');
                        } else if (error.response.data.message) {
                            this.$toasted.error(error.response.data.message);
                        } else {
                            this.$toasted.error('An error has occurred, please try again later');
                        }
                    });
            }
        },
    },
};
</script>
