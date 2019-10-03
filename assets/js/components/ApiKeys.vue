<template>
    <div>
        <template v-if="existed">
            <p>
                Your public key: <span class="text-danger">{{ keys.publicKey }}</span>
                <copy-link class="code-copy c-pointer ml-2" id="pub-copy-btn" :content-to-copy="keys.publicKey">
                    <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                </copy-link>
            </p>
            <template v-if="keys.plainPrivateKey">
                <p>
                    Your private key: <span class="text-danger">{{ keys.plainPrivateKey }}</span>
                    <copy-link
                            class="code-copy c-pointer ml-2"
                            id="private-copy-btn"
                            :content-to-copy="keys.plainPrivateKey">
                        <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                    </copy-link>
                    <br />
                    (Copy this key, you will not able to see it again after reload)
                </p>
            </template>
            <p>Invalidate your API keys:</p>
            <div class="btn btn-primary c-pointer" @click="toggleInvalidateModal(true)">Invalidate</div>
        </template>
        <template v-else>
            <p>Generate your API keys:</p>
            <div class="btn btn-primary c-pointer" @click="generate">Generate</div>
        </template>
        <confirm-modal :visible="invalidateModal" @confirm="invalidate" @close="toggleInvalidateModal(false)">
            <p class="text-white modal-title pt-2">
                Are you sure you want to invalidate your API keys.
                Currently running applications will not work. Continue?
            </p>
        </confirm-modal>
    </div>
</template>

<script>
    import ConfirmModal from './modal/ConfirmModal';
    import CopyLink from './CopyLink';

    export default {
        name: 'ApiKeys',
        components: {ConfirmModal, CopyLink},
        props: {
            apiKeys: {type: [Object, Array], required: true},
        },
        data: function() {
            return {
                keys: this.apiKeys,
                invalidateModal: false,
            };
        },
        computed: {
            existed: function() {
                return this.keys.hasOwnProperty('publicKey');
            },
        },
        methods: {
            generate: function() {
                return this.$axios.single.post(this.$routing.generate('post_keys'))
                    .then((res) => this.keys = res.data)
                    .catch(() => this.$toasted.error('Something went wrong. Try to reload the page.'));
            },
            invalidate: function() {
                return this.$axios.single.delete(this.$routing.generate('delete_keys'))
                    .then((res) => {
                        this.keys = {};
                        this.toggleInvalidateModal(false);
                    })
                    .catch(() => this.$toasted.error('Something went wrong. Try to reload the page.'));
            },
            toggleInvalidateModal: function(on) {
                this.invalidateModal = on;
            },
        },
    };
</script>

<style scoped>

</style>
