<script setup>
import { ref } from 'vue'
import axios from 'axios'
import Input from '../Input.vue'
import Error from '../Error.vue'
import Errors from '../../util/Errors.js'
import Param from '../../util/FormParameter.js'
import * as validate from '../../util/InputValidator.js'
import Config from '../../config.json'

const emit = defineEmits(['signup', 'loggedin'])

const ERR = new Errors()

ERR.addError(
    "Bitte geben Sie eine gültige Email und ein gültiges Passwort ein.",
    () => {return !email.value.isValid || !password.value.isValid})
ERR.addError(
    "Die von Ihnen angegebene Email oder das Passwort ist nicht korrekt.",
    () => {return requestErrorData.value})
ERR.addError(
    "Ihre Regestrierung wurde noch nicht abgeschlossen. Um die Regestrierung abzuschließen folgen Sie dem Link in Ihrer E-Mail.",
    () => {return requestErrorUnauthorized.value})
ERR.addError(
    "Beim Bearbeiten Ihrer Anfrage ist ein Fehler aufgetreten. Versuchen Sie es später erneut.",
    () => {return requestErrorServer.value})
ERR.addError(
    "Der Server konnte nicht erreicht werden. Stellen Sie sicher, dass Sie mit dem Internet verbunden sind und versuchen Sie es erneut.",
    () => {return requestErrorUnreachable.value})

const email = ref(new Param('', validate.email))
const password = ref(new Param('', validate.password))

const requestErrorData = ref(false)
const requestErrorUnauthorized = ref(false)
const requestErrorServer = ref(false)
const requestErrorUnreachable = ref(false)

const isLoading = ref(false)

function tryLogin(){

    email.value.check()
    password.value.check()

    if(email.value.isValid && password.value.isValid){

        const params = new URLSearchParams()
        params.append('email',email.value.value)
        params.append('password',password.value.value)

        password.value.reset()

        isLoading.value = true
        requestErrorData.value = false
        requestErrorUnauthorized.value = false
        requestErrorServer.value = false
        requestErrorUnreachable.value = false

        axios.post('http://' + Config.backendAddress + '/index.php/login', params)
            .then(handleResponse)
            .catch(handleError)   
    }
}
function handleResponse(response){
    emit('loggedin', response.data)

    isLoading.value = false
}
function handleError(error){
    if(error.response != undefined){
        if(error.response.status === 401)
            requestErrorData.value = true
        else if(error.response.status === 403)
            requestErrorUnauthorized.value = true
        else if(error.response.status === 500)
            requestErrorServer.value = true
    }else{
        requestErrorUnreachable.value = true
    }

    isLoading.value = false
}

function getType(inputType){
    if(inputType === undefined)inputType = ''
    return isLoading.value ? 'disabled' : inputType
}

function checkEmail(){email.value.check()}
function checkPassword(){password.value.check()}

</script>

<template>
    <div class='section-side'>
        <h1>Login</h1>
    </div>
    <div class='section-content'>
        <div class='content form'>
            <Input label='E-mail' :invalid='!email.isValid' v-model='email.value' :type='getType()' @focusout='checkEmail'/>
            <Input label='Password' :invalid='!password.isValid' v-model='password.value' :type='getType("password")' @focusout='checkPassword'/>
            
            <Error :err="ERR"/>
                        
            <button v-if='!isLoading' class='form-button' @click="tryLogin">Login</button>
            <div v-else class='loader'></div>
            
            <div>
                Noch kein Konto? Zum regestrieren <a @click='$emit("signup")'>hier</a> klicken.
            </div>
        </div>
    </div>
</template>