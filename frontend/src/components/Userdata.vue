<script setup>
    import { onBeforeMount, ref } from 'vue'
    import Input from './Input.vue'
    import Errors from '../util/Errors.js'
    import Param from '../util/FormParameter';
    import * as validate from '../util/InputValidator.js'
    import axios from 'axios';

    const props = defineProps(["user", "mode"])
    const emit = defineEmits(["updated"])

    const MODE_READ = "read"
    const MODE_OBSERVE = "observe"
    const MODE_EDIT = "edit"
    const MODE_SIGNUP = "signup"

    const ERR = new Errors()
    ERR.addError("Bitte prüfen Sie die Eingaben in den markierten Feldern.",
    () => !email.value.isValid || !name.value.isValid || !zip.value.isValid || !place.value.isValid || !phone.value.isValid)
    ERR.addError("Passwörter müssen mindestens acht zeichen lang sein.",
    () => !password.value.isValid)
    ERR.addError("Die von Ihnen angegebenen Passwörter stimmen nicht überein.",
    () => !passwordR.value.isValid)
    ERR.addError("Sie müssen den Nutzungsbedingungen und der Datenschutzerklärung zustimmen um fortzufahren.",
    () => !consentPresent.value && !consent.value)
    ERR.addError("Es existiert bereits ein Konto mit der von Ihnen angegebenen Email.",
    () => requestErrorConflict.value)
    ERR.addError("Beim Bearbeiten Ihrer Anfrage ist ein Fehler aufgetreten. Versuchen Sie es später erneut.",
    () => requestErrorServer.value)
    ERR.addError("Der Server konnte nicht erreicht werden. Stellen Sie sicher, dass Sie mit dem Internet verbunden sind und versuchen Sie es erneut.",
    () => requestErrorUnreachable.value)

    const email = ref(new Param('', validate.email))
    const name = ref(new Param('', validate.name))
    const zip = ref(new Param('', validate.zip))
    const place = ref(new Param('', validate.place))
    const phone = ref(new Param('', validate.phone))

    const password = ref(new Param('', () => (mode.value !== MODE_SIGNUP && password.value.value === '') || validate.password(password.value.value)))
    const passwordR = ref(new Param('', () => (mode.value !== MODE_SIGNUP && passwordR.value.value === '') || !password.value.isValid || password.value.value === passwordR.value.value))

    const consent = ref(false)
    const consentPresent = ref(true)

    const requestErrorConflict = ref(false)
    const requestErrorServer = ref(false)
    const requestErrorUnreachable = ref(false)

    const mode = ref("read")

    const isLoading = ref(false)

    onBeforeMount(() => {
        if(props.user !== undefined){
            resetUser()
        }

        if(props.mode !== undefined){
            mode.value = props.mode
        }
    });

    function resetUser(){
        email.value.setAndCheck(props.user.email)
        name.value.setAndCheck(props.user.name)
        zip.value.setAndCheck(props.user.zip)
        place.value.setAndCheck(props.user.place)
        phone.value.setAndCheck(props.user.phone)

        password.value.setAndCheck('')
        passwordR.value.setAndCheck('')
    }

    function signup(){
        if(checkAll()){
            consentPresent.value = consent.value
            if(consent.value)
                sendRequest()
        }
    }
    function update(){
        if(checkAll()){
            sendRequest()
        }
    }
    function checkAll(){
        return email.value.check() &
        name.value.check() &
        zip.value.check() &
        place.value.check() &
        phone.value.check() &
        password.value.check() &
        passwordR.value.check()
    }
    function sendRequest(){
        const params = new URLSearchParams()
        if(mode.value === MODE_SIGNUP || email.value.value != props.user.email)
            params.append('email', email.value.value)
        if(mode.value === MODE_SIGNUP || name.value.value != props.user.name)
            params.append('name', name.value.value)
        if(mode.value === MODE_SIGNUP || zip.value.value != props.user.zip)
            params.append('zip', zip.value.value)
        if(mode.value === MODE_SIGNUP || place.value.value != props.user.place)
            params.append('place', place.value.value)
        if(mode.value === MODE_SIGNUP || phone.value.value != props.user.phone)
            params.append('phone', phone.value.value)
        if(password.value.value != '')
            params.append('password', password.value.value)
        if(mode.value === MODE_EDIT)
            params.append('id', props.user.id)

        isLoading.value = true
        requestErrorConflict.value = false
        requestErrorServer.value = false
        requestErrorUnreachable.value = false

        axios({
            method: mode.value === MODE_SIGNUP ? 'post' : 'put',
            url: 'http://localhost/index.php/account',
            data: params})
            .then(handleResponse)
            .catch(handleError)
    }
    function handleResponse(response){
        isLoading.value = false

        if(mode.value === MODE_EDIT){
            emit("updated", {
                email: email.value.value,
                name: name.value.value,
                zip: zip.value.value,
                place: place.value.value,
                phone: phone.value.value
            })
            mode.value = MODE_READ
        }
    }
    function handleError(error){
        isLoading.value = false

        if(error.response != undefined){
            if(error.response.status === 409)
                requestErrorConflict.value = true
                
            if(error.response.status === 500)
                requestErrorServer.value = true
        }else{
            requestErrorUnreachable.value = true
        }
    }

    function edit(){
        mode.value = MODE_EDIT
    }
    function cancel(){
        resetUser()
        mode.value = MODE_READ
    }

    function getType(inputType){
        if(inputType === undefined)inputType = ''
        if(isLoading.value)inputType = inputType + 'disabled'
        return mode.value === MODE_READ || mode.value === MODE_OBSERVE ? 'disabled' : inputType
    }

    function showPasswordEdit(){
        return mode.value === MODE_SIGNUP || mode.value === MODE_EDIT
    }

    function changesMade(){
        return !(
            props.user.email === email.value.value &&
            props.user.name === name.value.value &&
            props.user.zip === zip.value.value &&
            props.user.place === place.value.value &&
            props.user.phone === phone.value.value &&
            password.value.value === '' && 
            passwordR.value.value === ''
        )
    }

    function checkEmail(){email.value.check()}
    function checkName(){name.value.check()}
    function checkZip(){zip.value.check()}
    function checkPlace(){place.value.check()}
    function checkPhone(){phone.value.check()}
    function checkPassword(){password.value.check()}
    function checkPasswordR(){passwordR.value.check()}
</script>

<template>
    <div class='content form'>
        <div v-if='mode === MODE_READ || mode === MODE_EDIT || mode === MODE_OBSERVE' class='userdata-heading'>
            <h1 v-if='mode === MODE_READ || mode === MODE_EDIT'>My Data</h1>
            <button v-if='mode === MODE_READ' class='edit' @click='edit'>&#9998</button>
            <button v-if='mode === MODE_EDIT && !isLoading' class='edit' @click='cancel'>&#10006</button>
        </div>
        <Input label='E-Mail' :invalid='!email.isValid' v-model='email.value' :type='getType()' @focusout='checkEmail'/>
        <Input label='Name' :invalid='!name.isValid' v-model='name.value' :type='getType()' @focusout='checkName'/>
        <Input label='PLZ' :invalid='!zip.isValid' v-model='zip.value' :type='getType()' @focusout='checkZip'/>
        <Input label='Ort' :invalid='!place.isValid' v-model='place.value' :type='getType()' @focusout='checkPlace'/>
        <Input label='Telefon' :invalid='!phone.isValid' v-model='phone.value' :type='getType()' @focusout='checkPhone'/>
        <Input v-if='showPasswordEdit()' class='password' label='Passwort' :invalid='!password.isValid' v-model='password.value' :type='getType("password")' @focusout='checkPassword'/>
        <Input v-if='showPasswordEdit()' label='Wiederholen' :invalid='!passwordR.isValid' v-model='passwordR.value' :type='getType("password")' @focusout='checkPasswordR'/>
        <div v-if='mode === MODE_SIGNUP' class='consent'>
            <input type='checkbox' v-model='consent'/>
            Ich stimme den <strong>Nutzungsbedingungen</strong> zu und habe die <strong>Datenschutzerklärung</strong> gelesen.
        </div>
        <div v-if='ERR.getError() !== false' class='input-message'>{{ ERR.getError() }}</div>
        <template v-if='!isLoading'>
            <button v-if='mode === MODE_SIGNUP' class='form-button' @click='signup'>Registrieren</button>
            <button v-if='mode === MODE_EDIT' class='form-button' :disabled="!changesMade()" @click='update'>Update</button>
        </template>
        <div v-else class='loader'></div>
    </div>
</template>