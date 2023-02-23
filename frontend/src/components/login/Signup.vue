<script setup>
import { ref } from 'vue'
import Userdata from '../Userdata.vue'

defineEmits(["login"])

const displayForm = ref(true);

function signedup(){
    displayForm.value = false;
}

</script>

<template>
    <div class='section-side'>
        <h1>Signup</h1>
    </div>
    <div class='section-content'>
        <Userdata v-if='displayForm' mode='signup' @login="$emit('login')" @signedup="signedup"/>
        <div v-else class='content form'>
            <div>
                Vielen Dank für Ihre Anmeldung.
            </div>
            <div>
                Zum Abschließen Ihrer Regestrierung folgen Sie bitte dem Link in der E-Mail, die wir Ihnen gesendet haben.
            </div>
            <div>
                Zum login <a @click='$emit("login")'>hier</a> klicken.
            </div>
        </div>
    </div>
</template>