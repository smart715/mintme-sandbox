<template>
    <div>
        <div class="card h-100">
            <div class="card-header">
                Description
                <guide class="float-right">
                    <template  slot="header">
                        Description
                    </template>
                    <template slot="body">
                        Description about the goals, milestones and promises.
                        Everything you should know before you buy {{ name }}.
                    </template>
                </guide>

            </div>
            <div class="card-body">
                <div class="row fix-height">
                    <div class="col-12">
                        <span class="card-header-icon">
                            <font-awesome-icon
                                v-if="showEditIcon"
                                class="float-right c-pointer icon-edit"
                                icon="edit"
                                transform="shrink-4 up-1.5"
                                @click="editingDescription = true"/>
                        </span>
                        <p v-if="!editingDescription">{{ description }}</p>
                        <template v-if="editable">
                            <div  v-if="editingDescription">
                                <div class="pb-1">
                                    About your plan:
                                    <guide>
                                        <template slot="header">
                                            About your plan
                                        </template>
                                        <template slot="body">
                                            Write here your plans, goals, proofs of your
                                            identity and everything that may interest buyers.
                                        </template>
                                    </guide>
                                </div>
                                <div class="pb-1 text-xs">Please describe goals milestones plans promises</div>

                                <textarea
                                    class="form-control"
                                    v-model="$v.newDescription.$model"
                                    :class="{ 'is-invalid': $v.$invalid }"
                                >
                                </textarea>
                                <div v-if="!$v.newDescription.minLength || !$v.newDescription.required"
                                     class="text-sm text-danger">
                                    Token Description must be more than one character
                                </div>
                                <div v-if="!$v.newDescription.maxLength" class="text-sm text-danger">
                                    Token Description must be less than {{ maxDescriptionLength }} characters
                                </div>
                                <div class="text-left pt-3">
                                    <button class="btn btn-primary" @click="editDescription">Save</button>
                                    <a class="btn-cancel pl-3 c-pointer" @click="editingDescription = false">Cancel</a>
                                </div>
                            </div>
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
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../../Guide';
import LimitedTextarea from '../../LimitedTextarea';
import Toasted from 'vue-toasted';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';

library.add(faEdit);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_BAD_REQUEST = 400;

export default {
    name: 'TokenIntroductionDescription',
    props: {
        name: String,
        description: String,
        updateUrl: String,
        editable: Boolean,
    },
    components: {
        FontAwesomeIcon,
        Guide,
        LimitedTextarea,
    },
    data() {
        return {
            editingDescription: false,
            newDescription: this.description,
            maxDescriptionLength: 10000,
        };
    },
    computed: {
        showEditIcon: function() {
            return !this.editingDescription && this.editable;
        },
    },
    methods: {
        editDescription: function() {
            this.$v.$touch();
            if (this.$v.$invalid) {
                if (!this.$v.newDescription.minLength || !this.$v.newDescription.required) {
                    this.$toasted.error('Token Description must be more than one character');
                } else if (!this.$v.newDescription.maxLength) {
                    this.$toasted.error('Token Description must be less than '+this.maxDescriptionLength+' characters');
                }
                return;
            }

            this.$axios.single.patch(this.updateUrl, {
                description: this.newDescription,
            })
                .then((response) => {
                    this.$emit('updated', this.newDescription);
                }, (error) => {
                    if (error.response.status === HTTP_BAD_REQUEST) {
                        this.$toasted.error(error.response.data);
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                })
                .then(() => {
                    this.editingDescription = false;
                    this.icon = 'edit';
                });
        },
    },
    validations() {
        return {
            newDescription: {
                required,
                minLength: minLength(2),
                maxLength: maxLength(this.maxDescriptionLength),
            },
        };
    },
    watch: {
        description: function(val) {
            this.newDescription = val;
        },
    },
};
</script>

<style lang="sass" scoped>
    p
        white-space: pre-line
        word-break: break-word
</style>

