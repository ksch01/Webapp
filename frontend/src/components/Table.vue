<script setup>
import { onBeforeMount, ref } from 'vue'

const props = defineProps(["rows"])
defineEmits(["select"])

const columns = ref([])

onBeforeMount(() => {
    if(props.rows !== undefined && props.rows.length !== 0){
        columns.value = Object.keys(props.rows[0])
    }
})
    
</script>

<template>
    <table>
        <thead>
            <tr>
                <th v-for="col in columns">{{ col.toUpperCase() }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in rows" @click="$emit('select', row)">
                <td v-for="col in columns">{{ row[col] }}</td>
            </tr>
        </tbody>
    </table>
</template>