<template>
    <div>
        <template v-if="editable">
            <input type="text" v-model="newName" v-if="editingName">
            <font-awesome-icon
                class="icon-edit c-pointer align-middle"
                :icon="icon"
                transform="shrink-4 up-1.5"
                @click="editName" />
        </template>
        <span v-if="!editingName">{{ currentName }}</span>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit} from '@fortawesome/free-solid-svg-icons';
import {faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Toasted from 'vue-toasted';

library.add(faEdit, faCheck);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_NO_CONTENT = 204;
const HTTP_BAD_REQUEST = 400;

export default {
    name: 'TokenName',
    props: {
        name: String,
        csrfToken: String,
        updateUrl: String,
        editable: Boolean,
    },
    components: {
        FontAwesomeIcon,
    },
    data() {
        return {
            editingName: false,
            icon: 'edit',
            currentName: this.name,
            newName: this.name,
        };
    },
    methods: {
        editName: function() {
            if (this.icon === 'check') {
                return this.doEditName();
            }

            if (!this.editable) {
                return;
            }

            this.editingName = !this.editingName;
            this.icon = 'check';
        },
        doEditName: function() {
            this.$axios.single.patch(this.updateUrl, {
                name: this.newName,
                _csrf_token: this.csrfToken,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentName = this.newName;
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data[0][0].message);
                } else {
                    this.$toasted.error('An error has ocurred, please try again later');
                }
            })
            .then(() => {
                this.newName = this.currentName;
                this.editingName = false;
                this.icon = 'edit';
            });
        },
    },
};
</script>

<style lang="sass" scoped>
    h1
        font-size: 2rem
        color: #fff

    .icon
        cursor: pointer

    input[type="text"]
        background-color: #fff
        border-style: unset
        padding: 0 5px</style>


