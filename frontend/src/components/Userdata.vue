<script setup>
    import { onBeforeMount, ref } from 'vue'
    import axios from 'axios'
    import PermissionCheck from './PermissionCheck.vue'
    import Input from './Input.vue'
    import Error from './Error.vue'
    import Errors from '../util/Errors.js'
    import Param from '../util/FormParameter'
    import * as validate from '../util/InputValidator.js'
    import Config from '../config.json'

    const props = defineProps(["user", "mode", "privileges", "invoker"])
    const emit = defineEmits(["login", "updated", "deleted", "signedup", "returned"])

    const MODE_OBSERVE = 0
    const MODE_STRING_OBSERVE = "observe"
    const MODE_READ = 1
    const MODE_STRING_READ = "read"
    const MODE_EDIT_OWN = 2
    const MODE_STRING_EDIT_OWN = "edit"
    const MODE_EDIT_OTHER = 3
    const MODE_STRING_EDIT_OTHER = "editother"
    const MODE_SIGNUP = 4
    const MODE_STRING_SIGNUP = "signup"

    const PRIVILEGES_PENDING = "pending"
    const PRIVILEGES_USER = "user"
    const PRIVILEGES_SUPER = "superuser"
    const PRIVILEGES_ADMIN = "admin"

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

    const privileges = ref(PRIVILEGES_USER)

    const password = ref(new Param('', () => (mode.value !== MODE_SIGNUP && password.value.value === '') || validate.password(password.value.value)))
    const passwordR = ref(new Param('', () => (mode.value !== MODE_SIGNUP && password.value.value === '') || !password.value.isValid || password.value.value === passwordR.value.value))
    const consent = ref(false)
    const consentPresent = ref(true)

    const requestErrorConflict = ref(false)
    const requestErrorServer = ref(false)
    const requestErrorUnreachable = ref(false)

    const mode = ref(MODE_READ)

    const isLoading = ref(false)
    const isEraseCheck = ref(false)
    const isErasing = ref(false)
    const isErased = ref(false)

    let groupchanged = false;

    onBeforeMount(() => {
        if(props.user !== undefined){
            resetUser()
        }

        if(props.mode !== undefined){
            switch(props.mode){
                case MODE_STRING_OBSERVE:
                    mode.value = MODE_OBSERVE
                    break
                case MODE_STRING_READ:
                    mode.value = MODE_READ
                    break
                case MODE_STRING_EDIT_OWN:
                    mode.value = MODE_EDIT_OWN
                    break
                case MODE_STRING_EDIT_OTHER:
                    mode.value = MODE_EDIT_OTHER
                    break
                case MODE_STRING_SIGNUP:
                    mode.value = MODE_SIGNUP
            }
        }
    });

    function resetUser(){
        email.value.setAndCheck(props.user.email)
        name.value.setAndCheck(props.user.name)
        zip.value.setAndCheck(props.user.zip)
        place.value.setAndCheck(props.user.place)
        phone.value.setAndCheck(props.user.phone)

        privileges.value = props.user.usergroup.name

        password.value.setAndCheck('')
        passwordR.value.setAndCheck('')
    }

    function signup(){
        if(checkAll()){
            consentPresent.value = consent.value
            if(consent.value)
                sendUpdateRequest()
        }
    }
    function update(){
        if(checkAll())
            sendUpdateRequest()
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
    function validInput(){
        return (email.value.isValid &&
        name.value.isValid && 
        zip.value.isValid && 
        place.value.isValid && 
        phone.value.isValid && 
        password.value.isValid && 
        passwordR.value.isValid)
    }
    function sendUpdateRequest(){
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

        if(mode.value === MODE_EDIT_OWN || mode.value === MODE_EDIT_OTHER){
            if(mode.value === MODE_EDIT_OWN){
                params.append('session', props.user.session)
            }else if(mode.value === MODE_EDIT_OTHER){
                params.append('session', props.invoker)
                params.append('target', props.user.email)
            }

            if(privileges.value != props.user.usergroup.name){
                params.append('group', privileges.value)
                groupchanged = true;
            }
        }

        isLoading.value = true
        resetErrors()

        axios({
            method: mode.value === MODE_SIGNUP ? 'post' : 'put',
            url: 'http://' + Config.backendAddress + '/user',
            data: params})
            .then(handlePutResponse)
            .catch(handlePutError)
    }
    function handlePutResponse(){

        if(mode.value === MODE_EDIT_OWN){
            if(groupchanged){
                const params = new URLSearchParams()
                params.append('group', privileges.value)
                axios.get('http://' + Config.backendAddress + '/usergroup', { params })
                    .then(response => {
                        isLoading.value = false
                        emitUpdated(response.data)
                        mode.value = MODE_READ
                    })
                    .catch((error) => {
                        handleError(error)
                        isLoading.value = false
                    })
                groupchanged = false;
            }else{
                
                isLoading.value = false
                emitUpdated(props.user.usergroup)
                mode.value = MODE_READ
            }
        }
        else if(mode.value === MODE_EDIT_OTHER){

            isLoading.value = false
            emitUpdated({
                name: privileges.value
            })
            mode.value = MODE_OBSERVE
        }else{
            emit("signedup")
        }  
    }
    function handlePutError(error){
        isLoading.value = false

        handleError(error)
    }
    function handleError(error){
        if(error.response != undefined){
            if(error.response.status === 409)
                requestErrorConflict.value = true
            else
                requestErrorServer.value = true
        }else{
            requestErrorUnreachable.value = true
        }
    }

    function emitUpdated(usergroup){
        emit("updated", {
            initialEmail: props.user.email,
            email: email.value.value,
            name: name.value.value,
            zip: zip.value.value,
            place: place.value.value,
            phone: phone.value.value,
            usergroup: usergroup
        })
    }

    function resetErrors(){
        requestErrorConflict.value = false
        requestErrorServer.value = false
        requestErrorUnreachable.value = false
    }

    function checkErase(){
        isEraseCheck.value = true
    }

    function cancelErase(){
        isEraseCheck.value = false
    }

    function erase(){
        const params = new URLSearchParams()
        params.append('session', props.invoker)
        params.append('target', props.user.email)

        isEraseCheck.value = false
        isErasing.value = true

        resetErrors()

        axios({
            method: 'delete',
            url: 'http://' + Config.backendAddress + '/user',
            data: params})
            .then(handleDeleteResponse)
            .catch(handleDeleteError)
    }
    function handleDeleteResponse(response){
        isErasing.value = false
        isErased.value = true

        emit("deleted", props.user.email)
    }
    function handleDeleteError(error){
        isErasing.value = false

        handleError(error)
    }

    function edit(){
        if(mode.value === MODE_READ)
            mode.value = MODE_EDIT_OWN
        else if(mode.value === MODE_OBSERVE)
            mode.value = MODE_EDIT_OTHER
    }
    function cancelUpdate(){
        resetUser()
        if(mode.value === MODE_EDIT_OWN)
            mode.value = MODE_READ
        else if(mode.value === MODE_EDIT_OTHER)
            mode.value = MODE_OBSERVE
    }

    function getType(inputType){
        if(inputType === undefined)inputType = ''
        if(shouldBeDisabled())inputType = inputType + 'disabled'
        return inputType
    }
    function shouldBeDisabled(){
        return isLoading.value || isEraseCheck.value || mode.value <= MODE_READ;
    }

    function validUpdate(){
        return !(
            props.user.email === email.value.value &&
            props.user.name === name.value.value &&
            props.user.zip === zip.value.value &&
            props.user.place === place.value.value &&
            props.user.phone === phone.value.value &&
            props.user.usergroup.name === privileges.value && 
            password.value.value === '' && 
            passwordR.value.value === ''
        ) && validInput()
    }

    function hasEditPriv(){
        if(mode.value === MODE_READ)
            return props.privileges.editOwnCred
        else if(mode.value === MODE_OBSERVE)
            return props.privileges.editOthCred
        return false
    }
    function hasEditPassPriv(){
        if(mode.value === MODE_SIGNUP)
            return true;
        else if(mode.value === MODE_EDIT_OWN)
            return props.privileges.editOwnPass;
        else if(mode.value === MODE_EDIT_OTHER)
            return props.privileges.editOthPass;
        return false;
    }
    function hasEditPrivPriv(){
        if(mode.value === MODE_EDIT_OWN)
            return props.privileges.editOwnPriv;
        else if(mode.value === MODE_EDIT_OTHER)
            return props.privileges.editOthPriv;
        return false;
    }
    function hasDeletePriv(){
        if(mode.value === MODE_EDIT_OTHER)
            return props.privileges.privDelete;
        return false;
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
    <PermissionCheck v-if="isEraseCheck || isErasing" :progressing="isErasing" @cancel="cancelErase" @accept="erase">Wollen Sie den gewählten Datensatz wirklich löschen?</PermissionCheck>
    <div v-else-if="isErased" class="content form">
        Der Datensatz wurde erfolgreich gelöscht.
        <button class='form-button' @click="$emit('returned')">&#60</button>
    </div>
    <div v-else class='content form'>
        <div v-if='mode <= MODE_EDIT_OTHER' class='userdata-heading'>
            <h1>{{ (mode === MODE_OBSERVE || mode === MODE_EDIT_OTHER) ? name.value : "My Data"}}</h1>
            <button v-if='hasEditPriv()' class='edit' @click='edit'>&#9998</button>
            <button v-else-if='mode == MODE_EDIT_OTHER || mode == MODE_EDIT_OWN && !isLoading' class='edit' @click='cancelUpdate'>&#10006</button>
        </div>

        <Input label='E-Mail' :invalid='!email.isValid' v-model='email.value' :type='getType("email")' @focusout='checkEmail'/>
        <Input label='Name' :invalid='!name.isValid' v-model='name.value' :type='getType("email")' @focusout='checkName'/>
        <Input label='PLZ' :invalid='!zip.isValid' v-model='zip.value' :type='getType("number")' @focusout='checkZip'/>
        <Input label='Ort' :invalid='!place.isValid' v-model='place.value' :type='getType()' @focusout='checkPlace'/>
        <Input label='Telefon' :invalid='!phone.isValid' v-model='phone.value' :type='getType("tel")' @focusout='checkPhone'/>

        <div v-if='hasEditPrivPriv()' class = "input">
            <label class="input-label">Rechte</label>
            <select class="input-select" v-model="privileges" :disabled="shouldBeDisabled()">
                <option disabled :value="PRIVILEGES_PENDING">None</option>
                <option :value="PRIVILEGES_USER">User</option>
                <option :value="PRIVILEGES_SUPER">Superuser</option>
                <option :value="PRIVILEGES_ADMIN">Admin</option>
            </select>
        </div>

        <template v-if='hasEditPassPriv()'>
            <Input class='password' label='Passwort' :invalid='!password.isValid' v-model='password.value' :type='getType("password")' @focusout='checkPassword'/>
            <Input label='Wiederholen' :invalid='!passwordR.isValid' v-model='passwordR.value' :type='getType("password")' @focusout='checkPasswordR'/>
        </template>

        <div v-if='mode === MODE_SIGNUP' class='consent'>
            <input type='checkbox' v-model='consent'/>
            Ich stimme den <strong>Nutzungsbedingungen</strong> zu und habe die <strong>Datenschutzerklärung</strong> gelesen.
        </div>
        
        <Error v-if='mode >= MODE_EDIT_OWN' :err="ERR"/>
        
        <button v-if='mode === MODE_OBSERVE' class='form-button' @click="$emit('returned')">&#60</button>

        <template v-else-if='!isLoading'>
            <button v-if='mode === MODE_SIGNUP' class='form-button' @click='signup'>Registrieren</button>
            <div v-else-if='mode >= MODE_EDIT_OWN' class='button-row'>
                <button v-if='mode == MODE_EDIT_OTHER' class='form-button' @click="$emit('returned')">&#60</button>
                <button class='form-button' :disabled="!validUpdate()" @click='update'>Update</button>
                <button v-if='hasDeletePriv()' class='form-button delete' @click='checkErase'>Löschen</button>
            </div>
        </template>

        <div v-else class='loader'></div>

        <div v-if="mode === MODE_SIGNUP">
            Sie haben bereits ein Konto? Zum anmelden <a @click='$emit("login")'>hier</a> klicken
        </div>
    </div>

</template>