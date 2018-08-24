<template>
    <div>
        <div v-if="formLoaded">
            <h2 slot="header">{{ formTitle }}</h2>
            <div
                v-html="formBody"
                @click.capture="handleFormClick"
                slot="body">
            </div>
        </div>
        <div class="row" v-if="!formLoaded">
            <div class="col text-center mt-3">
                <font-awesome-layers class="fa-3x">
                    <font-awesome-icon
                        icon="circle-notch"
                        spin fixed-width  />
                </font-awesome-layers>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import serialize from 'form-serialize';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';

export default {
    name: 'ChildForm',
    components: {
        FontAwesomeLayers,
        FontAwesomeIcon,
    },
    data() {
        return {
            formLoaded: false,
            formTitle: '',
            formBody: '',
        };
    },
    methods: {
        loadForm: function(url) {
            axios.get(url)
                .then((response) => {
                    this.formTitle = response.data.header;
                    this.formBody = response.data.body;
                    this.formLoaded = true;
                })
                .catch((error) => {
                    this.$emit('error');
                });
        },
        handleFormClick: function(e) {
            if (e.target.tagName == 'BUTTON' && e.target.type == 'submit') {
                e.preventDefault();
                let data = serialize(e.target.form, {
                    hash: false,
                    empty: true,
                });
                data += '&' + e.target.name + '='
                    + encodeURIComponent(e.target.value);
                let url = e.target.form.action;
                axios.post(url, data)
                    .then((response) => {
                        if (response.data.action) {
                            this.$emit('success-submit', response);
                        } else {
                            this.formTitle = response.data.header;
                            this.formBody = response.data.body;
                        }
                    })
                    .catch((error) => {
                        this.$emit('error');
                    });
            }
            if (e.target.tagName == 'A' && e.target.hasAttribute('close')) {
               this.closeForm();
            }
        },
        closeForm: function() {
            this.formLoaded = false;
            this.$emit('close');
        },
    },
};
</script>
