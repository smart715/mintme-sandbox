<template>
    <div v-on-clickaway="cancelEditingMode">
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
import {faEdit, faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Toasted from 'vue-toasted';
import {mixin as clickaway} from 'vue-clickaway';

library.add(faEdit, faCheck);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_ACCEPTED = 202;
const HTTP_BAD_REQUEST = 400;

export default {
    name: 'TokenName',
    props: {
        name: String,
        updateUrl: String,
        editable: Boolean,
    },
    components: {
        FontAwesomeIcon,
    },
    mixins: [clickaway],
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
            })
            .then((response) => {
                console.log(response);
                if (response.status === HTTP_ACCEPTED) {
                    this.currentName = response.data['tokenName'];
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data);
                } else {
                    this.$toasted.error('An error has ocurred, please try again later');
                }
            })
            .then(() => {
                this.cancelEditingMode();
            });
        },
        cancelEditingMode: function() {
            this.newName = this.currentName;
            this.editingName = false;
            this.icon = 'edit';
        },
    },
};
</script>

<style lang="sass" scoped>
    input
        font-family: monospace
        font-size: 0.8em
    h1
        font-size: 2rem
        color: #fff
        span
            font-family: monospace
            font-size: 0.8em

    .icon
        cursor: pointer

    input[type="text"]
        background-color: #fff
        border-style: unset
        padding: 0 5px
</style>


