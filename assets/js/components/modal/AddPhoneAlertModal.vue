<template>
    <div>
        <modal
            :visible="visible"
            @close="closeModal">
            <template slot="body">
                <div class="text-center">
                    <div class="col-12 pt-2" v-html="$sanitize(body)"></div>
                    <div class="pt-2 text-center">
                        <button
                            class="btn btn-primary"
                            @click="closeModal"
                        >
                            {{ $t('deposit_modal.ok') }}
                        </button>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';

export default {
    name: 'AddPhoneAlertModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        nickname: String,
    },
    computed: {
        body: function() {
            return this.$t('modal.add_phone_alert', {
                profileUrl: this.$routing.generate('profile-view', {
                    nickname: this.nickname,
                    edit: 1,
                }),
            });
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>
