const SORT_EMAIL = {
    attribute: "email",
    compare: function (a,b) {return a.email.localeCompare(b.email)},
    reverse: function (a,b) {return b.email.localeCompare(a.email)}
}
const SORT_NAME = {
    attribute: "name",
    compare: function (a,b) {return a.name.localeCompare(b.name)},
    reverse: function (a,b) {return b.name.localeCompare(a.name)}
}
const SORT_ZIP = {
    attribute: "zip",
    compare: function (a,b) {return a.zip - b.zip},
    reverse: function (a,b) {return b.zip - a.zip}
}
const SORT_PLACE = {
    attribute: "place",
    compare: function (a,b) {return a.place.localeCompare(b.place)},
    reverse: function (a,b) {return b.place.localeCompare(a.place)}
}
const SORT_PHONE = {
    attribute: "phone",
    compare: function (a,b) {return a.phone - b.phone},
    reverse: function (a,b) {return b.phone - a.phone}
}

const SORT_ASC = false;

export default class Data{

    value
    sortAttribute = SORT_EMAIL
    sortDirection = SORT_ASC

    constructor(value){
        if(value === undefined)value = []
        else this.value = value
    }

    update(data){
        let compare
        if(this.sortDirection == SORT_ASC){
            compare = this.sortAttribute.compare
        }else{
            compare = this.sortAttribute.reverse
        }
        this.value = data.sort(compare)
    }

    updateUser(updatedUser){
        let user = this.value.find(user => user.email === updatedUser.initialEmail)
        user.email = updatedUser.email
        user.name = updatedUser.name
        user.zip = updatedUser.zip
        user.place = updatedUser.place
        user.phone = updatedUser.phone
        user.privileges = updatedUser.privileges
    }

    delete(deletedUserEmail){
        for(let i = 0; i < this.value.length; i++){
            if(this.value[i].email === deletedUserEmail){
                this.value.splice(i,1)
                break
            }
        }
    }
    
    sort(attribute){
        switch(attribute){
            case SORT_EMAIL.attribute:
                this.sortAttribute = SORT_EMAIL
                break
            case SORT_NAME.attribute:
                this.sortAttribute = SORT_NAME
                break;
            case SORT_ZIP.attribute:
                this.sortAttribute = SORT_ZIP
                break;
            case SORT_PLACE.attribute:
                this.sortAttribute = SORT_PLACE
                break;
            case SORT_PHONE.attribute:
                this.sortAttribute = SORT_PHONE
                break;
            default:
                console.error("unexpected sort attribute ", attribute)
        }
        this.value = this.value.sort(this.sortAttribute.compare)
    }

    reverse(){
        this.sortDirection = !this.sortDirection
        this.value = this.value.reverse()
    }

    isEmpty(){
        return this.value.length === 0
    }
    empty(){
        this.value = []
    }
}