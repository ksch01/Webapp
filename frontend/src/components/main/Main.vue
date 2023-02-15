<script setup>
import { ref, onBeforeMount } from 'vue'
import axios from 'axios'
import Userdata from '../Userdata.vue'
import Userlist from './Userlist.vue'

const props = defineProps(['test'])
defineEmits(['loggedout', 'updated'])

const SCREEN_WELCOME = 0
const SCREEN_MYDATA = 1
const SCREEN_LISTPERSONS = 2
const SCREEN_SEARCH = 3

const contentScreen = ref(SCREEN_WELCOME)

const user = ref(props.test)

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
    
}

function updated(updatedUser){
    let id = user.value.id
    user.value = updatedUser
    user.value.id = id
}

function setScreen(screen){
    contentScreen.value = screen
}

</script>

<template>
    <div class='section-side'>
        <h1>WebApp</h1>
        <div class='selector' @click='setScreen(SCREEN_MYDATA)'>
            <label>My Data</label>
        </div>
        <div class='selector' @click='setScreen(SCREEN_LISTPERSONS)'>
            <label>List Persons</label>
        </div>
        <div class='selector' @click='setScreen(SCREEN_SEARCH)'>
            <label>Search</label>
        </div>
        <div class='selector' @click='$emit("loggedout")'>
            <label>Logout</label>
        </div>
    </div>
    <div class='section-content'>
        <div class='content welcome' v-if='contentScreen === SCREEN_WELCOME'>Willkommen {{ user.name }}!</div>
        <Userdata v-else-if='contentScreen === SCREEN_MYDATA' :user='user' @updated="updated"/>
        <Userlist v-else-if='contentScreen === SCREEN_LISTPERSONS' :data='data'/>
    </div>
</template>