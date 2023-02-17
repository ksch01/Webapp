<script setup>
import { ref } from 'vue'
import Input from '../Input.vue'
import Param from '../../util/FormParameter'
import * as validate from '../../util/InputValidator.js'

const MODE_ONE_LINE = true

const email = ref(new Param('', () => true))
const name = ref(new Param('', () => true))
const zip = ref(new Param('', validate.number))
const place = ref(new Param('', () => true))
const phone = ref(new Param('', validate.number))

const criteria = ref("email")
const mode = ref(MODE_ONE_LINE)

function toggleMode(){
    mode.value = !mode.value;
}

function getType(inputType){
    if(inputType === undefined)inputType = ''
    if(shouldBeDisabled())inputType = inputType + 'disabled'
    return inputType
}
function shouldBeDisabled(){
    return false;
}

function currentCriteria(){
    switch(criteria.value){
        case "email":
            return email.value;
        case "name":
            return name.value;
        case "zip":
            return zip.value;
        case "place":
            return place.value;
        case "phone":
            return phone.value;
    }
}

function checkEmail(){email.value.check()}
function checkName(){name.value.check()}
function checkZip(){zip.value.check()}
function checkPlace(){place.value.check()}
function checkPhone(){phone.value.check()}

</script>

<template>
    <div class="content form">
        <div v-if="mode" class="input">
            <select class="input-dyn-label" v-model="criteria">
                <option value="email">E-Mail</option>
                <option value="name">Name</option>
                <option value="zip">PLZ</option>
                <option value="place">Ort</option>
                <option value="phone">Telefon</option>
            </select>
            <input class="input-input" :v-model='currentCriteria()'/>
        </div>
        <template v-else>
            <Input label='E-Mail' :invalid='!email.isValid' v-model='email.value' :type='getType()' @focusout='checkEmail'/>
            <Input label='Name' :invalid='!name.isValid' v-model='name.value' :type='getType()' @focusout='checkName'/>
            <Input label='PLZ' :invalid='!zip.isValid' v-model='zip.value' :type='getType()' @focusout='checkZip'/>
            <Input label='Ort' :invalid='!place.isValid' v-model='place.value' :type='getType()' @focusout='checkPlace'/>
            <Input label='Telefon' :invalid='!phone.isValid' v-model='phone.value' :type='getType()' @focusout='checkPhone'/>
        </template>
        <div class="button-row">
            <button class="form-button" @click="toggleMode">{{ mode ? "Mehr" : "Weniger"}}</button>
            <button class="form-button">Suchen</button>
        </div>
    </div>
</template>