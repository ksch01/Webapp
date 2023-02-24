<script setup>
import { ref, onBeforeMount } from 'vue'
import axios from 'axios'
import Userdata from '../Userdata.vue'
import Userlist from './Userlist.vue'
import Usersearch from './Usersearch.vue'
import PermissionCheck from '../PermissionCheck.vue'
import Data from '../../util/Data.js'
import Errors from '../../util/Errors.js'

const props = defineProps(['user'])
defineEmits(['loggedout', 'updated'])

const ERR = new Errors()
ERR.addError("Die Daten konnte aufgrund eines Server Fehlers nicht abgerufen werden.",
() => requestErrorServer.value)
ERR.addError("Der Server konnte nicht erreicht werden.",
() => requestErrorUnreachable.value)

const SCREEN_WELCOME = 0
const SCREEN_MYDATA = 1
const SCREEN_LISTPERSONS = 2
const SCREEN_SEARCH = 3
const SCREEN_LOGIN = 4

const contentScreen = ref(SCREEN_WELCOME)
let lastScreen

const user = ref(props.user)

const data = ref(new Data())
const page = ref(1)

const isLoading = ref(false)
const requestErrorServer = ref(false)
const requestErrorUnreachable = ref(false)

onBeforeMount(() => {
    loadData()
})
function loadData(){
    isLoading.value = true;
    requestErrorServer.value = false;
    requestErrorUnreachable.value = false;

    axios.get('http://localhost/index.php/account')
        .then(handleReceiveData)
        .catch(handleError)
}
function handleReceiveData(response){
    updateData(response.data)
    isLoading.value = false;
}
function handleError(error){
    if(error.response != undefined){
        requestErrorServer.value = true
    }else{
        requestErrorUnreachable.value = true
    }
    
    isLoading.value = false;
}

function reload(){
    loadData();
}

function updated(updatedUser){
    let id = user.value.id
    user.value = updatedUser
    user.value.id = id

    updatedOther(updatedUser)
}

function updateData(newData){
    data.value.update(newData)
}
function updatedOther(updatedUser){
    data.value.updateUser(updatedUser)
}
function deleted(deletedUserEmail){
    data.value.delete(deletedUserEmail)
}
function sort(attribute){
    data.value.sort(attribute)
}
function reverse(){
    data.value.reverse();
}

function setPage(newPage){
    page.value = newPage;
}

function cancelLogout(){
    contentScreen.value = lastScreen
}

function setScreen(screen){
    lastScreen = contentScreen.value
    contentScreen.value = screen
}

function getSelectorClass(screen){
    let styleClass = 'selector'
    if(contentScreen.value === screen)styleClass += " selected"
    return styleClass
}
</script>

<template>
    <div class='section-side'>
        <h1>WebApp</h1>
        <div class="selectors">
            <div :class='getSelectorClass(SCREEN_MYDATA)' @click='setScreen(SCREEN_MYDATA)'>
                <label>My Data</label>
            </div>
            <div :class='getSelectorClass(SCREEN_LISTPERSONS)' @click='setScreen(SCREEN_LISTPERSONS)'>
                <label>List Persons</label>
            </div>
            <div :class='getSelectorClass(SCREEN_SEARCH)' @click='setScreen(SCREEN_SEARCH)'>
                <label>Search</label>
            </div>
            <div :class='getSelectorClass(SCREEN_LOGIN)' @click='setScreen(SCREEN_LOGIN)'>
                <label>Logout</label>
            </div>
        </div>
    </div>
    <div class='section-content'>
        <div class='content welcome' v-if='contentScreen === SCREEN_WELCOME'>Willkommen {{ user.name }}!</div>
        <Userdata v-else-if='contentScreen === SCREEN_MYDATA' :user='user' :privileges='user.privileges' @updated="updated"/>
        <Userlist v-else-if='contentScreen === SCREEN_LISTPERSONS' :data='data' :page='page' :email='user.email' :id='user.id' :privileges='user.privileges' :isLoading="isLoading" :err="ERR" @selectedself="setScreen(SCREEN_MYDATA)" @sort="sort" @reverse="reverse" @page="setPage" @updated="updatedOther" @deleted="deleted" @reload="reload"/>
        <Usersearch v-else-if='contentScreen === SCREEN_SEARCH' :email='user.email' :id='user.id' :privileges='user.privileges' @selectedself="setScreen(SCREEN_MYDATA)" @updatedOther="updatedOther" @deleted="deleted"/>
        <PermissionCheck v-else @cancel="cancelLogout" @accept="$emit('loggedout')">Wollen Sie sich wirklich ausloggen?</PermissionCheck>
    </div>
</template>