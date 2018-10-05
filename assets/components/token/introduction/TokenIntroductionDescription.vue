<template>
    <div>
        <div class="card h-100">
            <div class="card-header">
                Description
                <span class="card-header-icon">
                    <font-awesome-icon
                        v-if="editable"
                        class="icon float-right c-pointer"
                        size="2x"
                        :icon="icon"
                        transform="shrink-4 up-1.5"
                        @click="editDescription"
                    />
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <p v-if="!editingDescription">{{ currentDescription }}</p>
                        <template v-if="editable">
                            <textarea class="form-control" v-model="newDescription" v-if="editingDescription"></textarea>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit} from '@fortawesome/free-solid-svg-icons';
import {faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import Toasted from 'vue-toasted';

library.add(faEdit, faCheck);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_NO_CONTENT = 204;
const HTTP_BAD_REQUEST = 400;

export default {
    name: 'TokenIntroductionDescription',
    props: {
        description: String,
        csrfToken: String,
        updateUrl: String,
        editable: Boolean,
    },
    components: {
        FontAwesomeIcon,
    },
    data() {
        return {
            editingDescription: false,
            icon: 'edit',
            currentDescription: this.description,
            newDescription: this.description,
        };
    },
    methods: {
        editDescription: function() {
            if (this.icon === 'check') {
                return this.doEditDescription();
            }
            if (!this.editable) {
                return;
            }
            this.editingDescription = !this.editingDescription;
            this.icon = 'check';
        },
        doEditDescription: function() {
            axios.patch(this.updateUrl, {
                description: this.newDescription,
                _csrf_token: this.csrfToken,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentDescription = this.newDescription;
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data[0][0].message);
                } else {
                    this.$toasted.error('An error has ocurred, please try again later');
                }
            })
            .then(() => {
                this.newDescription = this.currentDescription;
                this.editingDescription = false;
                this.icon = 'edit';
            });
        },
    },
};
</script>

<style lang="sass" scoped>
    p
        white-space: pre-line
</style>

