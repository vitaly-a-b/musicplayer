
<div class="desktop-sidebar">
    <div class="boxShadow">
        <ul class="menu-box">
            <li class="pinned">
                <a href="https://muzofond.fm/popular">
                    Жанры                </a>
            </li>
            <li class="pinned">
                <a href="<?=$this->alias('artist')?>"><?=$this->translateEl('Исполнители')?></a>
            </li>

        </ul>

    </div>

    <div class="boxShadow block-cont">
        <h2>Плейлисты</h2>

        <?php if (empty($this->userData)): ?>

            <span><?=$this->translateEl('Чтобы создавать свои плейлисты авторизуйтесь')?></span>

        <?php else:?>

            <div class="create-new-playlist boxShadow">
                <button>создать новый плейлист</button>
                <div class="menu-create-playlist">
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

    <div class="boxShadow">

        <h3>Жанры
            <span class="showAll"></span>
        </h3>

        <ul class="genre">

            <li class="pinned">
                <a href="">Шансон</a>
            </li>

            <li class="pinned">
                <a href="">Шансон</a>
            </li>
            <li class="pinned">
                <a href="">Шансон</a>
            </li>


        </ul>
    </div>

</div>
