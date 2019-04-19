<template>
    <div v-on-clickaway="cancelEditingMode">
        <template v-if="allowEdit">
            <input
                type="text"
                v-model.trim="$v.newName.$model"
                v-if="editingName"
                ref="tokenNameInput"
                :class="{ 'is-invalid': $v.$invalid }">
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
import {faEdit, faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Toasted from 'vue-toasted';
import {mixin as clickaway} from 'vue-clickaway';
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';

const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);


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
            this.$v.$touch();
            if (!this.$v.newName.tokenContain) {
                this.$toasted.error('Token name can contain alphabets, numbers, spaces and dashes');
                return;
            } else if (!this.$v.newName.minLength) {
                this.$toasted.error('Token name can have at least 4 symbols');
                return;
            } else if (!this.$v.newName.maxLength) {
                this.$toasted.error('Token name can not be longer than 255 characters');
                return;
            }

            this.$axios.single.patch(this.updateUrl, {
                name: this.newName,
            })
            .then((response) => {
                if (response.status === HTTP_ACCEPTED) {
                    this.currentName = response.data['tokenName'];

                    // TODO: update name in a related components and link path instead of redirecting
                    location.href = this.$routing.generate('token_show', {
                        name: this.currentName,
                    });
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data.message);
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
    computed: {
        allowEdit: function() {
          return this.editable && null !== this.isTokenExchanged && !this.isTokenExchanged;
        },
    },
    validations() {
        return {
            newName: {
                required,
                tokenContain: tokenContain,
                minLength: minLength(4),
                maxLength: maxLength(255),
            },
        };
    },
};
</script>

