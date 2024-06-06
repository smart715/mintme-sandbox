<template>
    <div class="d-flex align-items-center">
        <div class="d-flex align-items-center">
            <h3
                class="table-header"
                v-html="header"
            />
            <slot name="left-side"></slot>
        </div>
        <slot name="right-side"></slot>
    </div>
</template>

<script>
export default {
    name: 'TableHeader',
    props: {
        header: String,
    },
};
</script>
