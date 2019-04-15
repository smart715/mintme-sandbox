<template>
    <div v-on-clickaway="cancelEditingMode">
        <template v-if="allowEdit">
            <input
                type="text"
                v-model="newName"
                v-if="editingName"
                ref="tokenNameInput">
            <font-awesome-icon
                class="icon-edit c-pointer align-middle"
                :icon="icon"
                transform="shrink-4 up-1.5"
                @click="editName"
            />
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
import {mixin as clickaway} from 'vue-clickaway';

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
            isTokenExchanged: true,
        };
    },
    mounted: function() {
        if (!this.editable) {
            return;
        }

        this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {
                name: this.currentName,
            }))
            .then((res) => this.isTokenExchanged = res.data)
            .catch(() => this.$toasted.error('Can not fetch token data now. Try later'));
    },
    methods: {
        editName: function() {
            if (!this.allowEdit) {
                return;
            }

            if (this.icon === 'check') {
                return this.doEditName();
            }

            this.editingName = !this.editingName;
            this.icon = 'check';
            this.$nextTick(() => {
                let tokenNameInput = this.$refs.tokenNameInput;
                tokenNameInput.focus();
            });
        },
        doEditName: function() {
            this.$axios.single.patch(this.updateUrl, {
                name: this.newName,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentName = this.newName;

                    // TODO: update name in a related components and link path instead of redirecting
                    location.href = this.$routing.generate('token_show', {
                        name: this.currentName,
                    });
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error('Invalid token name');
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
    computed: {
        allowEdit: function() {
          return this.editable && null !== this.isTokenExchanged && !this.isTokenExchanged;
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
        padding: 0 5px
</style>


