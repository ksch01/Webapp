<script setup>
import { ref, onBeforeUpdate, onBeforeMount } from 'vue'
import Userdata from '../Userdata.vue'
import Table from '../Table.vue'
import Navigator from '../Navigator.vue'

const props = defineProps(["data"])

const SCREEN_LIST = 0;
const SCREEN_USER = 1;

const LIST_SIZE = 16;
const emptyUser = {email: "", name: "", zip: "", place: "", phone: ""}
const max = Math.ceil(props.data.length / 16)

const page = ref(1)
const displayed = ref()

const screen = ref(SCREEN_LIST)
const selectedUser = ref(emptyUser)

onBeforeMount(updateList)
onBeforeUpdate(updateList)

function updateList(){
    let start = (page.value - 1) * LIST_SIZE
    let end = (page.value) * LIST_SIZE
    let chunk = props.data.slice(start, end)

    let missing
    if(chunk.length === 0)
        missing = LIST_SIZE
    else
        missing = LIST_SIZE - chunk.length % LIST_SIZE

    for(let i = 0; i < missing; i++)
        chunk.push(emptyUser)

    displayed.value = chunk
}

function setPage(newPage){
    page.value = newPage
}

function select(arg){
    selectedUser.value = arg
    setScreen(SCREEN_USER)
}

function setScreen(newScreen){
    screen.value = newScreen
}

</script>

<template>
    <div v-if="screen === SCREEN_LIST" class="userlist">
        <Table :rows="displayed" @select="select"/>
        <Navigator :current="page" :max="max" @navigate="setPage"/>
    </div>
    <Userdata v-else-if="screen === SCREEN_USER" :user="selectedUser" mode="observe"/>
</template>