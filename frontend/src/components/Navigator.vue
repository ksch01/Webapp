<script setup>
const props = defineProps(["current", "max"])
defineEmits(["navigate"])
</script>

<template>
    <div class="navigator">
        <button v-if="current > 3" class='edit' @click="$emit('navigate', 1)">1</button>
        <template v-if="current > 4">...</template>
        <button v-if="current > 2" class='edit' @click="$emit('navigate', props.current - 2)">{{ props.current - 2 }}</button>
        <button v-if="current > 1" class='edit' @click="$emit('navigate', props.current - 1)">{{ props.current - 1 }}</button>
        <button class='edit nav'>{{ props.current }}</button>
        <button v-if="current < max" class='edit' @click="$emit('navigate', props.current + 1)">{{ props.current + 1 }}</button>
        <button v-if="(current + 1) < max" class='edit' @click="$emit('navigate', props.current + 2)">{{ props.current + 2 }}</button>
        <template v-if="(current + 3) < max">...</template>
        <button v-if="(current + 2) < max" class='edit' @click="$emit('navigate', props.max)">{{ props.max }}</button>
    </div>
</template>