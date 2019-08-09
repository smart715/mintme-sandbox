<template>
    <div>
        <div v-on-clickaway="cancelEditingMode">
            <template v-if="editable">
                <input
                    type="text"
                    v-model.trim="$v.newName.$model"
                    v-if="editingName"
                    ref="tokenNameInput"
                    class="token-name-input token-name-font"
                    :class="{ 'is-invalid': $v.$invalid }">
                <font-awesome-icon
                    class="icon-edit c-pointer align-middle"
                    :icon="icon"
                    transform="shrink-4 up-1.5"
                    @click="editName"
                />
            </template>
            <span v-if="!editingName" class="token-name-font" v-b-tooltip="{title: currentName, boundary:'viewport'}">
                {{ currentName | truncate(7) }}
            </span>
        </div>
        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            @verify="doEditName"
            @close="closeTwoFactorModal"
            />
        <span v-if="!editingName" class="token-name-font" v-b-tooltip="{title: currentName, boundary:'viewport'}">
            {{ currentName | truncate(7) }}
        </span>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Toasted from 'vue-toasted';
import {mixin as clickaway} from 'vue-clickaway';
import {WebSocketMixin, FiltersMixin} from '../../mixins';
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';
import TwoFactorModal from '../modal/TwoFactorModal';

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
        identifier: String,
        updateUrl: String,
        editable: Boolean,
        twofa: String,
    },
    components: {
        FontAwesomeIcon,
        TwoFactorModal,
    },
    mixins: [WebSocketMixin, FiltersMixin, clickaway],
    data() {
        return {
            editingName: false,
            icon: 'edit',
            currentName: this.name,
            newName: this.name,
            isTokenExchanged: true,
            minLength: 4,
            maxLength: 60,
            showTwoFactorModal: false,
        };
    },
    mounted: function() {
        if (!this.editable) {
            return;
        }

        this.checkIfTokenExchanged();

        this.addMessageHandler((response) => {
            if ('asset.update' === response.method && response.params[0].hasOwnProperty(this.identifier)) {
                this.checkIfTokenExchanged();
            }
        }, 'token-name-asset-update');
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        checkIfTokenExchanged: function() {
            this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {
                name: this.currentName,
            }))
                .then((res) => this.isTokenExchanged = res.data)
                .catch(() => this.$toasted.error('Can not fetch token data now. Try later'));
        },
        editName: function() {
            if (!this.editable) {
                return;
            }

            if (null === this.isTokenExchanged || this.isTokenExchanged) {
                this.$toasted.error('You need all your tokens to change token\'s name');
                return;
            }

            if (this.icon === 'check') {
                this.$v.$touch();
                if (this.currentName === this.newName) {
                    this.cancelEditingMode();
                    return;
                } else if (!this.newName || this.newName.replace(/-/g, '').length === 0) {
                    this.$toasted.error('Token name shouldn\'t be blank');
                    return;
                } else if (!this.$v.newName.tokenContain) {
                    this.$toasted.error('Token name can contain alphabets, numbers, spaces and dashes');
                    return;
                } else if (!this.$v.newName.minLength || this.newName.replace(/-/g, '').length < this.minLength) {
                    this.$toasted.error('Token name should have at least 4 symbols');
                    return;
                } else if (!this.$v.newName.maxLength) {
                    this.$toasted.error('Token name can not be longer than 60 characters');
                    return;
                }

                if (!this.twofa) {
                    return this.doEditName();
                }

                return this.showTwoFactorModal = true;
            }

            this.editingName = !this.editingName;
            this.icon = 'check';
            this.$nextTick(() => {
                let tokenNameInput = this.$refs.tokenNameInput;
                tokenNameInput.focus();
            });
        },
        doEditName: function(code = '') {
            this.$axios.single.patch(this.updateUrl, {
                'name': this.newName,
                'code': code,
            })
            .then((response) => {
                if (response.status === HTTP_ACCEPTED) {
                    this.currentName = response.data['tokenName'];

                    // TODO: update name in a related components and link path instead of redirecting
                    location.href = this.$routing.generate('token_show', {
                        name: this.currentName,
                    });
                    this.cancelEditingMode();
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data.message);
                } else {
                    this.$toasted.error('An error has occurred, please try again later');
                }
            });
        },
        cancelEditingMode: function() {
            if (!this.showTwoFactorModal) {
                this.$v.$reset();
                this.newName = this.currentName;
                this.editingName = false;
                this.icon = 'edit';
            }
        },
    },
    validations() {
        return {
            newName: {
                required,
                tokenContain: tokenContain,
                minLength: minLength(this.minLength),
                maxLength: maxLength(this.maxLength),
            },
        };
    },
};
</script>

