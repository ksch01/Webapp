<script setup>
import Userdata from '../Userdata.vue'

defineEmits(["login"])

</script>

<template>
    <div class='section-side'>
        <h1>Signup</h1>
    </div>
    <div class='section-content'>
        <Userdata mode='signup' @login="$emit('login')"/>
    </div>
</template>