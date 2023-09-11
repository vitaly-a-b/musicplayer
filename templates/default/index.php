<main>


    <div class="container">
        <div class="content">
            <div class="centerMain">

                <div class="headPopular boxShadow">

                    <div class="module-search">

                        <form id="main-search" action="<?=$this->alias('search')?>" method="get">
                            <div class="form">
                                <div class="inputSearch">
                                    <div class="inInputSearch">
                                        <input type="search" name="search" value="" id="search" placeholder="Поиск музыки" aria-label="Поиск">
                                        <div class="dropdown name_focus">
                                            <select name="choice" class="searchArtist">
                                                <option value="name">По названию</option>
                                                <option value="artist">По исполнителю</option>

                                            </select>
                                            <button title="Начать поиск музыки" type="submit">Найти</button>
                                        </div>
                                        <div class="dropdownButton"></div>

                                    </div>


                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="module-popular">
                        <div class="collapsedBox collapsedBoxMin">

                            <h3>Популярные жанры
                                <span class="showAll"></span>
                            </h3>

                            <ul class="unstyled">

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

                </div>

                <div class="module-layout boxShadow">

                    <?php if (!empty($tracks)): ?>

                        <ul class="mainSongs unstyled ajaxContent songs">

                            <?php foreach ($tracks as $track): ?>

                                <li class="item" >

                                    <div class="action play" data-url="<?=PATH . UPLOAD_DIR . $track['link']?>"></div>
                                    <div class="description"><span><?=$track['artist_name']?></span> - <span><?=$track['name']?></span></div>
                                    <div class="duration"><span><?=$track['duration']?></span></div>
                                    <div class="download"></div>
                                    <?php if (!empty($playlists)):?>
                                        <div class="<?=isset($_GET['pl']) ? 'delete' : 'add'?>" data-track-id="<?=$track['id']?>"></div>

                                         <?php if (!isset($_GET['pl'])):?>
                                            <div class="add-to-playlist">
                                                <ul>
                                                   <?php foreach ($playlists as $playlist):?>
                                                        <li class="add-to-playlist-item" data-playlist-id="<?=$playlist['id']?>"><?=$playlist['name']?></li>
                                                    <?php endforeach;?>
                                                </ul>
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

            <div class="desktop-sidebar">

                <div class="boxShadow">

                    <ul class="menu-box">
                        <li class="pinned">
                            <a href="https://muzofond.fm/collections/new">
                                Новинки                </a>
                        </li>
                        <li class="pinned">
                            <a href="https://muzofond.fm/popular">
                                Жанры                </a>
                        </li>
                        <li class="pinned">
                            <a href="https://muzofond.fm/collections/artists">
                                Исполнители                </a>
                        </li>
                        <li class="pinned">
                            <a href="https://muzofond.fm/collections/albums">
                                Альбомы                </a>
                        </li>
                        <li class="pinned">
                            <a href="https://muzofond.fm/radio-online">
                                Радио онлайн                </a>
                        </li>
                        <li class="pinned">
                            <a href="https://muzofond.fm/collections">
                                Сборники                </a>
                        </li>
                        <li class="pinned">
                            <a href="https://muzofond.fm/collections/holidays">
                                Праздники                </a>
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

                <div class="boxShadow"></div>

            </div>
        </div>

    </div>


</main>