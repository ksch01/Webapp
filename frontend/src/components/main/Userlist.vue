<script setup>
import { ref, onBeforeUpdate, onBeforeMount } from 'vue'
import Userdata from '../Userdata.vue'
import Table from '../Table.vue'
import Navigator from '../Navigator.vue'
import Error from '../Error.vue'

const props = defineProps([
    "data", 
    "page", 
    "email", 
    "id", 
    "privileges",
    "isLoading",
    "returnable",
    "err"
])
const emit = defineEmits([
    "sort", 
    "reverse", 
    "page", 
    "reload", 
    "return", 
    "updated", 
    "deleted", 
    "selectedself"
])

const SCREEN_LIST = 0;
const SCREEN_USER = 1;

const LIST_SIZE = 16;
const emptyUser = {email: "", name: "", zip: "", place: "", phone: ""}
let max = Math.ceil(props.data.value.length / LIST_SIZE)

const displayed = ref()

const screen = ref(SCREEN_LIST)

const selectedUser = ref(emptyUser)

onBeforeMount(updateList)
onBeforeUpdate(updateList)

function updateList(){
    let start = (props.page - 1) * LIST_SIZE
    let end = (props.page) * LIST_SIZE
    let chunk = props.data.value.slice(start, end)

    let missing
    if(chunk.length === 0)
        missing = LIST_SIZE
    else
        missing = (LIST_SIZE - chunk.length) % LIST_SIZE

    for(let i = 0; i < missing; i++)
        chunk.push(emptyUser)
   
    max = Math.ceil(props.data.value.length / LIST_SIZE)
    displayed.value = chunk
}

function setPage(newPage){
    emit("page",newPage)
}

function sort(by){
    if(props.data.sortAttribute.attribute === by){
        emit("reverse")
    }else{
        emit("sort", by);
    }
}
function select(arg){
    if(props.email === arg.email){
        emit("selectedself")
    }else if(arg.email !== ""){
        selectedUser.value = arg
        setScreen(SCREEN_USER)
    }
}

function updated(updatedUser){
    emit('updated', updatedUser)
}

function deleted(deletedUserEmail){
    emit('deleted', deletedUserEmail)
}

function reload(){
    if(!props.isLoading){
        emit("reload")
    }
}

function setScreen(newScreen){
    screen.value = newScreen
}
</script>

<template>
    <div v-if="screen === SCREEN_LIST" class="userlist">
        <Table :rows="displayed" :exclude="['group']" :sortAttribute='props.data.sortAttribute.attribute' :sortDirection='props.data.sortDirection' @sort="sort" @select="select"/>
        <div class="footer">
            <div v-if='props.isLoading' class='small-loader'/>
            <button v-else class='reload' @click="reload">
                <span class="rotate">
                    &#8635
                </span>
            </button>
            <button class="reload" v-if="props.returnable" @click="$emit('return')">&#60</button>
            <Error :err="props.err"/>
            <Navigator :current="page" :max="max" @navigate="setPage"/>
        </div>
    </div>
    <Userdata v-else-if="screen === SCREEN_USER" :user="selectedUser" :invoker="props.id" :privileges="props.privileges" mode="observe" @updated="updated" @deleted="deleted" @returned="setScreen(SCREEN_LIST)"/>
</template>