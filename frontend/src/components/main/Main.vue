<script setup>
import { ref, onBeforeMount } from 'vue'
import axios from 'axios'
import Userdata from '../Userdata.vue'
import Userlist from './Userlist.vue'
import Usersearch from './Usersearch.vue'

const props = defineProps(['user'])
defineEmits(['loggedout', 'updated'])

const SCREEN_WELCOME = 0
const SCREEN_MYDATA = 1
const SCREEN_LISTPERSONS = 2
const SCREEN_SEARCH = 3

const contentScreen = ref(SCREEN_WELCOME)

const user = ref(props.user)

const data = ref()

const emptyUser = {email: "", name: "", zip: "", place: "", phone: ""}

onBeforeMount(() => {
    data.value = [emptyUser]
    loadData()
})
function loadData(){
    axios.get('http://localhost/index.php/account')
        .then(handleReceiveData)
        .catch(handleError)
}
function handleReceiveData(response){
    data.value = response.data
}
function handleError(error){
    console.log(error)
}

function updated(updatedUser){
    let id = user.value.id
    user.value = updatedUser
    user.value.id = id

    updatedOther(updatedUser)
}

function updatedOther(updatedUser){
    let user = data.value.find(user => user.email === updatedUser.email)
    user.email = updatedUser.email
    user.name = updatedUser.name
    user.zip = updatedUser.zip
    user.place = updatedUser.place
    user.phone = updatedUser.phone
    user.privileges = updatedUser.privileges
}

function sort(compare){
    data.value = data.value.sort(compare)
}

function reverse(){
    data.value = data.value.reverse()
}

function setScreen(screen){
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
        <div :class='getSelectorClass(SCREEN_MYDATA)' @click='setScreen(SCREEN_MYDATA)'>
            <label>My Data</label>
        </div>
        <div :class='getSelectorClass(SCREEN_LISTPERSONS)' @click='setScreen(SCREEN_LISTPERSONS)'>
            <label>List Persons</label>
        </div>
        <div :class='getSelectorClass(SCREEN_SEARCH)' @click='setScreen(SCREEN_SEARCH)'>
            <label>Search</label>
        </div>
        <div class='selector' @click='$emit("loggedout")'>
            <label>Logout</label>
        </div>
    </div>
    <div class='section-content'>
        <div class='content welcome' v-if='contentScreen === SCREEN_WELCOME'>Willkommen {{ user.name }}!</div>
        <Userdata v-else-if='contentScreen === SCREEN_MYDATA' :user='user' :privileges='user.privileges' @updated="updated"/>
        <Userlist v-else-if='contentScreen === SCREEN_LISTPERSONS' :data='data' :email='user.email' :id='user.id' :privileges='user.privileges' @selectedself="setScreen(SCREEN_MYDATA)" @sort="sort" @reverse="reverse" @updated="updatedOther"/>
        <Usersearch v-else-if='contentScreen === SCREEN_SEARCH'/>
    </div>
</template>