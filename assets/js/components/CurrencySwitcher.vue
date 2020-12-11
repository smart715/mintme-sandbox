<template>
    <b-dropdown
        id="currency"
        variant="primary"
        class="d-inline"
        :lazy="true"
    >
        <template slot="button-content">
            <span v-if="showUsd">
                {{ $t('trading.currency.usd') }}
            </span>
            <span v-else>
                {{ $t('trading.currency.crypto') }}
            </span>
        </template>
        <template>
            <b-dropdown-item @click="toggleUsd(false)">
                {{ $t('trading.currency.crypto') }}
            </b-dropdown-item>
            <b-dropdown-item class="usdOption" :disabled="!enableUsd" @click="toggleUsd(true)">
                {{ $t('trading.currency.usd') }}
            </b-dropdown-item>
        </template>
    </b-dropdown>

</template>

<script>
export default {
    name: 'CurrencySwitcher',
    data() {
        return {
            showUsd: true,
            enableUsd: true,
        };
    },
    mounted() {
    },
    methods: {
        toggleUsd: function(show) {
            this.showUsd = show;
        },
    },
    computed: {

    },
}
</script>
