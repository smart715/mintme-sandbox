<template>
    <b-dropdown
    variant="primary"
    class="float-left"
    lazy="true">
        <template slot="button-content">
            <span v-if="showMonth">
                {{ dataMonthVolume }}
            </span>
            <span v-else>
                {{ dataDayVolume }}
            </span>
        </template>
        <template v-if="showMonth">
            <b-dropdown-item active @click="toggleMonth(true)">
                {{ dataMonthVolume }}
            </b-dropdown-item>
            <b-dropdown-item @click="toggleMonth(false)">
                {{ dataDayVolume }}
            </b-dropdown-item>
        </template>
        <template v-else >
            <b-dropdown-item  @click="toggleMonth(true)">
                {{ dataMonthVolume }}
            </b-dropdown-item>
            <b-dropdown-item active @click="toggleMonth(false)">
                {{ dataDayVolume }}
            </b-dropdown-item>
        </template>
    </b-dropdown>
</template>

<script>
export default {
    name: 'SelectPeriod',
    props: {
        dataDayVolume: String,
        dataMonthVolume: String,
        showMonth: Boolean,
        toggleMonth: Function,
    },
};
</script>
