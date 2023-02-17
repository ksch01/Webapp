<script setup>

defineEmits(["cancel", "accept"])

</script>

<template>
    <div class="content form">
        <slot />
        <div class="button-row">
            <button @click="$emit('cancel')">Abbrechen</button>
            <button @click="$emit('accept')">Bestätigen</button>
        </div>
    </div>
</template>