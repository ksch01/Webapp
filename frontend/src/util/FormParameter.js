export default class FormParameter{

    value
    isValid = true

    constructor(value, validator){
        if(value === undefined || typeof value !== 'string')this.value = ''
        else this.value = value

        if(validator === undefined)this.validator = () => true
        else this.validator = validator
    }

    check(){
        this.isValid = this.validate()
        return this.isValid
    }

    reset(){
        this.value = ''
        this.isValid = true
    }

    setAndCheck(newValue){
        this.value = newValue
        this.check()
    }

    validate(){
        return this.validator(this.value)
    }
}