<script setup>
const props = defineProps(["err", "class"])
</script>

<template>
    <div :class="props.class">
        <div v-if='props.err != undefined && props.err.isError()' class='alert alert-danger'>{{ props.err.getError() }}</div>
        <div v-else></div>
    </div>
</template>