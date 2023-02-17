export default class Errors{

    errors = []
    currentError = -1

    addError(errorMessage, errorSource){
        this.errors.push(new Error(errorMessage, errorSource))
    }

    isError(){
        for(let i = 0; i < this.errors.length; i++){
            if(this.errors[i].occured()){
                this.currentError = i
                return true
            }
        }
        this.currentError = false
        return false
    }

    getError(){
        if(this.currentError === false)return false;
        return this.errors[this.currentError].message;
    }
}

class Error{
    constructor(errorMessage, errorSource){
        this.message = errorMessage
        this.occured = errorSource
    }
}