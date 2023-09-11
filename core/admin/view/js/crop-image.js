
document.addEventListener('DOMContentLoaded', () => {

    let cropContainer = document.querySelector('[data-crop]')

    let columnName = null;

    let targetContainer = null;

    let changeFlag = false

    let cropBorder = null

    if(!cropContainer){

        cropContainer = document.createElement('div')

        cropContainer.setAttribute('data-crop', true)

        let template = '<div class="image-block" xmlns="http://www.w3.org/1999/html">' +
                            '<div class="change-controls">' +
                                '<div>' +
                                    '<button type="button" data-change="drawRotate" data-clockwise="true">Rotate 90deg</button>' +
                                    '<button type="button" data-change="drawRotate">Rotate -90deg</button>' +
                                    '<button type="button" data-change="crop">Crop</button>' +
                                '</div>' +
                                '<div>' +
                                     '<button type="button" data-save="true">Save</button>' +
                                     '<button type="button" data-close="true">Close</button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="img-preview"></div>' +
                        '</div>'

        cropContainer.insertAdjacentHTML('beforeend', template)

        document.body.append(cropContainer)

    }

    document.addEventListener('click', e => {

        if(e.target.tagName === 'IMG' &&
            (e.target.closest('.img_show') || e.target.closest('.wq-block__img-gallery')) &&
            e.target.getAttribute('alt') !== '_image'){

            e.stopPropagation()

            targetContainer = e.target.parentElement

            let previewContainer = cropContainer.querySelector('.img-preview')

            columnName = (e.target.closest('.img_container') || e.target.closest('.wq-block')).querySelector('input[type="file"]').name

            previewContainer.innerHTML = ''

            let img = new Image();

            img.src = e.target.src

            changeFlag = false

            img.onload = () => {

                previewContainer.append(img)

                cropContainer.classList.add('_active-crop')

            }

        }

    })



    cropContainer.querySelector('[data-save]').addEventListener('click', e => {

        if(columnName && changeFlag){

            let form = document.querySelector('#main-form')

            if(form){

                let targetImg = cropContainer.querySelector('.img-preview img')

                if(targetImg){

                    let columnNameArr = columnName.split('[')

                    let dopName = '';

                    if(columnNameArr.length > 1){

                        columnName = columnNameArr[0]

                        columnNameArr.splice(0, 1)

                        dopName = '[' + columnNameArr.join('[')

                    }

                    let insertJsModified = true

                    if(targetContainer.classList.contains('wq-block__img-gallery')){

                        if(targetContainer.hasAttribute('data-deletefileid-' + columnName)){

                            dopName = `[${targetContainer.getAttribute('data-deletefileid-' + columnName)}]`

                        }else{

                            let form = document.querySelector('#main-form')

                            if(form){

                                let tableId = form.querySelector('input#tableId')

                                if(tableId){

                                    insertJsModified = false

                                    let idRow = tableId.name

                                    let id = tableId.value

                                    Ajax({
                                        type: 'POST',
                                        data: {
                                            id_row: idRow,
                                            id: id,
                                            row: columnName,
                                            table: form.querySelector('input[name="table"]').value,
                                            data: targetImg.src,
                                            fileName: targetContainer.querySelector('img').getAttribute('src').split('?')[0],
                                            ajax: 'modify_file'
                                        }
                                    }).then(res => {

                                        if(!res){

                                            alert('Ошибка сохранения данных')

                                        }else{

                                            targetContainer.querySelector('img').src = res

                                        }

                                    })

                                }

                            }

                        }

                    }

                    if(insertJsModified){

                        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="js_modified_files[${columnName}]${dopName}" value="${targetImg.src}">`)

                        targetContainer.querySelector('img').src = targetImg.src

                    }

                }

            }

        }

        cropContainer.querySelector('.img-preview').innerHTML = ''

        cropContainer.classList.remove('_active-crop')

        cropBorder = null

    })



    cropContainer.querySelector('[data-close]').addEventListener('click', () => {


        cropContainer.querySelector('.img-preview').innerHTML = ''

        cropContainer.classList.remove('_active-crop')

        cropBorder = null

    })



    cropContainer.querySelectorAll('[data-change]').forEach(item => {

        item.addEventListener('click', e => {

            if(item.getAttribute('data-change') === 'drawRotate'){

                drawRotate(item.hasAttribute('data-clockwise'))

            }else if(item.getAttribute('data-change') === 'crop'){

                cropImage()

            }

        })

    })

    cropBorder = cropContainer.querySelector('[data-crop-border]')

    let resizeFlag = false;

    let moveFlag = false

    function saveCrop(){

        let targetImg = cropContainer.querySelector('.img-preview img')

        let cropBorder = cropContainer.querySelector('[data-crop-border]')

        if(targetImg && cropBorder){

            let position = {
                top: targetImg.naturalHeight * (cropBorder.offsetTop / targetImg.clientHeight),
                left: targetImg.naturalWidth * ((cropBorder.offsetLeft - targetImg.offsetLeft) / targetImg.clientWidth),
                width: targetImg.naturalWidth * ((cropBorder.clientWidth / targetImg.clientWidth)),
                height: targetImg.naturalHeight * ((cropBorder.clientHeight / targetImg.clientHeight)),
            }

            let canvas = document.createElement('canvas')

            canvas.width = position.width

            canvas.height = position.height

            let ctx = canvas.getContext('2d');

            ctx.drawImage(targetImg, position.left, position.top, position.width, position.height, 0, 0, position.width, position.height);

            targetImg.src = canvas.toDataURL();

            targetImg.onload = () => {

                cropBorder.style.top = 0

                cropBorder.style.left = targetImg.offsetLeft + 'px'

                cropBorder.style.width = targetImg.clientWidth + 'px'

                cropBorder.style.height = targetImg.clientHeight + 'px'

            }

            changeFlag = true

        }

    }

    function cropImage(){

        if(!cropBorder){

            let template = '<div data-crop-border="resize-container">\n' +
                '  <span class="crop-save">CROP</span>\n' +
                '  <span class="resize-handle resize-handle-nw"></span>\n' +
                '  <span class="resize-handle resize-handle-ne"></span>\n' +
                '  <span class="resize-handle resize-handle-sw"></span>\n' +
                '  <span class="resize-handle resize-handle-se"></span>\n' +
                '</div>'

            cropContainer.querySelector('.img-preview').insertAdjacentHTML('beforeend', template)

            cropBorder = cropContainer.querySelector('[data-crop-border]');

        }

        let cropImg = cropBorder.previousElementSibling;

        if(cropImg){

            cropBorder.style.width = cropImg.clientWidth + 'px'

            cropBorder.style.height = cropImg.clientHeight + 'px'

            cropBorder.style.top = cropImg.offsetTop + 'px'

            cropBorder.style.left = cropImg.offsetLeft + 'px'

        }

        ['mousedown', 'mouseup'].forEach(event => {

            document.removeEventListener(event, startCropBorder)

            document.addEventListener(event, startCropBorder)

        })

        document.removeEventListener('mousemove', moveCropBorder)

        document.addEventListener('mousemove', moveCropBorder)

        let cropBtn = cropBorder.querySelector('.crop-save')

        cropBtn.removeEventListener('click', saveCrop)
        cropBtn.addEventListener('click', saveCrop)

    }

    function startCropBorder(e){

        if(e.type === 'mousedown' && (e.target === cropBorder || e.target.closest('[data-crop-border]'))){

            if(e.target.classList.contains('resize-handle')){

                resizeFlag = e.target.classList[e.target.classList.length - 1]

            }else{

                moveFlag = true

            }

        }else{

            moveFlag = resizeFlag = false

        }

    }

    function moveCropBorder(e){

        if(resizeFlag){

            e.stopPropagation()

            e.preventDefault()

            let sizes = {}

            switch (resizeFlag){

                case 'resize-handle-nw':

                    sizes.left = cropBorder.offsetLeft + e.movementX

                    sizes.top = cropBorder.offsetTop + e.movementY

                    sizes.width = cropBorder.clientWidth - e.movementX

                    sizes.height = cropBorder.clientHeight - e.movementY

                    break

                case 'resize-handle-ne':

                    sizes.top = cropBorder.offsetTop + e.movementY

                    sizes.width = cropBorder.clientWidth + e.movementX

                    sizes.height = cropBorder.clientHeight - e.movementY

                    break

                case 'resize-handle-se':

                    sizes.width = cropBorder.clientWidth + e.movementX

                    sizes.height = cropBorder.clientHeight + e.movementY

                    break

                case 'resize-handle-sw':

                    sizes.left = cropBorder.offsetLeft + e.movementX

                    sizes.width = cropBorder.clientWidth - e.movementX

                    sizes.height = cropBorder.clientHeight + e.movementY

                    break

            }

            if(sizes){

                let cropImg = cropBorder.previousElementSibling;

                if(typeof sizes.left !== 'undefined'){

                    if(sizes.left < cropImg.offsetLeft){

                        sizes.left = cropImg.offsetLeft

                    }

                }

                if(typeof sizes.top !== 'undefined'){

                    if(sizes.top < cropImg.offsetTop){

                        sizes.top = cropImg.offsetTop

                    }

                }

                if(typeof sizes.width !== 'undefined'){

                    let left = (sizes.left || cropBorder.offsetLeft) - cropImg.offsetLeft

                    if(sizes.width + left > cropImg.clientWidth){

                        sizes.width = cropImg.clientWidth - left

                    }

                }

                if(typeof sizes.height !== 'undefined'){

                    let top = (sizes.top || cropBorder.offsetTop) - cropImg.offsetTop

                    if(sizes.height + top > cropImg.clientHeight){

                        sizes.height = cropImg.clientHeight - top

                    }

                }

                for(let i in sizes){
                        cropBorder.style[i] = sizes[i] + 'px'

                }

            }

        }else if(moveFlag){

            e.stopPropagation()

            e.preventDefault()

            let cropImg = cropBorder.previousElementSibling;

            let offsetLeft = cropBorder.offsetLeft + e.movementX

            let offsetTop = cropBorder.offsetTop + e.movementY

            if(offsetLeft >= cropImg.offsetLeft && cropBorder.clientWidth + offsetLeft <= cropImg.clientWidth + cropImg.offsetLeft){

                cropBorder.style.left = offsetLeft + 'px'

            }

            if(offsetTop >= cropImg.offsetTop && cropBorder.clientHeight + offsetTop <= cropImg.clientHeight + cropImg.offsetTop){

                cropBorder.style.top = offsetTop + 'px'

            }


        }

    }

    function drawRotate (clockwise) {

        let img = cropContainer.querySelector('.img-preview img')

        let canvas = document.createElement('canvas')

        const degrees = clockwise === true? 90: -90;

        const iw = img.naturalWidth;
        const ih = img.naturalHeight;

        canvas.width = ih;

        canvas.height = iw;

        let ctx = canvas.getContext('2d');

        if(clockwise){

            ctx.translate(ih, 0);

        } else {

            ctx.translate(0, iw);

        }

        ctx.rotate(degrees * Math.PI/180);

        ctx.drawImage(img, 0, 0);

        img.src = canvas.toDataURL();

        changeFlag = true

    }
})

