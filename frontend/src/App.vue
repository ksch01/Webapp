<script setup>
import { ref } from 'vue'
import Login from './components/login/Login.vue'
import Signup from './components/login/Signup.vue'
import Main from './components/main/Application.vue'

const SCREEN_LOGIN = 0
const SCREEN_SIGNUP = 1
const SCREEN_MAIN = 2

let user

var screen = ref(SCREEN_LOGIN)

function login(){
  screen.value = SCREEN_LOGIN
}

function signup(){
  screen.value = SCREEN_SIGNUP
}

function loggedin(loginUser){
  user = loginUser
  screen.value = SCREEN_MAIN
}

function loggedout(){
  user = undefined
  screen.value = SCREEN_LOGIN
}

</script>

<template>
  <div class='screen'>
    <Login v-if="screen === SCREEN_LOGIN" @signup="signup" @loggedin="loggedin"/>
    <Signup v-else-if="screen === SCREEN_SIGNUP" @login="login"/>
    <Main v-else-if="screen === SCREEN_MAIN" :user="user" @loggedout="loggedout"/>
  </div>
</template>