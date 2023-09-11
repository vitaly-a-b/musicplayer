<div class="wq-main-form__controls wq-controls">
    <?php if((!isset($this->userData['credentials']) ||
            !is_array($this->userData['credentials']) ||
            isset($this->userData['credentials'][$this->table]['add'])) && empty($no_add) && $this->getController() === 'show'):?>

        <a href="<?=$this->adminPath?>add/<?=$this->table?>" class="wq-controls__button wq-button wq-button_fern _btn">
            добавить
        </a>

    <?php endif;?>

    <a href="<?=$_SERVER['HTTP_REFERER'] ?? ''?>" class="wq-controls__button wq-button wq-button_havelock _btn">
        назад
    </a>

</div>
