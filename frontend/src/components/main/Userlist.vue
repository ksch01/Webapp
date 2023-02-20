<script setup>
import { ref, onBeforeUpdate, onBeforeMount } from 'vue'
import Userdata from '../Userdata.vue'
import Table from '../Table.vue'
import Navigator from '../Navigator.vue'

const props = defineProps(["data", "page", "sort-attribute", "sort-direction", "email", "id", "privileges"])
const emit = defineEmits(["selectedself", "sort", "page", "reverse", "updated", "deleted", "reload"])

const SCREEN_LIST = 0;
const SCREEN_USER = 1;

const LIST_SIZE = 16;
const emptyUser = {email: "", name: "", zip: "", place: "", phone: ""}
const max = Math.ceil(props.data.length / LIST_SIZE)

const displayed = ref()

const screen = ref(SCREEN_LIST)

const selectedUser = ref(emptyUser)

onBeforeMount(updateList)
onBeforeUpdate(updateList)

function updateList(){
    let start = (props.page - 1) * LIST_SIZE
    let end = (props.page) * LIST_SIZE
    let chunk = props.data.slice(start, end)

    let missing
    if(chunk.length === 0)
        missing = LIST_SIZE
    else
        missing = (LIST_SIZE - chunk.length) % LIST_SIZE

    for(let i = 0; i < missing; i++)
        chunk.push(emptyUser)

    displayed.value = chunk
}

function setPage(newPage){
    emit("page",newPage)
}

function sort(by){
    if(props.sortAttribute === by){
        emit("reverse")
    }else{
        emit("sort", by);
    }
}

function select(arg){
    if(props.email === arg.email){
        emit("selectedself")
    }else{
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

function setScreen(newScreen){
    screen.value = newScreen
}
</script>

<template>
    <div v-if="screen === SCREEN_LIST" class="userlist">
        <Table :rows="displayed" :exclude="['privileges']" :sortAttribute='props.sortAttribute' :sortDirection='props.sortDirection' @sort="sort" @select="select"/>
        <div class="footer">
            <button class="reload" @click="$emit('reload')">&#8635</button>
            <Navigator :current="page" :max="max" @navigate="setPage"/>
        </div>
    </div>
    <Userdata v-else-if="screen === SCREEN_USER" :user="selectedUser" :invoker="props.id" :privileges="props.privileges" mode="observe" @updated="updated" @deleted="deleted" @returned="setScreen(SCREEN_LIST)"/>
</template>