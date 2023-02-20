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

const SORT_EMAIL = {
    attribute: "email",
    compare: function (a,b) {return a.email.localeCompare(b.email)},
    reverse: function (a,b) {return b.email.localeCompare(a.email)}
}
const SORT_NAME = {
    attribute: "name",
    compare: function (a,b) {return a.name.localeCompare(b.name)},
    reverse: function (a,b) {return b.name.localeCompare(a.name)}
}
const SORT_ZIP = {
    attribute: "zip",
    compare: function (a,b) {return a.zip - b.zip},
    reverse: function (a,b) {return b.zip - a.zip}
}
const SORT_PLACE = {
    attribute: "place",
    compare: function (a,b) {return a.place.localeCompare(b.place)},
    reverse: function (a,b) {return b.place.localeCompare(a.place)}
}
const SORT_PHONE = {
    attribute: "phone",
    compare: function (a,b) {return a.phone - b.phone},
    reverse: function (a,b) {return b.phone - a.phone}
}

const SORT_ASC = 0;
const SORT_DSC = 1;

const contentScreen = ref(SCREEN_WELCOME)

const user = ref(props.user)

const data = ref()
const page = ref(1)
const sortAttribute = ref(SORT_EMAIL);
const sortDirection = ref(SORT_ASC);

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
    let compare;
    if(sortDirection.value == SORT_ASC){
        compare = sortAttribute.value.compare
    }else{
        compare = sortAttribute.value.reverse
    }
    data.value = response.data.sort(compare)
}
function handleError(error){
    console.log(error)
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

function updatedOther(updatedUser){
    let user = data.value.find(user => user.email === updatedUser.initialEmail)
    user.email = updatedUser.email
    user.name = updatedUser.name
    user.zip = updatedUser.zip
    user.place = updatedUser.place
    user.phone = updatedUser.phone
    user.privileges = updatedUser.privileges
}

function deleted(deletedUserEmail){
    for(let i = 0; i < data.value.length; i++){
        if(data.value[i].email === deletedUserEmail){
            data.value.splice(i,i)
            return
        }
    }
}

function sort(attribute){
    switch(attribute){
        case SORT_EMAIL.attribute:
            sortAttribute.value = SORT_EMAIL
            break
        case SORT_NAME.attribute:
            sortAttribute.value = SORT_NAME
            break;
        case SORT_ZIP.attribute:
            sortAttribute.value = SORT_ZIP
            break;
        case SORT_PLACE.attribute:
            sortAttribute.value = SORT_PLACE
            break;
        case SORT_PHONE.attribute:
            sortAttribute.value = SORT_PHONE
            break;
        default:
            console.error("unexpected sort attribute ", attribute)
    }
    data.value = data.value.sort(sortAttribute.value.compare)
}

function reverse(){
    data.value = data.value.reverse()
    sortDirection.value = (sortDirection.value + 1) % 2
}

function setPage(newPage){
    page.value = newPage;
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
        <Userlist v-else-if='contentScreen === SCREEN_LISTPERSONS' :data='data' :page='page' :sort-attribute="sortAttribute.attribute" :sort-direction='sortDirection' :email='user.email' :id='user.id' :privileges='user.privileges' @selectedself="setScreen(SCREEN_MYDATA)" @sort="sort" @reverse="reverse" @page="setPage" @updated="updatedOther" @deleted="deleted" @reload="reload"/>
        <Usersearch v-else-if='contentScreen === SCREEN_SEARCH'/>
    </div>
</template>