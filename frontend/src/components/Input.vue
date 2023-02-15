<script setup>

const props = defineProps(['label', 'type', 'invalid', 'modelValue'])
defineEmits(['update:modelValue', 'focusout', 'onkeyPress:event'])

function getType(){
    if(props.type.includes('password'))return 'password'
    else return props.type
}

</script>

<template>
    <div class="input">
        <label class="input-label">{{label}}</label>
        <input 
            :class="(props.invalid) ? 'input-input input-invalid' : 'input-input'" 
            :type="getType()" 
            :value="modelValue" 
            :disabled="type.includes('disabled')" 
            @input="$emit('update:modelValue', $event.target.value)" 
            @focusout="$emit('focusout')" 
            @onkeypress="$emit('onkeyPress', $event)"
        />
    </div>
</template>