const validEmailRegEx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
export function email(email){
    if(typeof email !== 'string')return false
    email = email.trim()
    return email.length <= 64 && 
        validEmailRegEx.test(email)
}

export function password(password){
    return typeof password === 'string' && 
        password.length >= 8 && 
        password.length <= 72
}

export function name(name){
    if(typeof name !== 'string')return false
    name = name.trim()
    return name.length > 0 && 
        name.length <= 64
}

export function zip(zip){
    if(typeof zip !== 'string')return false
    let zipInt = parseInt(zip)
    return !isNaN(zip) &&
        zipInt >= 0 && 
        zipInt <= 99999
}


export function place(place){
    if(typeof place !== 'string')return false
    place = place.trim()
    return place.length > 0 && 
        place.length <= 64
}

export function phone(phone){
    if(typeof phone !== 'string')return false
    phone = phone.replace(/\s+/g, "")
    phone = phone.replace("-","")
    if(phone.charAt(0) === '+')phone = phone.substring(1)
    return phone.length >= 9 && phone.length <= 12 && !isNaN(phone)
}
export function phoneMax(phone){
    if(typeof phone !== 'string')return false
    phone = phone.replace(/\s+/g, "")
    phone = phone.replace("-", "")
    if(phone.charAt(0) === "+")phone = phone.substring(1)
    return ((phone.length <= 12) && !isNaN(phone))
}