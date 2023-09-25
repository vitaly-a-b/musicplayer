<!doctype html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, shrink-to-fit=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php $this->getMeta()?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <?php $this->getStyles()?>


</head>

<body>

    <header class="header">

        <audio id="player_from_mejs" src="" preload="none" type="audio/mp3" class="hide"></audio>

        <div class="container">


            <div class="player-controls inited">

                <div class="group additional  closed">
                    <div class="repeat"></div>
                    <div class="shuffle"></div>
                    <div class="volume">
                        <div class="mute"></div>
                        <div class="volume-bar">
                            <div class="volume-bar-value" style="width: 90%;"></div>
                        </div>
                    </div>

                </div>

                <div class="track-name">
                    <h4>
                        <span class="artist">track</span>  —  <span class="track">artist</span>
                    </h4>

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

            <button class="burger"></button>


            <div class="line-progress">
                <div class="seek-bar">
                    <span class="currentTime"></span>
                    <span class="all-time"></span>
                    <div class="play-bar"></div>
                </div>
            </div>

        </div>



    </header>