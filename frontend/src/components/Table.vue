<script setup>
import { onBeforeMount, ref } from 'vue'

const props = defineProps(["rows", "exclude","sortAttribute", "sortDirection"])
defineEmits(["select", "sort"])

const columns = ref([])

onBeforeMount(() => {
    if(props.rows !== undefined && props.rows.length !== 0){
        columns.value = Object.keys(props.rows[0])

        if(props.exclude !== undefined){
            for(let exclude of props.exclude){
                for(let i = 0; i < columns.value.length; i++){
                    if(columns.value[i] === exclude){
                        columns.value.splice(i, i)
                        break
                    }
                }
            }
        }
    }
})

function getColName(col){
    let colName = col.toUpperCase()
    if(props.sortAttribute === col){
        colName += (props.sortDirection) ? " &#9660" : " &#9650"
    }
    return colName;
}

</script>

<template>
    <table>
        <thead>
            <tr>
                <th v-for="col in columns" @click="$emit('sort', col)" v-html="getColName(col)"/>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in rows" @click="$emit('select', row)">
                <td v-for="col in columns">{{ row[col] }}</td>
            </tr>
        </tbody>
    </table>
</template>