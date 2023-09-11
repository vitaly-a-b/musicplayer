<!doctype html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, shrink-to-fit=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php $this->getMeta()?>

    <?php $this->getStyles()?>


</head>

<body>

    <header class="header">

        <audio id="player_from_mejs" src="" preload="none" type="audio/mp3" class="hide"></audio>

        <div class="container">


            <div class="player-controls inited">

                <div class="group additional  closed">
                    <div class="download">
                        <a target="_blank" class="dl" aria-label="Скачать трек по ссылке"></a>
                    </div>
                    <div class="repeat"></div>
                    <div class="shuffle"></div>
                    <div class="mute"></div>
                    <div class="volume-bar">
                        <div class="volume-bar-value" style="width: 90%;"></div>
                    </div>
                </div>

                <div class="group track-name">
                    <span class="track">track</span> — <span class="artist">artist</span>
                </div>

                <div class="group basic">
                    <div class="prev"></div>
                    <div class="play"></div>
                    <div class="pause" style="display:none;"></div>
                    <div class="next"></div>
                </div>


            </div>

            <div class="lk">
                <a href="<?=$this->alias(['login'=> 'logout'])?>" class="header-interaction-item " <?=!$this->userData ? 'data-modal-w="lk"' : ''?>>
                    <div class="icon-user"></div>
                    <span class="header-divisions-item-text"><?=$this->userData['name'] ?? $this->translateEl('Вход / регистрация') ?></span>
                </a>
            </div>


            <div class="progress">
                <div class="seek-bar">
                    <span class="currentTime">10</span>
                    <span class="timeLeft">20</span>
                    <div class="play-bar"></div>
                </div>
            </div>

        </div>



    </header>