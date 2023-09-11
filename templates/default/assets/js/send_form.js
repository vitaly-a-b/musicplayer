document.addEventListener('DOMContentLoaded', () => {

    // поиск всех форм на странице
    document.querySelectorAll('form').forEach(form => {

        // абработчик события отправки формы
        form.addEventListener('submit', e => {

            // если событие инициировал не скрипт
            if(e.isTrusted){

                // отменяем отправку формы
                e.preventDefault();

                // отправляем запрос на получение токена
                Ajax({

                    data: {ajax: 'token'}

                }).then(res => {

                    // если ответ пришел, то создаем скрытый элемент 'input' с полученным токеном.
                    if(res){

                        let input = document.createElement('input')
                        input.type = 'hidden'
                        input.name = 'token'
                        input.value = res
                        form.append(input)

                    }

                    // отправляем форму
                    form.submit();

                })

            }
        })
    })

})