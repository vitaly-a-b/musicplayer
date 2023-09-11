const Ajax = (set) => {

    if(typeof set === 'undefined') set = {};

    if(typeof set.url === 'undefined' || !set.url){
        set.url = typeof PATH !== 'undefined' ? PATH : '/';
    }

    if(typeof set.ajax === 'undefined') set.ajax = true

    if(typeof set.type === 'undefined' || !set.type) set.type = 'GET';

    set.type = set.type.toUpperCase();

    let body = '';

    if(typeof set.data !== 'undefined' && set.data){

        if(typeof set.processData !== 'undefined' && !set.processData){

            body = set.data

        }else{

            for(let i in set.data){

                if(set.data.hasOwnProperty(i))
                    body += '&' + i + '=' + set.data[i];

            }

            body = body.substr(1);

            if(typeof ADMIN_MODE !== 'undefined'){

                body += body ? '&' : '';
                body += 'ADMIN_MODE=' + ADMIN_MODE;

            }

        }

    }

    if(set.type === 'GET' && body){

        set.url += '?' + body;
        body = null;

    }

    return new Promise((resolve, reject) => {

        let xhr = new XMLHttpRequest();

        xhr.open(set.type, set.url, true);

        let contentType = false;

        if(typeof set.headers !== 'undefined' && set.headers){

            for (let i in set.headers){

                if(set.headers.hasOwnProperty(i)){

                    xhr.setRequestHeader(i, set.headers[i]);

                    if(i.toLowerCase() === 'content-type') contentType = true;

                }

            }

        }

        if(!contentType && (typeof set.contentType === 'undefined' || set.contentType))
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        if(set.ajax)
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');


        if(typeof set.onprogress === 'function'){

            xhr.upload.onprogress = set.onprogress

        }

        if(typeof set.onload === 'function'){

            xhr.upload.onload = set.onload

        }

        xhr.onload = function(){

            if(this.status >= 200 && this.status < 300){

                if(/fatal\s+?error/ui.test(this.response)){

                    reject(this.response);

                }

                resolve(this.response);

            }

            reject(this.response);

        }

        xhr.onerror = function () {
            reject(this.response)
        }

        xhr.send(body);

    });

}

function isEmpty(arr){

    for(let i in arr){

        return false;

    }

    return true;

}

function errorAlert(){

    alert('Произошла внутренняя ошибка')

    return false

}

Element.prototype.slideToggle = function(time, callback){

    let _time = typeof time === 'number' ? time : 400

    callback = typeof time === 'function' ? time : callback

    if(getComputedStyle(this)['display'] === 'none'){

        this.style.transition = null

        this.style.overflow = 'hidden';

        this.style.maxHeight = 0;

        this.style.display = 'block'

        this.style.transition = _time + 'ms'

        this.style.maxHeight = this.scrollHeight + 'px'

        setTimeout(() => {

            callback && callback()

        }, _time)

    }else{

        this.style.transition = _time + 'ms'

        this.style.maxHeight = 0;

        setTimeout(() => {

            this.style.transition = null

            this.style.display = 'none'

            callback && callback()

        }, _time)

    }

}

Element.prototype.sortable = (function(){

    let dragEl, nextEl;

    function _unDraggable(elements){

        if(elements && elements.length){

            for(let i = 0; i < elements.length; i++){

                if(!elements[i].hasAttribute('draggable')){

                    elements[i].draggable = false

                    _unDraggable(elements[i].children)

                }

            }

        }

    }

    function _onDragStart(e){

        this._dragging = null

        e.stopPropagation()

        this.tempTarget = null

        dragEl = e.target

        nextEl = dragEl.nextSibling

        e.dataTransfer.dropEffect = 'move'

        this.addEventListener('dragover', _onDragOver, false)

        this.addEventListener('dragend', _onDragEnd, false)

    }

    function _onDragOver(e){

        if(this._dragging === false) return false

        if(this._dragging === null && this.targetElementSelector){

            let targetElements = this.targetElementSelector.split(/,*\s+/)

            let exists = false

            for(let i in targetElements){

                if(e.target.matches(targetElements[i]) || e.target.closest(targetElements[i])){

                    exists = true

                    break

                }

            }

            if(!exists){

                return this._dragging = false

            }

        }

        this._dragging = true

        e.preventDefault()

        e.stopPropagation()

        e.dataTransfer.dropEffect = 'move'

        let target

        if(e.target !== this.tempTarget){

            this.tempTarget = e.target

            target = e.target.closest('[draggable=true]')

        }

        if(target && target !== dragEl && target.parentElement === this){

            let rect = target.getBoundingClientRect()

            let next = (e.clientY - rect.top)/(rect.bottom - rect.top) > .5;

            this.insertBefore(dragEl, next && target.nextSibling || target)

        }

    }

    function _onDragEnd(e){

        if(!this._dragging){

            return false;

        }

        e.preventDefault()

        this.removeEventListener('dragover', _onDragOver, false)

        this.removeEventListener('dragend', _onDragEnd, false)

        if(nextEl !== dragEl.nextSibling){

            this.onUpdate && this.onUpdate(dragEl)

        }

    }

    return function (options){

        options = options || {}

        this.onUpdate = options.stop || null

        this.targetElementSelector = options.targetElement || null

        let excludedElements = options.excludedElements && options.excludedElements.split(/,*\s+/) || null;

        [...this.children].forEach(item => {

            let draggable = true

            if(excludedElements){

                for(let i in excludedElements){

                    if(excludedElements.hasOwnProperty(i) && item.matches(excludedElements[i])){

                        draggable = false

                        break

                    }

                }

            }

            item.draggable = draggable

            _unDraggable(item.children)

        })

        this.removeEventListener('dragstart', _onDragStart, false)

        this.addEventListener('dragstart', _onDragStart, false)

    }

})()


// нужно для обновления GET параметров в адресной строке
// key - имя параметра, value - его значение, char - символ для подстановки в начало строки обычно ?
String.prototype.replaceGetParameters = function(key, value, char){
    // приводим к строке
    key  += ''
    value += ''

    // удаляем ? из начала строки
    let str = this.trim().replace(/^\?/, '')

    //если null в value
    if(/^\s*null\s*$/i.test(value)){
        value = ''
    }
    // экранируем спецсимволы в name инпута
    let regExpKey = key.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&")

    // если в key есть [] (чекбоксы) ,то добавляем экранированное value, иначе это поля с min и max (от и до)
    let regExpStr = (regExpKey + '=' + (/\[\]/.test(key) ? value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&") : '[^&]*'))

    // создаем паттерн для проверки нет ли этого же параметра в строке
    let regExp = new RegExp(`&?${regExpStr}`, 'i')

    //если этот паттерн есть, то нужно удалить подстроку подходящую под паттерн если это чекбокс или не нет value. Если это поля с min и max, то нужно обновить значение
    if(regExp.test(str)){

        str = /\[\]/.test(key) || !value ? str.replace(regExp, '') : str.replace(regExp, `&${key}=${value}`)

    }else{ // если паттерна нет в строке, то добавляем новый параметр в строку

        str += `&${key}=${value}`
    }

    return (char || '') + str.replace(/^&/, '')
}






























