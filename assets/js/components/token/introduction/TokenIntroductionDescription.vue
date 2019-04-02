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
                        <p v-if="!editingDescription">{{ currentDescription }}</p>
                        <template v-if="editable">
                            <div  v-if="editingDescription">
                                <div class="pb-1">
                                    About your plan
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
                                    v-model="newDescription"
                                    max="20000"
                                    @get-value="getValue">
                                </textarea>
                                <div class="text-left pt-3">
                                    <button class="btn btn-primary" @click="editDescription">Save</button>
                                    <a class="pl-3 c-pointer" @click="editingDescription = false">Cancel</a>
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

library.add(faEdit);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_NO_CONTENT = 204;
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
            currentDescription: this.description,
            newDescription: this.description,
        };
    },
    computed: {
        showEditIcon: function() {
            return !this.editingDescription && this.editable;
        },
    },
    methods: {
        getValue: function(newValue) {
            this.newDescription = newValue;
        },
        editDescription: function() {
            return this.doEditDescription();
        },
        doEditDescription: function() {
            this.$axios.single.patch(this.updateUrl, {
                description: this.newDescription,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentDescription = this.newDescription;
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data[0][0].message);
                } else {
                    this.$toasted.error('An error has occurred, please try again later');
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
        word-break: break-word
</style>

