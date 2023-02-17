<script setup>
import { ref, onBeforeUpdate, onBeforeMount } from 'vue'
import Userdata from '../Userdata.vue'
import Table from '../Table.vue'
import Navigator from '../Navigator.vue'

const props = defineProps(["data", "email", "id", "privileges"])
const emit = defineEmits(["selectedself", "sort", "reverse", "updated"])

const SCREEN_LIST = 0;
const SCREEN_USER = 1;

const SORT_EMAIL = "email"
const COMPARE_EMAIL = function (a,b) {return a.email.localeCompare(b.email)}
const SORT_NAME = "name"
const COMPARE_NAME = function (a,b) {return a.name.localeCompare(b.name)}
const SORT_ZIP = "zip"
const COMPARE_ZIP = function (a,b) {return a.zip - b.zip}
const SORT_PLACE = "place"
const COMPARE_PLACE = function (a,b) {return a.place.localeCompare(b.place)}
const SORT_PHONE = "phone"
const COMPARE_PHONE = function (a,b) {return a.phone - b.phone}

const SORT_ASC = 0;
const SORT_DSC = 1;

const LIST_SIZE = 16;
const emptyUser = {email: "", name: "", zip: "", place: "", phone: ""}
const max = Math.ceil(props.data.length / LIST_SIZE)

const data = ref(props.data)
const page = ref(1)
const displayed = ref()

const sortAttribute = ref(SORT_EMAIL)
const sortDirection = ref(SORT_ASC)

const screen = ref(SCREEN_LIST)

const selectedUser = ref(emptyUser)

onBeforeMount(() => {
    data.value = data.value.sort( COMPARE_EMAIL )
    updateList()
})
onBeforeUpdate(updateList)

function updateList(){
    let start = (page.value - 1) * LIST_SIZE
    let end = (page.value) * LIST_SIZE
    let chunk = data.value.slice(start, end)

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
    page.value = newPage
}

function sort(by){
    if(sortAttribute.value === by){
        sortDirection.value = (sortDirection.value + 1) % 2
    }else{
        sortAttribute.value = by
        sortDirection.value = SORT_ASC
    }
    
    if(sortDirection.value === SORT_DSC){
        emit("reverse")
    }else{
        let compare
        switch(sortAttribute.value){
            case SORT_EMAIL:
                compare = COMPARE_EMAIL
                break
            case SORT_NAME:
                compare = COMPARE_NAME
                break
            case SORT_ZIP:
                compare = COMPARE_ZIP
                break
            case SORT_PLACE:
                compare = COMPARE_PLACE
                break;
            case SORT_PHONE:
                compare = COMPARE_PHONE
        }
        emit("sort",compare)
    }

    updateList()
}

function select(arg){
    if(props.email === arg.email){
        emit("selectedself")
    }else{
        selectedUser.value = arg
        setScreen(SCREEN_USER)
    }
}

function setScreen(newScreen){
    screen.value = newScreen
}
</script>

<template>
    <div v-if="screen === SCREEN_LIST" class="userlist">
        <Table :rows="displayed" :exclude="['privileges']" :sortAttribute='sortAttribute' :sortDirection='sortDirection' @sort="sort" @select="select"/>
        <Navigator :current="page" :max="max" @navigate="setPage"/>
    </div>
    <Userdata v-else-if="screen === SCREEN_USER" :user="selectedUser" :invoker="props.id" :privileges="props.privileges" mode="observe" @updated="$emit('updated')" @returned="setScreen(SCREEN_LIST)"/>
</template>