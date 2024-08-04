
    const audio = document.querySelector('#audio')

    const time = document.querySelector('.play-bar')
    const audiotrack = document.querySelector('.seek-bar')

    const volume = document.querySelector('.volume-bar')
    const volumeInside = document.querySelector('.volume-bar-value')
    const mute = document.querySelector('.mute')

    const control = document.querySelector('.player-controls')

    const prev = document.querySelector('.prev')
    const next = document.querySelector('.next')
    const pause = document.querySelector('.pause')

    const repeat = document.querySelector('.repeat')
    const shuffle = document.querySelector('.shuffle')

    let audioPlay;

    if (volumeInside && mute){
        audio.volume = mute.volumeRate = +volumeInside.style.width.replace(/%/g, '')/100
    }

    if (mute && mute.classList.contains('active')){
        audio.volume = 0
        volumeInside.style.width = 0
    }



    function playElementEvent(){

        if(this.hasAttribute('data-url')){

            if(!this.closest('.item').classList.contains('active')){

                document.querySelectorAll('.item.active').forEach(elem => {
                    elem.classList.remove('active', 'pause')
                })

                this.closest('.item').classList.add('active')
                audio.src = this.getAttribute('data-url')
                audio.currentTime = 0;

                audioplay()

            }else{

                if(this.closest('.item').classList.contains('pause')){
                    audioplay()

                }else{
                    pausefunc()
                }

            }

        }else{

            if (!document.querySelector('.item.active')){
                let item = document.querySelector('.item')

                if (item){
                    item.classList.add('active')

                    if (item.querySelector('.play').hasAttribute('data-url')){
                        audio.src = item.querySelector('.play').getAttribute('data-url')
                        audio.currentTime = 0;
                    }
                }
            }

            audioplay()

        }

    }



    let el = document.querySelectorAll('.play')

    if (el.constructor === Array || typeof el[0] !== 'undefined'){

        el.forEach(item=>{
            item.addEventListener('click', playElementEvent)
        })

    }


    pause.addEventListener('click', pausefunc )

    next.addEventListener('click', nextTrack)

    prev.addEventListener('click', prevTrack)


    repeat.addEventListener('click', function (){

        if(!this.classList.contains('active')){
            this.classList.add('active')
            shuffle.classList.contains('active') && shuffle.classList.remove('active')

        }else{
            this.classList.remove('active')
        }
    })

    shuffle.addEventListener('click', function (){
        this.classList.toggle('active')
        repeat.classList.contains('active') && repeat.classList.remove('active')

    })


    audiotrack.addEventListener('click', function(event){

        if (!control.classList.contains('inited')){

            let coordPlayBar = audiotrack.getBoundingClientRect()

            if (coordPlayBar){

                audio.currentTime = (event.clientX - coordPlayBar.x) / coordPlayBar.width * audio.duration;
                time.style.width =  audio.currentTime/audio.duration * 100 + '%'
            }

        }

    });



    audiotrack.addEventListener('mouseover', function(event){

        let timeBarMouseMove = timeBarMouseMoveInit(event)

        if (typeof timeBarMouseMove === 'function'){

            audiotrack.addEventListener('mousemove', timeBarMouseMove);

            audiotrack.addEventListener('mouseout', () => {

                audiotrack.removeEventListener('mousemove', timeBarMouseMove)
                event.flag = true
                timeBarMouseMove(event)

            });
        }


    })


    function timeBarMouseMoveInit(event){

        if (!control.classList.contains('inited')){

            let currentTimeElement = document.querySelector('.line-progress .seek-bar .currentTime')
            let allTimeElement  = document.querySelector('.line-progress .seek-bar .all-time')

            if (currentTimeElement && allTimeElement){

                allTimeElement.innerText = String(Math.floor(audio.duration/60)) + ':' + String(Math.round(audio.duration%60)).replace(/^\d?$/, '0$&')
                allTimeElement.style.display = 'block'


                let cordTimeBar =  audiotrack.getBoundingClientRect()

                let timeCurrent = (event.clientX - cordTimeBar.x)/cordTimeBar.width * audio.duration
                currentTimeElement.innerText = String(Math.floor(timeCurrent/60)) + ':' + String(Math.round(timeCurrent%60))
                currentTimeElement.style.left = event.clientX + 'px'
                currentTimeElement.style.display = 'block'

                return function (e){

                    if (e.flag){
                        allTimeElement.style.display = 'none'
                        currentTimeElement.style.display = 'none'

                    }else{

                        timeCurrent = (e.clientX - cordTimeBar.x)/cordTimeBar.width * audio.duration
                        currentTimeElement.innerText = String(Math.floor(timeCurrent/60)) + ':' + String(Math.round(timeCurrent%60)).replace(/^\d?$/, '0$&')
                        currentTimeElement.style.left = e.clientX + 'px'
                    }

                }

            }

        }

        return null

    }




    volume.addEventListener('mousedown', function(event){

        volumeControl(event)

        volume.addEventListener('mousemove', volumeControl)

        document.addEventListener('mouseup', () => {
            volume.removeEventListener('mousemove', volumeControl)
        })

    });


    function volumeControl(event){

        mute.classList.contains('active') && mute.classList.remove('active')

        let cordVolume =  volume.getBoundingClientRect()
        audio.volume = (event.clientX - cordVolume.x)/cordVolume.width
        volumeInside.style.width = audio.volume * 100 + '%'

    }





    mute.addEventListener('click', function (){

        if(this.classList.contains('active')){

            this.classList.remove('active')

            if (typeof mute.volumeRate === 'undefined'){
                audio.volume = '1'

            }else{
                audio.volume = mute.volumeRate
            }

            volumeInside.style.width = audio.volume * 100 + '%'

        }else{

            this.classList.add('active')
            mute.volumeRate = audio.volume
            audio.volume = '0'
            volumeInside.style.width = audio.volume * 100 + '%'

        }

    })


    function intervalSet(){

        audioPlay = setInterval(function(){

            let audioTime = Math.round(audio.currentTime);
            let audioLength = Math.round(audio.duration);

            time.style.width = (audioTime * 100) / audioLength + '%';

            if (audioTime === audioLength){

                if(repeat.classList.contains('active')) {
                    audio.currentTime = 0;
                    audio.play();

                }else if (shuffle.classList.contains('active')){

                    let list =  document.querySelectorAll('.item')

                    if (list.length){
                        let old_active = document.querySelector('.item.active')
                        old_active.classList.remove('active')

                        let num = Math.floor(Math.random() * list.length)
                        list[num].classList.add('active')

                        labelTrackArtistHeader()

                        audio.src = list[num].querySelector('[data-url]').getAttribute('data-url')
                        audio.currentTime = 0;
                        audio.play();
                    }

                }else{
                    nextTrack()

                }
            }
        }, 100);

    }



    function audioplay(){

        control.classList.remove('inited')
        control.classList.remove('pause')
        pause.style.removeProperty('display')
        control.classList.add('playing')

        if(document.querySelector('.item.active').classList.contains('pause')){
            document.querySelector('.item.active').classList.remove('pause')
        }

        labelTrackArtistHeader()

        audio.play().then(() => {

            if (audioPlay === undefined){
                intervalSet()
            }

        }).catch(() => {
            alert('Не загружается медиафайл')
        })

    }


    function nextTrack(){

        let old_active = document.querySelector('.item.active')
        let next_item

        if (old_active){

            old_active.classList.remove('active')
            next_item = old_active.nextElementSibling ? old_active.nextElementSibling : old_active.parentElement.firstElementChild
            next_item.classList.add('active')

        }else{
            next_item = document.querySelectorAll('.item')[0]

            if (!next_item){
                return
            }

            next_item.classList.add('active')
        }

        if (next_item){

            labelTrackArtistHeader()

            audio.src = next_item.querySelector('[data-url]').getAttribute('data-url')
            audio.currentTime = 0;
            audioplay()
        }

    }



    function prevTrack(){

        let old_active = document.querySelector('.item.active')
        let prev_item

        if (old_active){
            old_active.classList.remove('active')
            prev_item = old_active.previousElementSibling ? old_active.previousElementSibling : old_active.parentElement.lastElementChild
            prev_item.classList.add('active')

        }else{
            prev_item = document.querySelectorAll('.item')[0]

            if (!prev_item){
                return
            }

            prev_item.classList.add('active')

        }


        labelTrackArtistHeader()

        audio.src = prev_item.querySelector('[data-url]').getAttribute('data-url')
        audio.currentTime = 0;
        audioplay()
    }




    function pausefunc() {

        audio.pause();

        document.querySelector('.item.active') && document.querySelector('.item.active').classList.add('pause')

        pause.style.display = 'none'
        control.classList.remove('playing')
        control.classList.add('pause')

        //clearInterval(audioPlay);

    }


    function labelTrackArtistHeader(){

        let trackName = document.querySelector('.item.active .description .track')
        let artist = document.querySelector('.item.active .description .artist')

        //сделать проверку на существование

        //let target = document.querySelector('.track-name')
        let targetArtist = document.querySelector('.track-name .artist')
        let targetTrack= document.querySelector('.track-name .track')

        targetArtist.innerText = artist.innerText
        targetTrack.innerText = trackName.innerText
        //target.style.visibility = 'visible'

    }



    document.addEventListener("visibilitychange", function() {

        let data = {
            volume: mute.classList.contains('active') ? mute.volumeRate : audio.volume,
            mute: mute.classList.contains('active')
        }

        if (document.visibilityState === "hidden") {
            navigator.sendBeacon("/inform", JSON.stringify(data));
        }
    });





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

        let trackElement = this.closest('.item').querySelector('.add[data-track-id]')

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

        let divAddToPlaylist = this.closest('.add-to-playlist')

        if (divAddToPlaylist){
            divAddToPlaylist.classList.remove('active')
        }

        if(typeof res.error !== 'undefined' || typeof res.success === 'undefined'){
            throw new Error(res.message || null)
        }

        trackElement.remove()


    }catch (e){

        console.error(e)

        if(typeof e.message !== 'undefined'){
            alert(e.message)
        }

    }
}

function deleteOrAddToPlaylist(){

    document.querySelectorAll('[data-track-id].delete, [data-track-id].add').forEach(item => {


        if(item.classList.contains('delete')){
            item.addEventListener('click', deleteTrack)
        }

        if(item.classList.contains('add')){

            item.addEventListener('click', function (e){
                //e.stopPropagation()

                let menu = this.nextElementSibling

                if (menu){
                    menu.classList.toggle('active')

                    let btnClose = menu.querySelector('button.btn-secondary')

                    if (btnClose){

                        btnClose.addEventListener('click', buttonClose)

                        function buttonClose(){
                            menu.classList.toggle('active')
                            btnClose.removeEventListener('click', buttonClose)
                        }
                    }
                }

            })

            item.nextElementSibling.querySelectorAll('li.add-to-playlist-item').forEach(i => {
                i.addEventListener('click', addTrack)
            })

        }


    })

}

deleteOrAddToPlaylist()



function activeMenuNewPlaylist() {

    document.querySelectorAll('.create-new-playlist > button').forEach(item => {

        item.addEventListener('click', () => {
            menu = item.closest('.create-new-playlist').querySelector('.menu-create-playlist')

            if (menu){
                menu.classList.toggle('active')
            }
        })

    })
}


activeMenuNewPlaylist()




async function requestNewPlaylist(){

    try {

        if (this.type === 'submit'){

            let name = this.closest('.menu-create-playlist').querySelector('input[type=text]').value

            if (!name){
                throw new Error('Не заданно имя плейлиста')
            }

            name = name.replace(/[^a-zA-ZА-Яа-я0-9\s]/ig, '').trim()

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

            }

            // повешать обработчик на удаления плейлиста
            eventDeletePlaylist()

            // обработчик на ссылку перехода на этот плейлист
            playlistElement.querySelector('a').addEventListener('click', linkEventHandler)

            let modalWin = document.querySelectorAll('.add-to-playlist')

            if (modalWin.length){

                for (let elem of modalWin){

                    let ul = elem.querySelector('ul')
                    let newLi = document.createElement('li')
                    newLi.className = 'add-to-playlist-item'
                    newLi.setAttribute('data-playlist-id', res.id)
                    newLi.innerText = res.name
                    ul.append(newLi)

                    newLi.addEventListener('click', addTrack)
                }

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


function menuCreatePlaylist(){

    document.querySelectorAll('.menu-create-playlist input[type=submit], .menu-create-playlist input[type=reset]').forEach(item => {
        item.addEventListener('click', requestNewPlaylist)
    })
}

menuCreatePlaylist()





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

                let modalWin = document.querySelectorAll('.add-to-playlist')

                if (modalWin.length){

                    for (let elem of modalWin){
                        let li = elem.querySelector(`[data-playlist-id="${id}"]`)
                        li.removeEventListener('click', addTrack)
                        li.remove()
                    }

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



document.querySelectorAll('.burger').forEach(item => {
    item.addEventListener('click', function (){

        let sideBar = document.querySelector('.content')

        if (sideBar){
          sideBar.classList.toggle('active')
        }
    })
})






async function linkEventHandler(event){
    event.preventDefault()

    let element = {
        track: {
            0: `<li class="item">#content#</li>`,
            1: `<div class="action play" data-url="#path#"></div>
                <div class="description"><span class="artist">#artistname#</span> - <span class="track">#trackname#</span></div>
                <div class="duration"><span>#trackduration#</span></div>
                <a href="#link#" download="#artistname# - #trackname#.mp3" class="download" data-track-id="#trackid#" title="Скачать трек"></a>`,
            2: `<div class="#deleteOrAdd#" title="#delOrAdd#" data-track-id="#trackid#"></div>`,
            3: `<div class="modal-dialog add-to-playlist">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Выберите в какой плейлист добавить трек</h5>
                        </div>
                        <div class="modal-body">
                            <ul>
                               #playlists#
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>`
        },
        artist: ` <li class="item-artist" >
                        <a href="#aliasArtist#">
                            <div>
                                <img src="#artistImg#" alt="нет фото">
                                <span>#artistname#</span>
                            </div>
                        </a>
                </li>`,
        playlist: ` <li class="add-to-playlist-item" data-playlist-id="#playlistid#">#playlistname#</li>`,
        pagination: ` <div class="pagination">
                            <div class="pagination-description">
                                #pagination#
                            </div>
                        </div>`,
        sidebar: {
            0: `    <h2>Плейлисты</h2>
                    <div class="create-new-playlist boxShadow">
                        <button>Создать новый плейлист</button>
                        <div class="menu-create-playlist boxShadow">
                            <div>
                                <input type="text" placeholder="Введите название плейлиста">
                            </div>
                            <div>
                                <input type="submit" value="Создать">
                                <input type="reset" value="отменить">
                            </div>
                        </div>
                   </div>
                   <span class="notPlaylist">У Вас еще нет не одного плейлиста</span>`,
            1:  `<h2>Плейлисты</h2><span>Чтобы создавать свои плейлисты авторизуйтесь</span>`
        }
    }

    try{

        let href

        switch (this.tagName){

            case 'A':
                if (!this.hasAttribute('href')){
                    throw new Error('У тэга A нет href')
                }

                if (this.closest('.desktop-sidebar')){

                    if (document.querySelector('.content.active')){
                        document.querySelector('.content.active').classList.remove('active')
                    }
                }

                href = this.getAttribute('href')
                break

            case 'FORM':
                let formData = new FormData(this)
                href = `${this.getAttribute('action')}?search=${formData.get('search')}&choice=${formData.get('choice')}`
                break

            default:
                throw new Error('Не поддерживаемый тэг')

        }

        history.pushState(null, null, href ? href : '/')

        let response = await fetch ( href ? href : '/', {headers: {'X-Requested-With': 'Request'}})
        let res

        if (response.ok){
            res = await response.json()

            let tracks = []
            let artists = []

            if (res['tracks'] && res['tracks'].length){

                let trackElement
                let duration
                let el2

                for (let track of res['tracks']){

                    trackElement = element.track["1"]
                    trackElement = trackElement.replace(/#path#/ig, res['uploadDir'] + track['link'])
                    trackElement = trackElement.replace(/#artistname#/ig, track['artist_name'])
                    trackElement = trackElement.replace(/#trackname#/ig, track['name'])

                    duration = track['duration'] ? String(Math.floor(track['duration']/60)).replace(/^\d?$/, '0$&') + ':' + String(track['duration']%60).replace(/^\d?$/, '0$&')  : ''

                    trackElement = trackElement.replace(/#trackduration#/ig, duration)
                    trackElement = trackElement.replace(/#trackid#/ig, track['id'])
                    trackElement = trackElement.replace(/#link#/ig, res['uploadDir'] + track['link'])

                    if (res['playlists'] && res['playlists'].length){

                        if (/\?.*?pl=\d+/.test(href)){

                            el2 = element.track["2"].replace(/#deleteOrAdd#/ig, 'delete')
                            el2 = el2.replace(/#trackid#/ig, track['id'])
                            el2 = el2.replace(/#delOrAdd#/ig, 'Удалить трек из плейлиста')
                            trackElement += el2

                        }else{

                            el2 = element.track["2"].replace(/#deleteOrAdd#/ig, 'add')
                            el2 = el2.replace(/#trackid#/ig, track['id'])
                            el2 = el2.replace(/#delOrAdd#/ig, 'Добавить трек в плейлист')
                            trackElement += el2

                        }

                        let playlists = ''
                        let val = ''

                        for (let playlist of res['playlists']){

                            val = element.playlist.replace(/#playlistid#/ig, playlist['id'])
                            val = val.replace(/#playlistname#/ig, playlist['name'])
                            playlists += val

                        }

                        trackElement += element.track["3"].replace(/#playlists#/ig, playlists)

                    }else{

                        let pl = document.querySelector('.block-cont.boxShadow')

                        if (pl){

                            // если пользовыатель залогинен
                            if (typeof res['user'] !== 'undefined' && res['user']){
                                pl.innerHTML = element.sidebar["0"]
                                activeMenuNewPlaylist()
                                menuCreatePlaylist()

                            }else{
                                pl.innerHTML = element.sidebar["1"]
                            }

                        }


                    }

                    trackElement = element.track["0"].replace(/#content#/ig, trackElement)
                    tracks.push(trackElement)

                }

            }else if (res['artists'] && res['artists'].length){

                let artistElement

                for (let artist of res['artists']){

                    artistElement = element.artist.replace(/#aliasArtist#/ig, '/?artist=' + artist['alias'])
                    artistElement = artistElement.replace(/#artistname#/ig, artist['name'])
                    artistElement = artistElement.replace(/#artistImg#/ig,  artist['img'] ? res['uploadDir'] + artist['img'] : res['uploadDir'] + 'artist/card.jpg')
                    artists.push(artistElement)

                }

            }

            // установка активного плейлиста
            if (res['playlists'] && res['playlists'].length){
                let activePlaylistId = null

                for (let playlist of res['playlists']){

                    if (typeof playlist['active'] !== 'undefined' && playlist['active'] === true){
                        activePlaylistId = +playlist['id']
                        break
                    }
                }

                let playlistActive = document.querySelector('.playlist-item.active')

                if (playlistActive){
                    playlistActive.classList.remove('active')

                }

                if (activePlaylistId){
                    let newActivePlaylist = document.querySelector(`.playlist-item[data-playlist-id="${activePlaylistId}"]`)

                    if (!newActivePlaylist){
                        throw new Error('Не найден плейлист для установки его активным')
                    }

                    newActivePlaylist.classList.add('active')

                }

            }

            let ul = document.querySelector('ul.mainSongs') ? document.querySelector('ul.mainSongs') : document.querySelector('ul.artist')

            if (ul){

                let ul2 = ul.querySelectorAll('li.item').length ? ul.querySelectorAll('li.item') : ul.querySelectorAll('li.item-artist')

                ul2.forEach(item => {
                    item.remove()
                })
            }

            if (tracks.length){

                if (!ul){
                    ul = document.createElement('ul')
                    ul.classList.add('mainSongs')

                    let parentUl = document.querySelector('.module-layout.boxShadow')

                    if (!parentUl){
                        throw new Error('Не найден элемент .module-layout.boxShadow')
                    }

                    parentUl.append(ul)

                }

                if (ul.classList.contains('artist')){
                    ul.classList.remove('artist')
                    ul.classList.add('mainSongs')
                }

                ul.innerHTML = tracks.join('')
                deleteOrAddToPlaylist()

                ul.querySelectorAll('.play').forEach(item => {
                    item.addEventListener('click', playElementEvent)
                })


            }else if (artists.length){

                if (ul.classList.contains('mainSongs')){
                    ul.classList.remove('mainSongs')
                    ul.classList.add('artist')
                }

                ul.innerHTML = artists.join('')

            }


            let pagination = document.querySelector('.pagination')

            if (res['pages']){

                if (pagination){
                    pagination.querySelector('.pagination-description').innerHTML = res['pages']

                }else{
                    pagination = element.pagination.replace(/#pagination#/ig, res['pages'])
                    document.querySelector('.module-layout.boxShadow').insertAdjacentHTML('beforeend', pagination)
                }

                eventLink('.pagination')

            }else{

                if (pagination){
                    pagination.remove()
                }

            }

        }



    }catch (e){

        console.error(e)

        if(typeof e.message !== 'undefined'){
            alert(e.message)
        }

    }

}

function eventLink(tag){
    document.querySelector(tag).querySelectorAll('a').forEach(item => {
        item.addEventListener('click', linkEventHandler)
    })
}

eventLink('main')



document.querySelectorAll('form[data-form-search]').forEach(form => {
    form.addEventListener('submit', linkEventHandler)
})























