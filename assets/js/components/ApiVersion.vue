<template>
    <b-dropdown
        variant="primary"
        menu-class="min-width-0"
        right
        :lazy="true"
        v-model="options.selected"
    >
        <template slot="button-content">
            <span>{{ options.versions[options.selected].label }}</span>
        </template>
        <template>
            <b-dropdown-item
                v-for="version in options.versions"
                v-bind:key="version.key"
                :href="version.key"
            >
                {{ version.label }}
            </b-dropdown-item>
        </template>
    </b-dropdown>
</template>

<script>

export default {
    name: 'ApiVersion',
    props: {
        urlVersion: String,
    },
    data() {
        return {
            options: {
                selected: this.urlVersion,
                versions: {
                    v1: {
                        key: 'v1',
                        label: 'v1',
                    },
                    v2: {
                        key: 'v2',
                        label: 'v2',
                    },
                },
            },
        };
    },
};

</script>