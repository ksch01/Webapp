<script setup>
import { ref } from 'vue'
import Input from '../Input.vue'
import axios from 'axios'
import Userlist from './Userlist.vue'
import Error from '../Error.vue'
import Data from '../../util/Data.js'
import Errors from '../../util/Errors.js'

const props = defineProps([
    "email",
    "id",
    "privileges"
])
const emit = defineEmits([
    "updatedOther",
    "deleted"
])

const ERR = new Errors()
ERR.addError("Für Ihre Suche wurden keine Ergebnisse gefunden.",
() => requestErrorEmpty.value)
ERR.addError("Beim Bearbeiten Ihrer Anfrage ist ein Fehler aufgetreten. Versuchen Sie es später erneut.",
() => requestErrorServer.value)
ERR.addError("Der Server konnte nicht erreicht werden. Stellen Sie sicher, dass Sie mit dem Internet verbunden sind und versuchen Sie es erneut.",
() => requestErrorUnreachable.value)

const MODE_ONE_LINE = true

const values = ref({
    email: "",
    name: "",
    zip: "",
    place: "",
    phone: ""
})

const requestErrorEmpty = ref(false)
const requestErrorServer = ref(false)
const requestErrorUnreachable = ref(false)

const criteria = ref("email")
const mode = ref(MODE_ONE_LINE)

const data = ref(new Data())
const displayData = ref(false)
const page = ref(1)

const isLoading = ref(false)

function toggleMode(){
    mode.value = !mode.value
}

function search(){
    isLoading.value = true
    requestErrorEmpty.value = false
    requestErrorServer.value = false
    requestErrorUnreachable.value = false

    let search = values.value;
    if(mode.value == MODE_ONE_LINE){
        search = {}
        search[criteria.value] = values.value[criteria.value]
    }

    const params = new URLSearchParams()
    for(const [key, value] of Object.entries(search)){
        params.append(key, value)
    }

    axios.get('http://localhost/index.php/account', { params } )
        .then(handleResponse)
        .catch(handleError)
}
function handleResponse(response){
    isLoading.value = false

    updateData(response.data)
    if(response.data.length !== 0)
        displayData.value = true
    else
        requestErrorEmpty.value = true
}
function handleError(error){
    isLoading.value = false

    if(error.response != undefined){
        requestErrorServer.value = true
    }else{
        requestErrorUnreachable.value = true
    }

    displayData.value = false;
}

function reload(){
    search();
}

function updateData(newData){
    data.value.update(newData)
}
function updatedOther(updatedUser){
    data.value.updateUser(updatedUser)
    emit("updatedOther", updatedUser)
}
function deleted(deletedUserEmail){
    data.value.delete(deletedUserEmail)
    emit("deleted", deletedUserEmail)
}
function sort(attribute){
    data.value.sort(attribute)
}
function reverse(){
    data.value.reverse()
}
function back(){
    data.value.empty()
    displayData.value = false
}

function setPage(newPage){
    page.value = newPage
}

function getType(inputType){
    if(inputType === undefined)inputType = ''
    if(shouldBeDisabled())inputType = inputType + 'disabled'
    return inputType
}
function shouldBeDisabled(){
    return isLoading.value
}
</script>

<template>
    <Userlist v-if="displayData" :data="data" :page="page" @page="setPage" :returnable="true" :email='props.email' :id='props.id' :privileges='props.privileges' :isLoading="isLoading" @return="back" @selectedself="$emit('selectedself')" @sort="sort" @reverse="reverse" @updated="updatedOther" @deleted="deleted" @reload="reload"/>
    <div v-else class="content form">
        <div v-if="mode" class="input">
            <select class="input-dyn-label" v-model="criteria">
                <option value="email">E-Mail</option>
                <option value="name">Name</option>
                <option value="zip">PLZ</option>
                <option value="place">Ort</option>
                <option value="phone">Telefon</option>
            </select>
            <input class="input-input" v-model='values[criteria]' :disabled='isLoading'/>
        </div>
        <template v-else>
            <Input label='E-Mail' v-model='values.email' :type='getType()'/>
            <Input label='Name' v-model='values.name' :type='getType()'/>
            <Input label='PLZ' v-model='values.zip' :type='getType()'/>
            <Input label='Ort' v-model='values.place' :type='getType()'/>
            <Input label='Telefon' v-model='values.phone' :type='getType()'/>
        </template>
        <Error :err="ERR"/>
        <div v-if="!isLoading" class="button-row">
            <button class="form-button" @click="toggleMode">{{ mode ? "Mehr" : "Weniger"}}</button>
            <button class="form-button" @click="search">Suchen</button>
        </div>
        <div v-else class='loader'></div>
    </div>
</template>