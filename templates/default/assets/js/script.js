
document.addEventListener('DOMContentLoaded', () => {

    const audio = document.querySelector('#player_from_mejs')

    let time = document.querySelector('.play-bar')
    let audiotrack = document.querySelector('.seek-bar')

    let volume = document.querySelector('.volume-bar')
    let mute = document.querySelector('.mute')

    let control = document.querySelector('.player-controls')

    let prev = document.querySelector('.prev')
    let next = document.querySelector('.next')
    let pause = document.querySelector('.pause')
    let play = document.querySelector('.basic .play')

    let repeat = document.querySelector('.repeat')

    let audioPlay;

    let track = 0


    let el = document.querySelectorAll('.play')

    if (el.constructor === Array || typeof el[0] !== 'undefined'){

        el.forEach(item=>{

            item.addEventListener('click', () => {

                if(item.hasAttribute('data-url')){

                    if(!item.closest('.item').classList.contains('active')){

                        document.querySelectorAll('.item.active').forEach(elem => {
                            elem.classList.remove('active', 'pause')
                        })

                        item.closest('.item').classList.add('active')

                        audio.src = item.getAttribute('data-url')

                        audio.currentTime = 0;

                        audioplay()

                    }else{

                        if(item.closest('.item').classList.contains('pause')){

                            audioplay()

                        }else{

                            pausefunc()
                        }

                    }

                }else{

                    audioplay()

                }
            })
        })


    }


    pause.addEventListener('click', pausefunc )

    next.addEventListener('click', nextTrack)

    prev.addEventListener('click', prevTrack)


    repeat.addEventListener('click', function (){

        if(!this.classList.contains('active')){

            this.classList.add('active')

        }else{

            this.classList.remove('active')
        }

    })


    audiotrack.addEventListener('click', function(event){

        let width = window.getComputedStyle(audiotrack).width;

        let novstr = Number(width.substr(0, width.length-2));

        audio.currentTime = event.clientX / novstr * audio.duration;

    });





    volume.addEventListener('click', function(event){

        let width = window.getComputedStyle(volume).width;

        let novstr = Number(width.substr(0, width.length-2));

        //audio.volume = event.clientX / novstr;

        console.log(novstr)
        console.log(event.clientX)
        //volume.querySelector('.volume-bar-value').style.width = audio.volume * 100 + '%';

    });





    mute.addEventListener('click', function (){

        if(this.classList.contains('active')){

            this.classList.remove('active')

            audio.volume = '1'

        }else{

            this.classList.add('active')

            audio.volume = '0'

        }

    })




    function audioplay(){

        control.classList.remove('inited')
        control.classList.remove('pause')
        pause.style.removeProperty('display')
        control.classList.add('playing')

        if(document.querySelector('.item.active').classList.contains('pause')){
            document.querySelector('.item.active').classList.remove('pause')
        }

        audio.play();

        audioPlay = setInterval(function(){

            let audioTime = Math.round(audio.currentTime);

            let audioLength = Math.round(audio.duration);

            time.style.width = (audioTime * 100) / audioLength + '%';

            if (audioTime === audioLength){

                if(document.querySelector('.repeat.active')){

                    audio.currentTime = 0;
                    audio.play();

                }else{

                    nextTrack()

                }
            }
        }, 100);


    }


    function nextTrack(){

        let old_active = document.querySelector('.item.active')
        old_active.classList.remove('active')

        let next_item = old_active.nextElementSibling ? old_active.nextElementSibling : old_active.parentElement.firstElementChild
        next_item.classList.add('active')

        audio.src = next_item.querySelector('[data-url]').getAttribute('data-url')

        audio.currentTime = 0;

        audio.play();

    }



    function prevTrack(){

        let old_active = document.querySelector('.item.active')
        old_active.classList.remove('active')

        let prev_item = old_active.previousElementSibling ? old_active.previousElementSibling : old_active.parentElement.lastElementChild
        prev_item.classList.add('active')

        audio.src = prev_item.querySelector('[data-url]').getAttribute('data-url')

        audio.currentTime = 0;

        audio.play();
    }




    function pausefunc() {

        audio.pause();

        document.querySelector('.item.active').classList.add('pause')

        pause.style.display = 'none'
        control.classList.remove('playing')
        control.classList.add('pause')

        clearInterval(audioPlay);


    }







})



document.querySelectorAll('[data-modal-w]').forEach(item => {

    if(item.getAttribute('data-modal-w')){

        item.addEventListener('click', (e) => {

            e.preventDefault()

            let target = document.querySelector(`[data-modal-target="${item.getAttribute('data-modal-w')}"]`)

            if(target){
                target.classList.add('visible')
            }
        })
    }
})

document.querySelectorAll('[data-modal-target]').forEach(item => {

    item.addEventListener('click', (e) => {

        if(e.target === item || e.target.closest('.close')){
            item.classList.remove('visible')
        }
    })
})

let loginForms = document.querySelectorAll('[data-modal-target="lk"] form')

if(loginForms.length){
    document.querySelectorAll('.registration-titles h2').forEach((item, index) => {
        item.addEventListener('click', () => {
            loginForms[index].style.display = 'block'
            loginForms[+!index].style.display = 'none'
        })
    })
}

document.querySelectorAll('input[type="tel"]').forEach(item => phoneValidate(item))


function phoneValidate(item){

    let char = ''

    item.addEventListener('input', function (e){

        // если inputType был на удаление
        if(e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward'){
            return false
        }

        if(e.data && e.data==='7' && char && char !== '+'){
            this.value = ''
            char = ''
        }

        char = (e.data && e.data === '+' && this.value.length === 1) ? e.data : ''

        this.value = this.value.replace(/\D/g, '')

        let start = 2

        if(/^./.test(this.value)){
            this.value = this.value.replace(/^./, '+7')

        }else if (this.value && !/^\+/.test(this.value)){
            this.value = '+' + this.value

        }

        let objectChars = {
            0: '(',
            4: ')',
            8: '-',
            11: '-'
        }

        //if(/^\+7/.test(this.value)){

        let limit = 14

        for (let i in objectChars){

            let j = +i

            if(this.value[start + j] && this.value[start + j] !== objectChars[j])
                this.value = this.value.substring(0, start + j) + objectChars[j] + this.value.substring(start + j)

        }

        if(this.value[start+limit])
            this.value = this.value.substring(0, start + limit)

        //}

    })

    item.dispatchEvent(new Event('input'))

}







async function deleteTrack(e){

    try{

        if (!this.hasAttribute('data-track-id')){
            throw new Error('нет атрибута на элементе data-track-id')
        }

        let id = +this.getAttribute('data-track-id')

        if (isNaN(id) || !id){
            throw new Error('значение id не число')
        }

        let playListActive = document.querySelector('.playlist-item.active')

        if (!playListActive || !playListActive.hasAttribute('data-playlist-id')){
            throw new Error('нет активного плейлиста')
        }

        let playListId = +playListActive.getAttribute('data-playlist-id')

        if (playListId){

            let res = await Ajax({
                data: {
                    id: id,
                    playListId: playListId,
                    ajax: 'deleteTrack'
                }
            })

            res = JSON.parse(res)

            if(typeof res.error !== 'undefined' || typeof res.success === 'undefined'){
                throw new Error(res.message || null)
            }

            let item = this.closest('.item')

            if (!item){
                throw new Error('не найден html элемент для удаления со страницы')
            }

            item.remove()

        }

    }catch (e){

        console.error(e)

        if(typeof e.message !== 'undefined'){
            alert(e.message)
        }

    }

}




async function addTrack(e){

    try{

        if (!this.hasAttribute('data-playlist-id')){
            throw new Error('нет атрибута на элементе data-playlist-id')
        }

        let playListId = +this.getAttribute('data-playlist-id')

        if (isNaN(playListId) || !playListId){
            throw new Error('значение playListId не число ')
        }

        let trackElement = this.closest('.item').querySelector('[data-track-id]')

        if (!trackElement){
            throw new Error('нет элемента с атрибутом data-track-id')
        }

        let trackId = +trackElement.getAttribute('data-track-id')

        if (isNaN(trackId) || !trackId){
            throw new Error('значение trackId не число ')
        }

        let res = await Ajax({
            data: {
                id: trackId,
                playListId: playListId,
                ajax: 'addTrack'
            }
        })

        res = JSON.parse(res)

        if(typeof res.error !== 'undefined' || typeof res.success === 'undefined'){
            throw new Error(res.message || null)
        }

        let divAddToPlaylist = this.closest('.add-to-playlist')

        if (divAddToPlaylist){
            divAddToPlaylist.classList.remove('active')
        }

        trackElement.remove()


    }catch (e){

        console.error(e)

        if(typeof e.message !== 'undefined'){
            alert(e.message)
        }

    }
}



document.querySelectorAll('[data-track-id].delete, [data-track-id].add').forEach(item => {


    if(item.classList.contains('delete')){
        item.addEventListener('click', deleteTrack)
    }

    if(item.classList.contains('add')){
        item.addEventListener('click', function (){
            this.nextElementSibling.classList.toggle('active')
        })

        item.nextElementSibling.querySelectorAll('li.add-to-playlist-item').forEach(i => {
            i.addEventListener('click', addTrack)
        })

    }

})




document.querySelectorAll('.create-new-playlist > button').forEach(item => {

    item.addEventListener('click', () => {
        menu = item.closest('.create-new-playlist').querySelector('.menu-create-playlist')

        if (menu){
            menu.classList.toggle('active')
        }
    })

})
/*
    document.querySelectorAll('li.item .add').forEach(item => {
        item.addEventListener('click', function (){
            this.nextElementSibling.classList.toggle('active')
        })
    })*/

async function requestNewPlaylist(){

    try {

        if (this.type === 'submit'){

            let name = this.closest('.menu-create-playlist').querySelector('input[type=text]').value

            if (!name){
                throw new Error('Не заданно имя плейлиста')
            }

            name = name.replace(/[^\w\s]/igu, '').trim()

            let data = {
                ajax: 'add_to_playlist',
                namePlaylist: name
            };

            let response = await fetch ( `/?namePlaylist=${name}&ajax=add_to_playlist`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            let res

            if (response.ok){
                res = await response.json()
            }


            if(typeof res.error !== 'undefined' || typeof res.success === 'undefined'){
                throw new Error(res.message || null)
            }

            let playlistElement = document.createElement('li')
            playlistElement.className = 'playlist-item'
            playlistElement.setAttribute('data-playlist-id', res.id)
            playlistElement.innerHTML = `<a href="/?pl=${res.id}">${res.name}</a> <div class="delete"></div>`

            let parent = document.querySelector('ul.playlists')

            if(parent){
                parent.append(playlistElement)

            }else{

                let elNotPlaylist = document.querySelector('.notPlaylist')

                if (elNotPlaylist){
                    elNotPlaylist.remove()
                }

                parent = document.createElement('ul')
                parent.className ='playlists'
                this.closest('.boxShadow.block-cont').append(parent)
                parent.append(playlistElement)

                // повешать обработчик на удаления плейлиста
                eventDeletePlaylist()

            }

        }

        this.closest('.menu-create-playlist').classList.remove('active')


    }catch (e){

        console.error(e)

        if(typeof e.message !== 'undefined'){
            alert(e.message)
        }
    }


}


document.querySelectorAll('.menu-create-playlist input[type=submit], .menu-create-playlist input[type=reset]').forEach(item => {
    item.addEventListener('click', requestNewPlaylist)
})



async function deletePlaylist(){

    try{

        let playlistElement = this.closest('[data-playlist-id]')

        if (!playlistElement){
            throw new Error('Не найден элемент с id плейлиста')
        }

        let id = +playlistElement.getAttribute('data-playlist-id')

        if (id){

            fetch(`/?id=${id}&ajax=delete_playlist`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(response => {
                    return response.json()

                }).then(response => {

                if(typeof response.error !== 'undefined' || typeof response.success === 'undefined'){
                    throw new Error(response.message || null)
                }

                let element = this.closest('li')
                let parentElement = this.closest('ul')

                if (parentElement.querySelectorAll('li').length <= 1){

                    let boxShadow = this.closest('.boxShadow.block-cont')
                    parentElement.remove()

                    let textElement = document.createElement('span')
                    textElement.classList.add('notPlaylist')
                    textElement.innerText = 'У Вас еще нет не одного плейлиста'
                    boxShadow.append(textElement)

                }else if (element){
                    element.remove()
                }




            })
        }


    }catch (e){

        console.error(e)

        if(typeof e.message !== 'undefined'){
            alert(e.message)
        }

    }

}

function eventDeletePlaylist(){
    document.querySelectorAll('.playlists .delete').forEach(item => {
        item.removeEventListener('click', deletePlaylist)
        item.addEventListener('click', deletePlaylist)
    })
}

eventDeletePlaylist()























