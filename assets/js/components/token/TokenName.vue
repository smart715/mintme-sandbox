<template>
    <div v-on-clickaway="cancelEditingMode">
        <template v-if="editable">
            <input
                type="text"
                v-model="$v.newName.$model"
                v-if="editingName"
                :class="{ 'is-invalid': $v.newName.$error }"
                ref="tokenNameInput">
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
import {mixin as clickaway} from 'vue-clickaway';
import {minLength, maxLength, alphaNum} from 'vuelidate/lib/validators';

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
            this.$nextTick(() => {
                let tokenNameInput = this.$refs.tokenNameInput;
                tokenNameInput.focus();
            });
        },
        doEditName: function() {
            this.$v.$touch();
            if (!$v.newName.alphaNum) {
                this.$toasted.error('Token name can contain alphabets and numbers');
                return;
            } else if (!$v.newName.minLength) {
                this.$toasted.error('Token name can have at least 4 symbols');
                return;
            } else if (!$v.newName.minLength) {
                this.$toasted.error('Token name can not be longer than 255 characters');
                return;
            }
            this.$axios.single.patch(this.updateUrl, {
                name: this.newName,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentName = this.newName;
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
            this.$v.$reset();
            this.newName = this.currentName;
            this.editingName = false;
            this.icon = 'edit';
        },
    },
    validations: {
        newName: {
            alphaNum,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
};
</script>


