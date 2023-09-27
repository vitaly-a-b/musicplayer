
<div class="desktop-sidebar">
    <div class="boxShadow">
        <ul class="menu-box">
            <li class="pinned">
                <a href="<?=$this->alias('artist')?>"><?=$this->translateEl('Исполнители')?></a>
            </li>
            <li class="pinned">
                <a href="<?=$this->alias('')?>"><?=$this->translateEl('Коллекция')?></a>
            </li>

        </ul>

    </div>

    <div class="boxShadow block-cont">
        <h2>Плейлисты</h2>

        <?php if (empty($this->userData)): ?>

            <span><?=$this->translateEl('Чтобы создавать свои плейлисты авторизуйтесь')?></span>

        <?php else:?>

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

            <?php if (!empty($playlists)): ?>

                <ul class="playlists">

                    <?php foreach ($playlists as $playlist): ?>
                        <li class="playlist-item <?=!empty($playlist['active']) ? 'active' : '' ?>" data-playlist-id="<?=$playlist['id']?>">
                            <a href="<?=$this->alias('', 'pl=' . $playlist['id'])?>"><?=$playlist['name']?></a>
                            <div class="delete"></div>
                        </li>
                    <?php endforeach;?>

                </ul>

            <?php else:?>
                <span class="notPlaylist"><?=$this->translateEl('У Вас еще нет не одного плейлиста')?></span>
            <?php endif;?>

        <?php endif;?>

    </div>

    <?php if (!empty($styles)): ?>
        <div class="boxShadow style-genre">

            <h3>Жанры
                <span class="showAll"></span>
            </h3>

            <ul class="genre">

                <?php foreach ($styles as $style): ?>

                    <li class="pinned">
                        <a href="<?=$this->alias('', 'genre='. $style['alias'])?>"><?=$style['name']?></a>
                    </li>

                <?php endforeach;?>



            </ul>
        </div>
    <?php endif; ?>

</div>
