<script setup>
const props = defineProps(["err"])
</script>

<template>
    <div v-if='props.err != undefined && props.err.isError()' class='error'>{{ props.err.getError() }}</div>
</template>