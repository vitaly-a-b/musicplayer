<?php if(!$this->userData): ?>
    <div class="modal" data-modal-target="lk">
        <div class="modal-wrapper">
            <div class="registration-titles" style="display: flex; justify-content: center; flex-wrap: wrap">
                <h2 style="cursor: pointer; margin-right: 20px"><?=$this->translateEl('Регистрация')?></h2>
                <h2 style="cursor: pointer"><?=$this->translateEl('Вход')?></h2>
            </div>

            <form action="<?= $this->alias(['login' => 'registration'])?>" method="post">

                <input type="text" name="name" required placeholder="<?=$this->translateEl('Ваше имя')?>*" value="<?=$this->setFormValues('name', 'userData')?>">
                <input type="email" name="email" required placeholder="E-mail*" value="<?=$this->setFormValues('email', 'userData')?>">
                <input type="tel" name="phone" required placeholder="<?=$this->translateEl('Телефон')?>*" value="<?=$this->setFormValues('phone', 'userData')?>">
                <input type="password" name="password" required placeholder="<?=$this->translateEl('Пароль')?>*" value="">
                <input type="password" name="confirm_password" required placeholder="<?=$this->translateEl('Подтверждение пароля')?>*" value="">

                <div class="order-btn">
                    <button type="submit" class="button button_dark button_big"><?=$this->translateEl('Регистрация')?></button>
                </div>

            </form>

            <form action="<?= $this->alias(['login' => 'login'])?>" method="post" style="display: none">

                <input type="text" name="login" required placeholder="<?=$this->translateEl('E-mail или телефон')?>*" value="">
                <input type="password" name="password" required placeholder="<?=$this->translateEl('Пароль')?>*" value="">

                <div class="order-btn">
                    <button type="submit" class="button"><?=$this->translateEl('Авторизация')?></button>
                </div>

            </form>
        </div>
    </div>
<?php endif;  ?>


