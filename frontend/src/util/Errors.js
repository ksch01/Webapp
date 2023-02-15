export default class Errors{

    errors = []

    addError(errorMessage, errorSource){
        this.errors.push(new Error(errorMessage, errorSource))
    }

    getError(){
        for(let i = 0; i < this.errors.length; i++){
            if(this.errors[i].occured()){
                return this.errors[i].message
            }
        }
        return false
    }
}

class Error{
    constructor(errorMessage, errorSource){
        this.message = errorMessage
        this.occured = errorSource
    }
}