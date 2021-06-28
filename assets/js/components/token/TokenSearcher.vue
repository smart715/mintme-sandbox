<template>
    <div class="input-group">
        <div ref="tokenSearch" @keyup.enter="onItemSelected">
            <autocomplete
                    ref="searchInput"
                    :value="searchValue"
                    :input-class="inputClass"
                    :placeholder="this.$t('token.searcher.placeholder')"
                    :auto-select-one-item="false"
                    @update-items="searchUpdate"
                    @item-clicked="onItemSelected"
                    @change="onInputChange"
                    :items="items"
                    :min-len="3"
                    :input-attrs="inputAttrs"
            >
            </autocomplete>
        </div>
        <div class="input-group-append position-relative ml-2">
            <div v-if="input" class="clear-search-icon">
                <font-awesome-icon size="xs" @click="clearSearch" class="c-pointer hover-icon" icon="times"></font-awesome-icon>
            </div>
            <span class="input-group-text text-white">
                <font-awesome-icon class="c-pointer hover-icon" @click="redirectToToken" icon="search"></font-awesome-icon>
            </span>
        </div>
    </div>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes, faSearch} from '@fortawesome/free-solid-svg-icons';
import Autocomplete from 'v-autocomplete';
import {LoggerMixin} from '../../mixins';

library.add(faTimes, faSearch);

const tokenRegEx = new RegExp('^[a-zA-Z0-9\\-\\s]*$');

export default {
    name: 'TokenSearcher',
    mixins: [LoggerMixin],
    components: {
        Autocomplete,
        FontAwesomeIcon,
    },
    props: {
        searchUrl: {type: String, required: true},
        isLoggedIn: Boolean,
    },
    data() {
        return {
            validName: true,
            input: '',
            searchValue: undefined,
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
                this.sendLogs('error', 'Service timeout error', error);
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
        onItemSelected: function(val) {
            this.input = val.isTrusted ? val.target.value : val;
            this.redirectToToken();
        },
        onInputChange: function(val) {
            this.validName = tokenRegEx.test(val);
            this.input = val;
            this.items = [];
        },
        clearSearch: function() {
            this.input = '';
            this.searchValue = '';
            this.items = [];
            this.$nextTick(() => {
                this.searchValue = undefined;
            });
        },
    },
    computed: {
        inputClass: function() {
            return this.isLoggedIn ? 'search-input logged form-control no-bg-img' : 'search-input form-control no-bg-img';
        },
    },
};
</script>
