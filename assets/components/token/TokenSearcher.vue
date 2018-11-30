<template>
    <div class="input-group">
        <div ref="tokenSearch">
            <autocomplete
                    input-class="search-input form-control"
                    placeholder="Search for token... Use token name or wallet address"
                    v-model="item"
                    :auto-select-one-item="false"
                    @update-items="searchUpdate"
                    @item-selected="searchSelected"
                    :items="items"
                    :min-len="1"
            >
            </autocomplete>
        </div>
        <div class="input-group-append">
            <span class="input-group-text">
                <font-awesome-icon class="c-pointer" @click="searchSelected" icon="search"></font-awesome-icon>
            </span>
        </div>
    </div>
</template>

<script>
import Autocomplete from 'v-autocomplete';

export default {
    name: 'TokenSearcher',
    components: {
        Autocomplete,
    },
    props: {
        searchUrl: {type: String, required: true},
    },
    data() {
        return {
            item: '',
            items: [],
        };
    },
    methods: {
        searchUpdate: function(value) {
            this.$axios.get(
                this.searchUrl,
                {params: {tokenName: value}}
            ).then((response) => {
                this.items = response.data.map((token) => {
                    return token.name;
                });
            }).catch((error) => {
                console.error('Service timeout');
            });
        },
        searchSelected: function() {
            location.href = this.$routing.generate('token_show', {name: this.item}, true);
        },
    },
};
</script>
