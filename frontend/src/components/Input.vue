<script setup>

const props = defineProps(['label', 'type', 'invalid', 'modelValue', 'id'])
const emit = defineEmits(['update:modelValue', 'focusout', 'onkeyPress:event'])

function getType(){
    if(props.type.includes('password'))return 'password'
    else return props.type
}

</script>

<template>
    <div class="input">
        <label class="form-label" :for="props.id">{{label}}</label>
        <input 
            :class="(props.invalid) ? 'form-control input-invalid' : 'form-control'" 
            :id="props.id"
            :type="getType()" 
            :value="modelValue" 
            :disabled="type.includes('disabled')" 
            @input="emit('update:modelValue', $event.target.value)" 
            @focusout="$emit('focusout')" 
            @onkeypress="$emit('onkeyPress', $event)"
        />
    </div>
</template>