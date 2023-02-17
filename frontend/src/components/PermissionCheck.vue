<script setup>

defineProps(["progressing"])
defineEmits(["cancel", "accept"])

</script>

<template>
    <div class="content form">
        <slot />
        <div class="button-row">
            <div v-if="progressing" class="loader"></div>
            <template v-else>
                <button @click="$emit('cancel')">Abbrechen</button>
                <button @click="$emit('accept')">Bestätigen</button>
            </template>
        </div>
    </div>
</template>