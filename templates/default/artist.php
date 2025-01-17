<main>

    <div class="container">
        <div class="content">
            <div class="centerMain">

                <div class="module-layout boxShadow">

                    <!-- поиск -->
                    <?=$this->search?>
                    <!-- поиск -->


                    <?php if (!empty($artists)): ?>

                        <ul class="artist">

                            <?php foreach ($artists as $artist): ?>

                                <li class="item-artist" >
                                    <a href="<?=$this->alias('', 'artist=' . $artist['alias'])?>">
                                        <div>
                                            <img src="<?=!empty($artist['img']) ? PATH . UPLOAD_DIR . $artist['img'] :  PATH . UPLOAD_DIR .'artist/card.jpg'?>" alt="нет фото">
                                            <span><?=$artist['name']?></span>
                                        </div>
                                    </a>


                                </li>

                            <?php endforeach;?>


                        </ul>

                    <?php elseif ($this->getController() === 'search'):?>
                        <span>Ничего не найденно</span>
                    <?php endif;?>

                    <?php if(!empty($pages)): ?>
                        <div class="pagination">

                            <div class="pagination-description">
                                <?php $this->pagination($pages) ?>
                            </div>

                        </div>
                    <?php endif;?>

                </div>

            </div>

            <!-- боковая панель -->
            <?=$this->sidebar?>
            <!-- боковая панель -->

        </div>

    </div>


</main>