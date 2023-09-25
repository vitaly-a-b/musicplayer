<main>

    <div class="container">
        <div class="content">
            <div class="centerMain">

                <div class="module-layout boxShadow">

                    <!-- поиск -->
                    <?=$this->search?>
                    <!-- поиск -->


                    <?php if (!empty($tracks)): ?>

                        <ul class="mainSongs unstyled ajaxContent songs">

                            <?php foreach ($tracks as $track): ?>

                                <li class="item" >

                                    <div class="action play" data-url="<?=PATH . UPLOAD_DIR . $track['link']?>"></div>
                                    <div class="description">
                                        <span class="artist"><?=$track['artist_name']?> </span> - <span class="track"><?=$track['name']?></span>
                                    </div>

                                    <div class="duration">
                                        <span><?=!empty($track['duration']) ? (sprintf('%02d', floor($track['duration']/60)) . ':' . sprintf('%02d', $track['duration']%60)) : '' ?></span>
                                    </div>

                                    <a href="<?=PATH . UPLOAD_DIR . $track['link']?>" download="<?=$track['artist_name']?> - <?=$track['name']?>.mp3" class="download" data-track-id="<?=$track['id']?>"></a>

                                    <?php if (!empty($playlists)):?>
                                        <div class="<?=isset($_GET['pl']) ? 'delete' : 'add'?>" data-track-id="<?=$track['id']?>"></div>

                                         <?php if (!isset($_GET['pl'])):?>




                                                <div class="modal-dialog add-to-playlist">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Выберите в какой плейлист добавить трек</h5>

                                                        </div>
                                                        <div class="modal-body">

                                                            <ul>
                                                                <?php foreach ($playlists as $playlist):?>
                                                                    <li class="add-to-playlist-item" data-playlist-id="<?=$playlist['id']?>"><?=$playlist['name']?></li>
                                                                <?php endforeach;?>
                                                            </ul>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>




                                         <?php endif;?>
                                    <?php endif;?>



                                </li>

                            <?php endforeach;?>


                    </ul>

                    <?php elseif ($this->getController() === 'search'):?>
                        <span>Ничего не найденно</span>
                    <?php endif;?>

                </div>

            </div>

            <!-- боковая панель -->
            <?=$this->sidebar?>
            <!-- боковая панель -->

        </div>

    </div>


</main>