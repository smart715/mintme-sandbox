<template>
    <div class="input-group">
        <div ref="tokenSearch" @keyup.enter="redirectToToken">
            <autocomplete
                    :input-class="inputClass"
                    placeholder="Search for the token"
                    :auto-select-one-item="false"
                    @update-items="searchUpdate"
                    @item-clicked="onItemClicked"
                    @change="onInputChange"
                    :items="items"
                    :min-len="3"
                    :input-attrs="inputAttrs"
            >
            </autocomplete>
        </div>
        <div class="input-group-append">
            <span class="input-group-text text-white pl-2">
                <font-awesome-icon class="c-pointer" @click="redirectToToken" icon="search"></font-awesome-icon>
            </span>
        </div>
    </div>
</template>

<script>
import Autocomplete from 'v-autocomplete';
import {NotificationMixin} from '../../mixins';

const tokenRegEx = new RegExp('^[a-zA-Z0-9\\-\\s]*$');

export default {
    name: 'TokenSearcher',
    mixins: 'NotificationMixin',
    components: {
        Autocomplete,
    },
    props: {
        searchUrl: {type: String, required: true},
    },
    data() {
        return {
            validName: true,
            input: '',
            items: [],
            inputAttrs: {
                maxlength: 60,
            },
        };
    },
    methods: {
        searchUpdate: function(value) {
            this.$axios.retry.get(
                this.searchUrl,
                {params: {tokenName: value}}
            ).then((response) => {
                this.items = response.data.map((token) => {
                    return token.name;
                });
            }).catch((error) => {
                this.notifyError('Service timeout');
            });
        },
        redirectToToken: function() {
            if (!tokenRegEx.test(this.input)) {
                return;
            }
            if (this.input.trim().length === 0) {
                location.href = this.$routing.generate('trading');
                return;
            }
            location.href = this.$routing.generate('token_show', {name: this.input}, true);
        },
        onItemClicked: function(val) {
            this.input = val;
            this.redirectToToken();
        },
        onInputChange: function(val) {
            this.validName = tokenRegEx.test(val);
            this.input = val;
            this.items = [];
        },
    },
    computed: {
        inputClass: function() {
            return 'search-input form-control pr-3 no-bg-img ' + (this.validName ? '' : 'is-invalid');
        },
    },
};
</script>
